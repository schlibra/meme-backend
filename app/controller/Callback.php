<?php

namespace app\controller;

use app\BaseController;
use app\Request;
use Firebase\JWT\JWT;
use think\facade\Cache;
use think\Response;
use WpOrg\Requests\Requests;

class Callback extends BaseController {
    private function returnView(Request$request, $username, $nickname, $error, $avatar = ""): Response {
        if ($request->cookie("dev") === "Y") {
            $view = file_get_contents("http://localhost:5173/");
            $view = str_replace("<meta name=\"baseurl\" />", "<base href=\"http://{$request->host(true)}:5173/\" />", $view);
            $view = str_replace("<script type=\"module\" src=\"/@id/virtual:vue-devtools-path:overlay.js\"></script>", "", $view);
        } else {
            $view = file_get_contents(root_path() . "view/dist/index.html");
        }
        $platform = str_replace("api/login/callback/", "", $request->pathinfo());
        $token = "";
        if (!empty($username)) {
            $url = $request->domain();
            $payload = [
                "iss" => $url,
                "aud" => $url,
                "kid" => $url,
                "iat" => time(),
                "exp" => time() + 36000,
                "username" => $username,
                "nickname" => $nickname,
                "avatar" => $avatar,
            ];
            $token = JWT::encode($payload, thirdPartySecret, "HS256");
            Cache::set("{$platform}_$username", $token);
        }
        return response(str_replace([
            "{\$thirdPartyLoginUsername}",
            "{\$thirdPartyLoginNickname}",
            "{\$thirdPartyLoginToken}",
            "{\$thirdPartyLoginError}"
        ], [
            $username,
            $nickname,
            $token,
            $error
        ], $view));
    }
    function sckurCallback(Request$request): Response {
        $setting = getSetting();
        $clientId = $setting["sckurClientId"];
        $apiKey = $setting["sckurApiKey"];
        $redirect_uri = $request->domain() . "/api/login/callback/sckur";
        $code = $request->get("code");
        $token = Requests::get("https://api.sckur.com/auth?grant_type=authorization_code&code=$code&redirect_uri=$redirect_uri&client_id=$clientId")->body;
        $token = json_decode($token);
        if (isset($token->access_token)) {
            $access_token = $token->access_token;
            $userinfo = Requests::get("https://api.sckur.com/passport/get?api_key=$apiKey&action=get_userinfo&params=[\"all\"]", [
                "Authorization" => "Bearer $access_token"
            ])->body;
            $userinfo = json_decode($userinfo);
            $username = $userinfo->all->username;
            $nickname = $userinfo->all->nickname;
            $avatar = Requests::get($userinfo->all->avatar)->body;
            $avatar = base64_encode($avatar);
            return $this->returnView($request, $username, $nickname, "", $avatar);
        } else {
            return $this->returnView($request, "", "", $token->msg);
        }
    }
    function giteeCallback(Request$request): Response {
        $redirect_uri = explode("?", $request->url(true))[0];
        $setting = getSetting();
        $client_id = $setting["giteeClientId"];
        $client_secret = $setting["giteeClientSecret"];
        $code = $request->get("code");
        if ($code) {
            $token = Requests::post("https://gitee.com/oauth/token?grant_type=authorization_code&code=$code&client_id=$client_id&redirect_uri=$redirect_uri&client_secret=$client_secret")->body;
            $token = json_decode($token, true);
            if (isset($token["error"])) {
                return $this->returnView($request, "", "", $token["error_description"]);
            } else {
                $access_token = $token["access_token"];
                $userInfo = Requests::get("https://gitee.com/api/v5/user?access_token=$access_token")->body;
                $userInfo = json_decode($userInfo);
                $username = $userInfo->login;
                $nickname = $userInfo->name;
                $avatar = Requests::get($userInfo->avatar_url)->body;
                $avatar = base64_encode($avatar);
                return $this->returnView($request, $username, $nickname, "", $avatar);
            }
        } else {
            return $this->returnView($request, "", "", "没有获取到返回码");
        }
    }
    function githubCallback(Request$request): Response {
        $setting = getSetting();
        $client_id = $setting["githubClientId"];
        $client_secret = $setting["githubClientSecret"];
        $code = $request->get("code");
        $token = Requests::post("https://github.com/login/oauth/access_token", [], [
            "client_id" => $client_id,
            "client_secret" => $client_secret,
            "code" => $code,
            "accept" => "json"
        ])->body;
        $tokenList = [];
        foreach (explode("&", $token) as $item) {
            $value = explode("=", $item);
            if (count($value) === 2) {
                $tokenList[$value[0]] = $value[1];
            }
        }
        if (isset($tokenList["error"])) {
            return $this->returnView($request, "", "", urldecode($tokenList["error_description"]));
        }
        $access_token = $tokenList["access_token"];
        $userInfo = Requests::get("https://api.github.com/user", [
            "Authorization" => "Bearer $access_token"
        ])->body;
        $userInfo = json_decode($userInfo);
        $username = $userInfo->login;
        $nickname = $userInfo->name;
        $avatar = Requests::get($userInfo->avatar_url)->body;
        $avatar = base64_encode($avatar);
        return $this->returnView($request, $username, $nickname, "", $avatar);
    }
    function gitlabCallback(Request$request): Response {
        $setting = getSetting();
        $client_id = $setting["gitlabClientId"];
        $client_secret = $setting["gitlabClientSecret"];
        $code = $request->get("code");
        $redirect_uri = explode("?", $request->url(true))[0];
        $token = Requests::post("https://gitlab.com/oauth/token", [], [
            "client_id" => $client_id,
            "client_secret" => $client_secret,
            "code" => $code,
            "grant_type" => "authorization_code",
            "redirect_uri" => $redirect_uri
        ])->body;
        $token = json_decode($token, true);
        if (isset($token["error"])) {
            return $this->returnView($request, "", "", $token["error_description"]);
        } else {
            $access_token = $token["access_token"];
            $userinfo = Requests::get("https://gitlab.com/api/v4/user?access_token=$access_token")->body;
            $userinfo = json_decode($userinfo);
            $username = $userinfo->username;
            $nickname = $userinfo->name;
            $avatar = Requests::get(gravatar($userinfo->commit_email))->body;
            $avatar = base64_encode($avatar);
            return $this->returnView($request, $username, $nickname, "", $avatar);
        }
    }
    function microsoftCallback(Request$request): Response {
        $setting = getSetting();
        $client_id = $setting["microsoftClientId"];
        $client_secret = $setting["microsoftClientSecret"];
        $code = $request->get("code");
        $redirect_uri = explode("?", $request->url(true))[0];
        $token = Requests::post("https://login.microsoftonline.com/consumers/oauth2/v2.0/token", [], [
            "client_id" => $client_id,
            "client_secret" => $client_secret,
            "scope" => "https://graph.microsoft.com/User.Read",
            "redirect_uri" => $redirect_uri,
            "grant_type" => "authorization_code",
            "code" => $code
        ])->body;
        $token = json_decode($token, true);
        if (isset($token["error"])) {
            return $this->returnView($request, "", "", $token["error_description"]);
        }
        $access_token = $token["access_token"];
        $userinfo = Requests::get("https://graph.microsoft.com/v1.0/me", [
            "Authorization" => "Bearer $access_token"
        ])->body;
        $avatar = Requests::get("https://graph.microsoft.com/v1.0/me/photo/\$value", [
            "Authorization" => "Bearer $access_token"
        ])->body;
        $avatar = base64_encode($avatar);
        $userinfo = json_decode($userinfo);
        $username = $userinfo->mail;
        $nickname = $userinfo->displayName;
        return $this->returnView($request, $username, $nickname, "", $avatar);
    }
}