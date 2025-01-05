<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\CommentModel;
use app\model\PicsModel;
use app\model\ScoreModel;
use think\Request;
use think\Response;
use think\response\Json;
use hg\apidoc\annotation as ApiDoc;

#[ApiDoc\Title("图片接口")]
class Pics extends BaseController {
    #[ApiDoc\Title("获取图片列表接口")]
    #[ApiDoc\Url("/api/pics/pic")]
    #[ApiDoc\Method("GET")]
    #[ApiDoc\Query("pageSize", type: "int", require: false, desc: "分页大小")]
    #[ApiDoc\Query("pageNum", type: "int", require: false, desc: "分页页码")]
    #[ApiDoc\Header("Authorization", type: "string", require: false, desc: "Bearer Token")]
    #[ApiDoc\Returned("picId", type: "int", require: true, default: 1, desc: "图片ID")]
    #[ApiDoc\Returned("name", type: "string", require: true, default: "图片名称", desc: "图片名称")]
    #[ApiDoc\Returned("description", type: "string", require: true, default: "图片描述", desc: "图片描述")]
    #[ApiDoc\Returned("userId", type: "int", require: true, default: 1, desc: "上传者ID")]
    #[ApiDoc\Returned("verified", type: "string", require: true, default: "Y", desc: "是否通过审核")]
    #[ApiDoc\Returned("create", type: "datetime", require: true, default: "2024-07-01", desc: "上传时间")]
    #[ApiDoc\Returned("update", type: "datetime", require: true, default: "2024-07-01", desc: "更新时间")]
    #[ApiDoc\Returned("delete", type: "datetime", require: true, default: "2024-07-01", desc: "删除时间")]
    #[ApiDoc\Returned("scored", type: "string", require: true, default: "Y", desc: "是否已打分")]
    #[ApiDoc\Returned("score", type: "float", require: true, default: 5, desc: "评分")]
    #[ApiDoc\Returned("myScore", type: "float", require: false, default: 5, desc: "我的评分")]
    #[ApiDoc\Returned("nickname", type: "string", require: true, default: "用户1", desc: "上传者昵称")]
    #[ApiDoc\Returned("url", type: "string", require: true, default: "http://127.0.0.1/api/pics/image/1", desc: "图片url")]
    public function index(Request$request): Json {
        $pageSize = (int)$request->get("pageSize", 20);
        $pageNum = (int)$request->get("pageNum", 1);
        $name = $request->get("name", "");
        $auth = loginAuth($request);
        $userId = null;
        if ($auth["status"]) {
            $user = $auth["data"];
            $userId = $user->userId;
        }
        $pics = PicsModel::where("delete")
            ->whereLike("name", "%$name%")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select();
        $picsCount = PicsModel::where("delete")
            ->whereLike("name", "%$name%")
            ->count();
        $scores = ScoreModel::where("delete")->select();
        foreach ($pics as &$picsItem) {
            $scoreSum = 0;
            $scoreCount = 0;
            $picsItem->scored = "N";
            $picsItem->score = 0;
            foreach ($scores as $scoreItem) {
                if ($scoreItem->picId === $picsItem->picId) {
                    if ($scoreItem->userId === $userId) {
                        $picsItem->scored = "Y";
                        $picsItem->myScore = $scoreItem->score;
                    }
                    $scoreSum += $scoreItem->score;
                    $scoreCount++;
                }
                if ($scoreCount) $picsItem->score = number_format($scoreSum / $scoreCount, 2);
            }
            $picsItem->nickname = $picsItem->user->nickname;
            unset($picsItem["data"], $picsItem["type"]);
            $picsItem->url = $request->domain() . "/api/pics/image/" . $picsItem->picId;
        }
        return jb(200, "数据获取成功", $pics, $picsCount);
    }
    #[ApiDoc\Title("获取随机图片接口")]
    #[ApiDoc\Url("/api/pics/random")]
    #[ApiDoc\Method("GET")]
    #[ApiDoc\Header(name: "Authorization", type: "string", require: false, desc: "Bearer Token")]
    #[ApiDoc\Returned("picId", type: "int", require: true, default: 1, desc: "图片ID")]
    #[ApiDoc\Returned("name", type: "string", require: true, default: "图片名称", desc: "图片名称")]
    #[ApiDoc\Returned("description", type: "string", require: true, default: "图片描述", desc: "图片描述")]
    #[ApiDoc\Returned("userId", type: "int", require: true, default: 1, desc: "上传者ID")]
    #[ApiDoc\Returned("verified", type: "string", require: true, default: "Y", desc: "是否通过审核")]
    #[ApiDoc\Returned("create", type: "datetime", require: true, default: "2024-07-01", desc: "上传时间")]
    #[ApiDoc\Returned("update", type: "datetime", require: true, default: "2024-07-01", desc: "更新时间")]
    #[ApiDoc\Returned("delete", type: "datetime", require: true, default: "2024-07-01", desc: "删除时间")]
    #[ApiDoc\Returned("scored", type: "string", require: true, default: "Y", desc: "是否已打分")]
    #[ApiDoc\Returned("score", type: "float", require: true, default: 5, desc: "评分")]
    #[ApiDoc\Returned("myScore", type: "float", require: false, default: 5, desc: "我的评分")]
    #[ApiDoc\Returned("nickname", type: "string", require: true, default: "用户1", desc: "上传者昵称")]
    #[ApiDoc\Returned("url", type: "string", require: true, default: "http://127.0.0.1/pics/image/1", desc: "图片url")]
    function randomPic(Request$request): Json {
        $auth = loginAuth($request);
        $userId = null;
        if ($auth["status"]) {
            $user = $auth["data"];
            $userId = $user->userId;
        }
        $score = ScoreModel::where("delete")
            ->select();
        $picsCount = PicsModel::where("delete")->count();
        $picsIndex = rand(0, $picsCount - 1);
        $picsItem = PicsModel::where("delete")
            ->limit($picsIndex, 1)
            ->select();
        if (!$picsItem->count()) {
            return jb(401, "没有图片");
        }else {
            $picsItem = $picsItem[0];
            $scoreSum = 0;
            $scoreCount = 0;
            $picsItem->scored = "N";
            foreach ($score as $scoreItem) {
                if ($scoreItem->picId === $picsItem->picId) {
                    if ($scoreItem->userId === $userId) {
                        $picsItem->scored = "Y";
                        $picsItem->myScore = $scoreItem->score;
                    }
                    $scoreSum += $scoreItem->score;
                    $scoreCount++;
                }
            }
            if ($scoreCount) {
                $picsItem->score = number_format($scoreSum / $scoreCount, 2);
            } else {
                $picsItem->score = 0;
            }
            $picsItem->nickname = $picsItem->user->nickname;
            $picsItem->url = $request->domain() . "/api/pics/image/" . $picsItem->picId;
            return jb(200, "数据获取成功", $picsItem, $picsCount, $picsIndex);
        }
    }
    #[ApiDoc\Title("上传图片接口")]
    #[ApiDoc\Url("/api/pics/pic")]
    #[ApiDoc\Method("POST")]
    #[ApiDoc\ContentType("multipart/form-data")]
    #[ApiDoc\Header("Content-Type", type: "string", require: true, desc: "请求类型，使用multipart/form-data")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Param("image", type: "file", require: true, desc: "上传图片")]
    #[ApiDoc\Param("name", type: "string", require: true, desc: "图片名称")]
    #[ApiDoc\Param("description", type: "string", require: false, desc: "图片描述")]
    public function create(Request$request): Json {
        $auth = loginAuth($request);
        if ($auth["status"]) {
            $user = $auth["data"];
            $group = $user->group;
            if ($group) {
                if ($group->uploadPic === "Y") {
                    if ($request->file("image")) {
                        $image = chunk_split(base64_encode(file_get_contents($request->file("image")->getPathname())));
                        $type = $request->file("image")->getMime();
                        $name = $request->post("name");
                        $description = $request->post("description");
                        if ($name) {
                            $pic = new PicsModel;
                            $pic->name = $name;
                            $pic->description = $description;
                            $pic->userId = $user->userId;
                            $pic->data = $image;
                            $pic->type = $type;
                            $pic->verified = "N";
                            $pic->create = now();
                            $pic->update = now();
                            $pic->save();
                            return jb(200, "上传成功");
                        } else {
                            return jb(401, "图片名称不能为空");
                        }
                    } else {
                        return jb(401, "未选择图片");
                    }
                } else {
                    return jb(403, "没有上传图片权限");
                }
            } else {
                return jb(401, "没有权限");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("加载图片数据接口")]
    #[ApiDoc\Url("/api/pics/image/<id>")]
    #[ApiDoc\RouteParam("id", type: "int", require: true, desc: "图片ID")]
    #[ApiDoc\Method("GET")]
    public function read($id): Json|Response {
        $data = PicsModel::where("picId", $id)->findOrEmpty();
        if ($data->isEmpty()) {
            return jb(404, "图片不存在");
        } else {
            $img = base64_decode($data->data);
            $type = $data->type;
            return response($img)->header([
                "Content-Type" => $type
            ]);
        }
    }
    #[ApiDoc\Title("图片评分接口")]
    #[ApiDoc\Url("/api/pics/score")]
    #[ApiDoc\Method("POST")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Param("pic", type: "int", require: true, desc: "图片id")]
    #[ApiDoc\Param("score", type: "float", require: true, desc: "打分值")]
    public function addScore(Request$request): Json {
        $auth = loginAuth($request);
        $pic = $request->post("pic");
        $score = $request->post("score");
        if ($auth["status"]) {
            $user = $auth["data"];
            $group = $user->group;
            if ($group) {
                if ($group->sendScore === "Y") {
                    $_score = new ScoreModel;
                    $_score->picId = $pic;
                    $_score->userId = $user->userId;
                    $_score->score = $score;
                    $_score->create = now();
                    $_score->update = now();
                    $_score->save();
                    return jb(200, "评分成功");
                } else {
                    return jb(403, "没有评分权限");
                }
            } else {
                return jb(401, "没有权限");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("获取指定图片评论列表接口")]
    #[ApiDoc\Url("/api/pics/comment")]
    #[ApiDoc\Method("GET")]
    #[ApiDoc\Param("pic", type: "int", require: true, desc: "图片ID")]
    #[ApiDoc\Returned("commentId", type: "int", require: true, desc: "评论ID")]
    #[ApiDoc\Returned("picId", type: "int", require: true, desc: "图片ID")]
    #[ApiDoc\Returned("userId", type: "int", require: true, desc: "用户ID")]
    #[ApiDoc\Returned("replyId", type: "int", require: true, desc: "回复ID")]
    #[ApiDoc\Returned("comment", type: "string", require: true, desc: "评论内容")]
    #[ApiDoc\Returned("verified", type: "int", require: true, desc: "是否通过审核")]
    #[ApiDoc\Returned("update", type: "datetime", require: true, desc: "更新时间")]
    #[ApiDoc\Returned("create", type: "datetime", require: true, desc: "发送时间")]
    #[ApiDoc\Returned("delete", type: "datetime", require: false, desc: "删除时间")]
    #[ApiDoc\Returned("nickname", type: "string", require: true, desc: "昵称")]
    #[ApiDoc\Returned("avatar", type: "string", require: true, desc: "用户头像地址")]
    function getComment(Request$request): Json {
        $setting = getSetting();
        $pic = $request->get("pic");
        $comments = CommentModel::where("delete")
            ->where("picId", $pic)
            ->select();
        foreach ($comments as &$comment) {
            $user = $comment->user;
            $comment->nickname = $user->nickname;
            $comment->avatar = gravatar($user->email);
            if ($comment->reply > 0) {
                $reply = CommentModel::where("commentId", $comment->reply)->findOrEmpty();
                if (!$reply->isEmpty()) {
                    $replyUser = $reply->user;
                    $comment->replyNickname = $replyUser->nickname;
                }
            }
        }
        return jb(200, "数据获取成功", $comments);
    }
    #[ApiDoc\Title("发送评论接口")]
    #[ApiDoc\Url("/api/pics/comment")]
    #[ApiDoc\Method("POST")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Param("pic", type: "int", require: true, desc: "图片id")]
    #[ApiDoc\Param("comment", type: "string", require: true, desc: "评论内容")]
    #[ApiDoc\Param("reply", type: "int", require: true, desc: "回复消息id")]
    function addComment(Request$request): Json {
        $auth = loginAuth($request);
        $pic = $request->post("pic");
        $comment = $request->post("comment");
        $reply = $request->post("reply");
        if ($auth["status"]) {
            $user = $auth["data"];
            $group = $user->group;
            if ($group) {
                if ($group->sendComment === "Y") {
                    $_comment = new CommentModel;
                    $_comment->userId = $user->userId;
                    $_comment->picId = $pic;
                    $_comment->comment = $comment;
                    $_comment->reply = $reply;
                    $_comment->verified = "N";
                    $_comment->create = now();
                    $_comment->update = now();
                    $_comment->save();
                    return jb(200, "评论发送成功");
                } else {
                    return jb(403, "没有评论权限");
                }
            } else {
                return jb(401, "没有权限");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
}
