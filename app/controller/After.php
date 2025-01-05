<?php

namespace app\controller;

use app\BaseController;
use app\model\BindModel;
use app\model\UserModel;
use app\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Cache;
use think\response\Json;

class After extends BaseController {
    private function bindAuth(Request$request): array {
        $platform = $request->post("platform");
        $token = $request->post("token");
        $username = $request->post("username");
        if (Cache::get("{$platform}_$username") === $token) {
            $data = (array)JWT::decode($token, new Key(thirdPartySecret, "HS256"));
            if ($data["username"] === $username) {
                return returnData(data: $data);
            } else {
                return returnData(false, "token不正确");
            }
        } else {
            return returnData(false, "token无效");
        }
    }
    private function makeUserToken(Request$request, $userId): Json {
        $user = UserModel::find($userId);
        if ($user) {
            if ($user->ban === "Y") {
                return jb(401, "用户已封禁");
            } else {
                $url = $request->domain();
                $payload = [
                    "iss" => $url,
                    "aud" => $url,
                    "kid" => $url,
                    "iat" => time(),
                    "exp" => time() + 36000,
                    "username" => $user->username,
                    "email" => $user->email
                ];
                $token = JWT::encode($payload, loginSecret, "HS256");
                Cache::set($user->username, $token);
                return jb(200, "登录成功", null, null, $token);
            }
        } else {
            return jb(404, "用户不存在");
        }
    }
    function sckurAfter(Request$request): Json {
        $action = $request->post("action");
        $auth = $this->bindAuth($request);
        $username = $request->post("username");
        if ($auth["status"]) {
            if ($action === "login") {
                $bind = BindModel::where("sckurUsername", $username)->findOrEmpty();
                if ($bind->isEmpty()) {
                    return jb(401, "该账号未在平台绑定");
                } else {
                    return $this->makeUserToken($request, $bind->userId);
                }
            } elseif ($action === "bind") {
                $login = loginAuth($this->request);
                if ($login["status"]) {
                    $user = $login["data"];
                    $bind = BindModel::where("userId", $user->userId)->findOrEmpty();
                    if ($bind->isEmpty()) {
                        $bind = new BindModel;
                    }
                    $bind->sckurBind = "Y";
                    $bind->sckurUsername = $username;
                    $bind->sckurNickname = $auth["data"]["nickname"];
                    $bind->sckurAvatar = $auth["data"]["avatar"];
                    $bind->save();
                    return jb(200, "绑定成功");
                } else {
                    return jb(401, $login["msg"]);
                }
            } else {
                return jb(400, "action值不正确");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function giteeAfter(Request$request): Json {
        $action = $request->post("action");
        $auth = $this->bindAuth($request);
        $username = $request->post("username");
        if ($auth["status"]) {
            if ($action === "login") {
                $bind = BindModel::where("giteeUsername", $username)->findOrEmpty();
                if ($bind->isEmpty()) {
                    return jb(401, "该账号未在平台绑定");
                } else {
                    return $this->makeUserToken($request, $bind->userId);
                }
            } elseif ($action === "bind") {
                $login = loginAuth($this->request);
                if ($login["status"]) {
                    $user = $login["data"];
                    $bind = BindModel::where("userId", $user->userId)->findOrEmpty();
                    if ($bind->isEmpty()) {
                        $bind = new BindModel;
                    }
                    $bind->giteeBind = "Y";
                    $bind->giteeUsername = $username;
                    $bind->giteeNickname = $auth["data"]["nickname"];
                    $bind->giteeAvatar = $auth["data"]["avatar"];
                    $bind->save();
                    return jb(200, "绑定成功");
                } else {
                    return jb(401, $login["msg"]);
                }
            } else {
                return jb(400, "action值不正确");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function githubAfter(Request$request): Json {
        $action = $request->post("action");
        $auth = $this->bindAuth($request);
        $username = $request->post("username");
        if ($auth["status"]) {
            if ($action === "login") {
                $bind = BindModel::where("githubUsername", $username)->findOrEmpty();
                if ($bind->isEmpty()) {
                    return jb(401, "该账号未在平台绑定");
                } else {
                    return $this->makeUserToken($request, $bind->userId);
                }
            } elseif ($action === "bind") {
                $login = loginAuth($this->request);
                if ($login["status"]) {
                    $user = $login["data"];
                    $bind = BindModel::where("userId", $user->userId)->findOrEmpty();
                    if ($bind->isEmpty()) {
                        $bind = new BindModel;
                    }
                    $bind->githubBind = "Y";
                    $bind->githubUsername = $username;
                    $bind->githubNickname = $auth["data"]["nickname"];
                    $bind->githubAvatar = $auth["data"]["avatar"];
                    $bind->save();
                    return jb(200, "绑定成功");
                } else {
                    return jb(401, $login["msg"]);
                }
            } else {
                return jb(400, "action值不正确");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function gitlabAfter(Request$request): Json {
        $action = $request->post("action");
        $auth = $this->bindAuth($request);
        $username = $request->post("username");
        if ($auth["status"]) {
            if ($action === "login") {
                $bind = BindModel::where("gitlabUsername", $username)->findOrEmpty();
                if ($bind->isEmpty()) {
                    return jb(401, "该账号未在平台绑定");
                } else {
                    return $this->makeUserToken($request, $bind->userId);
                }
            } elseif ($action === "bind") {
                $login = loginAuth($this->request);
                if ($login["status"]) {
                    $user = $login["data"];
                    $bind = BindModel::where("userId", $user->userId)->findOrEmpty();
                    if ($bind->isEmpty()) {
                        $bind = new BindModel;
                    }
                    $bind->gitlabBind = "Y";
                    $bind->gitlabUsername = $username;
                    $bind->gitlabNickname = $auth["data"]["nickname"];
                    $bind->gitlabAvatar = $auth["data"]["avatar"];
                    $bind->save();
                    return jb(200, "绑定成功");
                } else {
                    return jb(401, $login["msg"]);
                }
            } else {
                return jb(400, "action值不正确");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function microsoftAfter(Request$request): Json {
        $action = $request->post("action");
        $auth = $this->bindAuth($request);
        $username = $request->post("username");
        if ($auth["status"]) {
            if ($action === "login") {
                $bind = BindModel::where("microsoftUsername", $username)->findOrEmpty();
                if ($bind->isEmpty()) {
                    return jb(401, "该账号未在平台绑定");
                } else {
                    return $this->makeUserToken($request, $bind->userId);
                }
            } elseif ($action === "bind") {
                $login = loginAuth($this->request);
                if ($login["status"]) {
                    $user = $login["data"];
                    $bind = BindModel::where("userId", $user->userId)->findOrEmpty();
                    if ($bind->isEmpty()) {
                        $bind = new BindModel;
                    }
                    $bind->microsoftBind = "Y";
                    $bind->microsoftUsername = $username;
                    $bind->microsoftNickname = $auth["data"]["nickname"];
                    $bind->microsoftAvatar = $auth["data"]["avatar"];
                    $bind->save();
                    return jb(200, "绑定成功");
                } else {
                    return jb(401, $login["msg"]);
                }
            } else {
                return jb(400, "action值不正确");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
}