<?php

namespace app\controller;

use app\BaseController;
use app\model\BasicModel;
use app\model\SecurityModel;
use app\model\ThirdPartyModel;
use app\Request;
use think\Response;
use think\response\Json;
use think\validate\ValidateRule;

class Index extends BaseController
{
    function index(Request$request): string|Json {
        if ($request->isGet()) {
            if ($request->cookie("dev") === "Y") {
                $view = file_get_contents("http://localhost:5173/");
                $view = str_replace("<meta name=\"baseurl\" />", "<base href=\"http://{$request->host(true)}:5173/\" />", $view);
                $view = str_replace("<script type=\"module\" src=\"/@id/virtual:vue-devtools-path:overlay.js\"></script>", "", $view);
            } else {
                $view = file_get_contents(root_path() . "view/dist/index.html");
            }
            $data = [];
            $basic = BasicModel::find(1);
            $security = SecurityModel::find(1);
            $thirdParty = ThirdPartyModel::find(1);
            if ($basic) $data = array_merge($data, $basic->toArray());
            if ($security) $data = array_merge($data, $security->toArray());
            if ($thirdParty) $data = array_merge($data, $thirdParty->toArray());
            foreach ($data as $key => $value) {
                $view = str_replace("{\$$key}", $value ?? "", $view);
            }
            $language_name = "zh-cn";
            $language_data = file_get_contents(app_path()."/languages/$language_name.json");
            return str_replace("\"{\$languageData}\"", $language_data, $view);
//            return $view;
        } else {
            return json(["code" => 403, "msg" => "Method not allowed"]);
        }
    }
    function assets(Request$request): Response {
        $setting = getSetting();
        $bool = [
            "Y" => "!0",
            "N" => "!1",
        ];
        $REPLACE = [
            "[\"IURT meme 2.0\"]" => "[\"{$setting["siteName"]}\"]",
            "!0,\"enableHomeType\"" => $bool[$setting["enableHomeTyping"]],
            "!1,\"enableHomeType\"" => $bool[$setting["enableHomeTyping"]],
            "[\"IURT memes 2.0\",\"Home Title\"][0]" => "[\"{$setting["siteName"]}\",\"Home Title\"][0]",
        ];
        $filename = $request->url();
        $filepath = root_path() . "view/dist/$filename";
        if (file_exists($filepath)) {
            $ext = explode(".", $filename);
            $ext = end($ext);
            $type = match ($ext) {
                "js" => "application/javascript",
                "css" => "text/css",
                default => "text/plain",
            };
            header("Content-Type: $type");
            $data = file_get_contents($filepath);
            foreach ($REPLACE as $key => $value) {
                $data = str_replace($key, $value, $data);
            }
            return response($data)->header([
                "Content-Type" => $type
            ]);
        } else {
            return jb(404, "文件不存在", [
                "filename" => $filename,
                "realPath" => $filepath
            ]);
        }
    }
    function languageList(Request$request): Json {
        $list = scandir(app_path() . "/languages/");
        $data = [];
        foreach ($list as $item) {
            if ($item === "." || $item === "..") continue;
            $language_data = json_decode(file_get_contents(app_path() . "/languages/$item"));
            if (isset($language_data->language_name)) {
                $data[] = [
                    "code" => str_replace(".json", "", $item),
                    "name" => $language_data->language_name
                ];
            }
        }
        return jb(data: $data);
    }
}
