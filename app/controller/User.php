<?php
/**
 * @noinspection all
 */
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\BindModel;
use app\model\CommentModel;
use app\model\GroupModel;
use app\model\PicsModel;
use app\model\ScoreModel;
use app\model\UserModel;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use think\facade\Cache;
use think\facade\Db;
use think\Request;
use Firebase\JWT\JWT;
use think\response\Json;
use hg\apidoc\annotation as ApiDoc;

#[ApiDoc\Title("用户接口")]
class User extends BaseController {
    #[ApiDoc\Title("用户登录接口")]
    #[ApiDoc\Desc("通过用户名和密码进行登录，返回Token认证信息")]
    #[ApiDoc\Url("/api/user/login")]
    #[ApiDoc\Method("POST")]
    #[ApiDoc\Param("username", type: "string", require: true, desc: "用户名")]
    #[ApiDoc\Param("password", type: "string", require: true, desc: "密码")]
    function login(Request $request): Json {
        $username = $request->post("username", "");
        $password = $request->post("password", "");
        $secret = $request->post("secret", "");
        if (strlen($secret) === 64) {
            $time = "";
            $md5 = "";
            for ($i = 0; $i < 10; $i++) {
                $time.=substr($secret, $i * 2, 1);
            }
            if (time() - (int)$time > 5) {
                return jb(401, "不安全的请求");
            }
            for ($i = 0; $i < 32; $i++) {
                $md5.=substr($secret, $i * 2 + 1, 1);
            }
            if ($md5 != md5(md5($time) . md5($username) . md5($password))) {
                return jb(401, "不安全的请求");
            }
        } else {
            return jb(401, "安全码无效");
        }
        $captcha = $request->post("captcha");
        $captchaStatus = captchaCheck($captcha);
        if (!$captchaStatus["status"]) {
            return jb(401, $captchaStatus["msg"]);
        }
        $user = UserModel::where("username", $username)
            ->whereOr("email", $username)
            ->findOrEmpty();
        if ($user->isEmpty()) {
            return jb(401, "用户不存在");
        } else {
            if (password_verify($password, $user->password)) {
                if ($user->ban === "Y") {
                    return jb(401, "用户已被封禁：".$user->reason);
                } else {
                    if ($user->email === $username && $user->verified !== "Y") {
                        return jb(401, "邮箱未通过验证，请先使用用户名登录");
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
                        Cache::set($username, $token);
                        return jb(200, "登录成功", null, null, $token);
                    }
                }
            } else {
                return jb(401, "密码错误");
            }
        }
    }

    #[ApiDoc\Title("用户注册接口")]
    #[ApiDoc\Url("/api/user/register")]
    #[ApiDoc\Method("POST")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "邮箱验证信息Bearer Token")]
    #[ApiDoc\Param("username", type: "string", require: true, desc: "用户名")]
    #[ApiDoc\Param("nickname", type: "string", require: true, desc: "昵称")]
    #[ApiDoc\Param("email", type: "string", require: true, desc: "邮箱")]
    #[ApiDoc\Param("code", type: "string", require: true, desc: "验证码")]
    #[ApiDoc\Param("password", type: "string", require: true, desc: "密码")]
    function register(Request $request): Json{
        $username = $request->post("username", "");
        $nickname = $request->post("nickname", "");
        $email = $request->post("email", "");
        $code = $request->post("code", "");
        $password = $request->post("password", "");
        $password = password_hash($password, PASSWORD_BCRYPT);
        $auth = emailAuth($request);
        if ($auth["status"]) {
            $data = $auth["data"];
            if ($data["email"] === $email && (string)$data["code"] === $code) {
                if (UserModel::where("username", $username)->whereOr("email", $email)->findOrEmpty()->isEmpty()) {
                    $defaultGroup = GroupModel::where("default", "Y")->findOrEmpty();
                    if ($defaultGroup->isEmpty()) {
                        return jb(400, "没有默认用户组");
                    } else {
                        $user = new UserModel;
                        $user->username = $username;
                        $user->nickname = $nickname;
                        $user->password = $password;
                        $user->email = $email;
                        $user->verified = "Y";
                        $user->create = now();
                        $user->groupId = $defaultGroup->groupId;
                        $user->ban = "N";
                        $user->reason = "";
                        $user->save();
                        Cache::delete($email);
                        return jb(200, "用户注册成功");
                    }
                } else {
                    return jb(401, "用户名或邮箱已存在");
                }
            } else {
                return jb(401, "验证码不正确");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("重置密码接口")]
    #[ApiDoc\Url("/api/user/register")]
    #[ApiDoc\Method("POST")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "邮箱验证信息Bearer TOken")]
    #[ApiDoc\Param("email", type: "string", require: true, desc: "邮箱")]
    #[ApiDoc\Param("code", type: "string", require: true, desc: "验证码")]
    #[ApiDoc\Param("password", type: "string", require: true, desc: "密码")]
    #[ApiDoc\Param("username", type: "string", require: true, desc: "用户名")]
    function forget(Request $request): Json {
        $email = $request->post("email");
        $code = $request->post("code");
        $password = $request->post("password");
        $password = password_hash($password, PASSWORD_BCRYPT);
        $username = $request->post("username");
        $auth = emailAuth($request);
        if ($auth["status"]) {
            $data = $auth["data"];
            $_email = $data["email"];
            $_code = $data["code"];
            if ($email === $_email && $code === $_code) {
                $user = UserModel::where("username", $username)
                    ->where("email", $email)
                    ->findOrEmpty();
                if ($user->isEmpty()) {
                    return jb(401, "账号不存在");
                } else {
                    Cache::delete($username);
                    Cache::delete($email);
                    $user->password = $password;
                    $user->save();
                    return jb(200, "密码重置成功");
                }
            } else {
                return jb(401, "验证码不正确");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("发送验证码接口")]
    #[ApiDoc\Desc("发送邮箱验证码，返回邮箱认证Token")]
    #[ApiDoc\Url("/api/user/sendCode")]
    #[ApiDoc\Method("POST")]
    #[ApiDoc\Param("action", type: "string", require: true, desc: "验证码操作类型：register/forget/verify")]
    #[ApiDoc\Param("email", type: "string", require: true, desc: "邮箱")]
    function sendCode(Request $request): Json {
        $action = $request->post("action");
        $actionText = match ($action) {
            "register" => "注册IURT meme 2.0账号",
            "forget" => "重置IURT meme 2.0密码",
            "verify" => "验证IURT meme 2.0邮箱",
            default => ""
        };
        $actionShort = match ($action) {
            "register" => "注册",
            "forget" => "重置密码",
            "verify" => "验证邮箱",
            default => ""
        };
        $email = $request->post("email");
        if (empty($email)) {
            return json(["code" => 400, "msg" => "邮箱地址为空"]);
        } else {
            try {
                $mail = new PHPMailer(true);
                $mail->SMTPDebug = SMTP::DEBUG_OFF;
                $mail->isSMTP();
                $mail->Host = env("SMTP_HOST", "");
                $mail->SMTPAuth = true;
                $mail->Username = env("SMTP_USERNAME", "");
                $mail->Password = env("SMTP_PASSWORD", "");
                $mail->SMTPSecure = env("SMTP_SECURE", "");
                $mail->Port = env("SMTP_PORT", 25);
                $mail->setFrom(env("SMTP_USERNAME"), "IURT meme");
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = "{$actionText}验证码";
                $code = rand(111111, 999999);
                $mail->Body = "您正在{$actionText}，这是你的验证码：<mark>{$code}</mark>，验证码在10分钟内有效，请不要将验证码泄露给他人。";
                $mail->AltBody = $actionShort;
                $mail->CharSet = "UTF-8";
                $mail->send();
                $url = $request->domain();
                $payload = [
                    "iss" => $url,
                    "aud" => $url,
                    "kid" => $url,
                    "iat" => time(),
                    "exp" => time() + 600,
                    "code" => $code,
                    "email" => $email
                ];
                $token = JWT::encode($payload, "meme_email_token_key", "HS256");
                Cache::set($email, $token);
                return jb(200, "发送成功，请及时查收", null, null, $token);
            } catch (Exception$e) {
                return jb(500, "发送失败：".$e->getMessage());
            }
        }
    }
    #[ApiDoc\Title("获取用户信息接口")]
    #[ApiDoc\Url("/api/user/info")]
    #[ApiDoc\Method("GET")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Returned("userId", type: "int", require: true, desc: "用户id")]
    #[ApiDoc\Returned("username", type: "string", require: true, desc: "用户名")]
    #[ApiDoc\Returned("nickname", type: "string", require: true, desc: "昵称")]
    #[ApiDoc\Returned("email", type: "string", require: true, desc: "邮箱")]
    #[ApiDoc\Returned("verified", type: "string", require: true, desc: "邮箱是否验证")]
    #[ApiDoc\Returned("create", type: "datetime", require: true, desc: "创建时间")]
    #[ApiDoc\Returned("groupId", type: "int", require: true, desc: "用户组id")]
    #[ApiDoc\Returned("birth", type: "int", require: true, desc: "出生年份")]
    #[ApiDoc\Returned("sex", type: "string", require: true, desc: "性别")]
    #[ApiDoc\Returned("description", type: "string", require: true, desc: "个人简介")]
    #[ApiDoc\Returned("avatar", type: "string", require: true, desc: "头像")]
    #[ApiDoc\Returned("groupName", type: "string", require: true, desc: "用户组名")]
    #[ApiDoc\Returned("admin", type: "string", require: true, desc: "是否管理员")]
    #[ApiDoc\Returned("uploadPic", type: "string", require: true, desc: "允许上传图片")]
    #[ApiDoc\Returned("updatePic", type: "string", require: true, desc: "允许更新图片")]
    #[ApiDoc\Returned("deletePic", type: "string", require: true, desc: "允许删除图片")]
    #[ApiDoc\Returned("restorePic", type: "string", require: true, desc: "允许还原图片")]
    #[ApiDoc\Returned("sendComment", type: "string", require: true, desc: "允许发送评论")]
    #[ApiDoc\Returned("updateComment", type: "string", require: true, desc: "允许更新评论")]
    #[ApiDoc\Returned("deleteComment", type: "string", require: true, desc: "允许删除评论")]
    #[ApiDoc\Returned("restoreComment", type: "string", require: true, desc: "允许还原评论")]
    #[ApiDoc\Returned("sendScore", type: "string", require: true, desc: "允许评分")]
    #[ApiDoc\Returned("updateScore", type: "string", require: true, desc: "允许修改评分")]
    #[ApiDoc\Returned("deleteScore", type: "string", require: true, desc: "允许删除评分")]
    #[ApiDoc\Returned("restoreScore", type: "string", require: true, desc: "运行还原评分")]
    #[ApiDoc\Returned("update", type: "datetime", require: true, desc: "更新时间")]
    function getInfo(Request $request): Json {
        $setting = getSetting();
        $auth = loginAuth($request);
        if ($auth["status"]) {
            $user = $auth["data"];
            unset($user["password"], $user["ban"], $user["reason"]);
            $user->avatar = gravatar($user->email);
            $group = $user->group;
            if ($group) {
                $user = array_merge($user->toArray(), $group->toArray());
            }
            $bind = BindModel::where("userId", $user["userId"])->findOrEmpty();
            if ($bind->isEmpty()) {
                $bind = new BindModel;
                $bind->userId = $user["userId"];
                $bind->save();
            }
            $user = array_merge($user, $bind->toArray());
            return jb(200, "数据获取成功", $user);
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("更新用户信息接口")]
    #[ApiDoc\Url("/api/user/info")]
    #[ApiDoc\Method("PUT")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Param("nickname", type: "string", require: true, desc: "昵称")]
    #[ApiDoc\Param("birth", type: "int", require: true, desc: "出生年份")]
    #[ApiDoc\Param("sex", type: "string", require: true, desc: "性别")]
    #[ApiDoc\Param("description", type: "string", require: true, desc: "个人简介")]
    #[ApiDoc\Param("email", type: "string", require: true, desc: "邮箱")]
    function updateInfo(Request $request): Json {
        $nickname = $request->post("nickname");
        $birth = $request->post("birth");
        $sex = $request->post("sex");
        $description = $request->post("description");
        $email = $request->post("email");
        $auth = loginAuth($request);
        if ($auth["status"]) {
            $user = $auth["data"];
            $_email = $user->email;
            if ($email === $_email) $email = null;
            if ($email && !UserModel::where("email", $email)->findOrEmpty()->isEmpty()) {
                return json(["code" => 401, "msg" => "邮箱已存在"]);
            }
            if ($nickname) $user->nickname = $nickname;
            if ($birth) $user->birth = $birth;
            if ($sex) $user->sex = $sex;
            if ($description) $user->description = $description;
            if ($email) {
                $user->email = $email;
                $user->verified = "N";
            }
            $user->save();
            return jb(200, "用户信息更新成功");
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("退出登录接口")]
    #[ApiDoc\Url("/api/user/logout")]
    #[ApiDoc\Method("POST")]
    #[ApiDoc\Header("Authorization", type: "string", require: false, desc: "Bearer Token")]
    function logout(Request $request):Json {
        $auth = loginAuth($request);
        if ($auth["status"]) {
            Cache::delete($auth["data"]->username);
        }
        return jb(200, "已退出登录");
    }
    #[ApiDoc\Title("修改密码接口")]
    #[ApiDoc\Url("/api/user/password")]
    #[ApiDoc\Method("PUT")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Param("newPassword", type: "string", require: true, desc: "新密码")]
    #[ApiDoc\Param("oldPassword", type: "string", require: true, desc: "旧密码")]
    function changePassword(Request $request): Json {
        $newPassword = $request->post("newPassword");
        $oldPassword = $request->post("oldPassword");
        $auth = loginAuth($request);
        if ($auth["status"]) {
            $user = $auth["data"];
            if (password_verify($oldPassword, $user->password)) {
                $user->password = password_hash($newPassword, PASSWORD_BCRYPT);
                $user->save();
                Cache::delete($user->username);
                return jb(200, "密码修改成功");
            } else {
                return jb(401, "原密码不正确");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("验证邮箱接口")]
    #[ApiDoc\Url("/api/user/verify")]
    #[ApiDoc\Method("POST")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "邮箱认证Bearer Token")]
    #[ApiDoc\Param("email", type: "string", require: true, desc: "邮箱")]
    #[ApiDoc\Param("code", type: "int", require: true, desc: "验证码")]
    function verify(Request $request): Json {
        $code = $request->post("code");
        $auth = emailAuth($request);
        if ($auth["status"]) {
            $data = $auth["data"];
            $_code = $data["code"];
            $_email = $data["email"];
            if ($code === $_code) {
                $user = UserModel::where("email", $_email)->findOrEmpty();
                if ($user->isEmpty()) {
                    return jb(401, "用户不存在");
                } else {
                    $user->verified = "Y";
                    Cache::delete($_email);
                    return jb(200, "验证成功");
                }
            } else {
                return jb(401, "验证码错误");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("获取当前用户图片列表接口")]
    #[ApiDoc\Url("/api/user/pics")]
    #[ApiDoc\Method("GET")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Param("pageSize", type: "int", require: true, desc: "分页大小")]
    #[ApiDoc\Param("pageNum", type: "int", require: true, desc: "分页页码")]
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
    function getPicList(Request $request): Json {
        $auth = loginAuth($request);
        if ($auth["status"]) {
            $user = $auth["data"];
            $pageSize = (int)$request->get("pageSize", 10);
            $pageNum = (int)$request->get("pageNum", 1);
            $pic = PicsModel::where("userId", $user->userId)
                ->limit(($pageNum - 1) * $pageSize, $pageSize)
                ->select();
            $picCount = PicsModel::where("userId", $user->userId)->count();
            $score = ScoreModel::where("delete")->select();
            foreach ($pic as &$picsItem) {
                $picsItem->score = 0;
                $scoreSum = 0;
                $scoreCount = 0;
                $picsItem->url = $request->domain() . "/api/pics/image/" . $picsItem->picId;
                foreach ($score as $scoreItem) {
                    if ($scoreItem->picId === $picsItem->picId) {
                        $scoreSum += $scoreItem->score;
                        $scoreCount++;
                    }
                }
                if ($scoreCount) $picsItem->score = $scoreSum / $scoreCount;
                $picsItem->create = explode(" ", $picsItem->create)[0];
                $picsItem->update = explode(" ", $picsItem->update)[0];
                unset($picsItem["data"], $picsItem["user"], $picsItem["type"]);
            }
            return jb(200, "数据获取成功", $pic, $picCount);
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("删除图片接口")]
    #[ApiDoc\Url("/api/user/pics")]
    #[ApiDoc\Method("DELETE")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Query("pic", type: "int", require: true, desc: "图片id")]
    function deletePic(Request $request): Json {
        $auth = loginAuth($request);
        $picId = $request->get("pic", "");
        if ($auth["status"]) {
            $user = $auth["data"];
            $group = $user->group;
            if ($group) {
                if ($group->deletePic === "Y") {
                    $pic = PicsModel::where("picId", $picId)
                        ->findOrEmpty();
                    if ($pic->isEmpty()) {
                        return jb(404, "找不到指定的图片");
                    } else {
                        if ($pic->userId === $user->userId) {
                            $pic->delete = now();
                            $pic->save();
                            return jb(200, "图片删除成功");
                        } else {
                            return jb(403, "没有权限操作该图片");
                        }
                    }
                } else {
                    return jb(403, "没有删除权限");
                }
            } else {
                return jb(401, "没有权限");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("还原图片接口")]
    #[ApiDoc\Url("/api/user/pics")]
    #[ApiDoc\Method("PATCH")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Param("pic", type: "int", require: true, desc: "图片id")]
    function restorePic(Request $request): Json {
        $auth = loginAuth($request);
        $picId = $request->post("pic", "");
        if ($auth["status"]) {
            $user = $auth["data"];
            $group = $user->group;
            if ($group) {
                if ($group->restorePic === "Y") {
                    $pic = PicsModel::where("picId", $picId)
                        ->findOrEmpty();
                    if ($pic->isEmpty()) {
                        return jb(404, "找不到指定的图片");
                    } else {
                        if ($pic->userId === $user->userId) {
                            $pic->delete = null;
                            $pic->save();
                            return jb(200, "图片还原成功");
                        } else {
                            return jb(403, "没有权限操作该图片");
                        }
                    }
                } else {
                    return jb(403, "没有还原权限");
                }
            } else {
                return jb(401, "没有权限");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("更新图片接口")]
    #[ApiDoc\Url("/api/user/pics")]
    #[ApiDoc\Method("PUT")]
    #[ApiDoc\ContentType("multipart/form-data")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Header("Content-Type", type: "string", require: true, desc: "multipart/form-data")]
    #[ApiDoc\Param("pic", type: "int", require: true, desc: "图片id")]
    #[ApiDoc\Param("name", type: "string", require: true, desc: "图片id")]
    #[ApiDoc\Param("description", type: "string", require: true, desc: "图片id")]
    #[ApiDoc\Param("image", type: "file", require: true, desc: "图片id")]
    function updatePic(Request $request): Json {
        $auth = loginAuth($request);
        $picId = $request->post("pic");
        $name = $request->post("name");
        $description = $request->post("description");
        $image = $request->file("image");
        if ($image) {
            chunk_split(base64_encode(file_get_contents($request->file("image")->getPathname())));
        }
        if ($auth["status"]) {
            $user = $auth["data"];
            $group = $user->group;
            if ($group) {
                if ($group->updatePic === "Y") {
                    $pic = PicsModel::where("picId", $picId)->findOrEmpty();
                    if ($pic) {
                        if ($pic->userId === $user->userId) {
                            if ($name) $pic->name = $name;
                            if ($description) $pic->description = $description;
                            if ($image) $pic->data = $image;
                            $pic->save();
                            return jb(200, "图片更新成功");
                        } else {
                            return jb(403, "没有权限操作该图片");
                        }
                    } else {
                        return jb(404, "找不到指定的图片");
                    }
                } else {
                    return jb(403, "没有更新图片权限");
                }
            } else {
                return jb(401, "没有权限");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("获取当前用户评分列表接口")]
    #[ApiDoc\Url("/api/user/scores")]
    #[ApiDoc\Method("GET")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Param("pageSize", type: "int", require: true, desc: "分页大小")]
    #[ApiDoc\Param("pageNum", type: "int", require: true, desc: "分页页码")]
    #[ApiDoc\Returned("scoreId", type: "int", require: true, desc: "评分id")]
    #[ApiDoc\Returned("picId", type: "int", require: true, desc: "图片id")]
    #[ApiDoc\Returned("userId", type: "int", require: true, desc: "用户id")]
    #[ApiDoc\Returned("score", type: "float", require: true, desc: "评分id")]
    #[ApiDoc\Returned("create", type: "datetime", require: true, desc: "创建时间")]
    #[ApiDoc\Returned("update", type: "datetime", require: true, desc: "更新时间")]
    #[ApiDoc\Returned("delete", type: "datetime", require: true, desc: "删除时间")]
    #[ApiDoc\Returned("url", type: "int", require: true, desc: "图片链接")]
    #[ApiDoc\Returned("name", type: "int", require: false, desc: "图片名称")]
    function getScore(Request $request): Json {
        $auth = loginAuth($request);
        $pageSize = (int)$request->get("pageSize", 20);
        $pageNum = (int)$request->get("pageNum", 1);
        if ($auth["status"]) {
            $user = $auth["data"];
            $score = ScoreModel::where("userId", $user->userId)
                ->limit(($pageNum - 1) * $pageSize, $pageSize)
                ->select();
            $scoreCount = ScoreModel::where("userId", $user->userId)->count();
            foreach ($score as &$scoreItem) {
                $scoreItem->url = $request->domain()."/api/pics/image/".$scoreItem->picId;
                $pic = $scoreItem->pic;
                if ($pic) $scoreItem["name"] = $pic->name;
            }
            return jb(200, "数据获取成功", $score, $scoreCount);
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("更新评分接口")]
    #[ApiDoc\Url("/api/user/scores")]
    #[ApiDoc\Method("PUT")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Param("id", type: "int", require: true, desc: "评分id")]
    #[ApiDoc\Param("score", type: "float", require: true, desc: "评分值")]
    function updateScore(Request $request): Json {
        $auth = loginAuth($request);
        $id = $request->post("id");
        $score = $request->post("score");
        if ($auth["status"]) {
            $user = $auth["data"];
            $group = $user->group;
            if ($group) {
                if ($group->updateScore === "Y") {
                    $scoreItem = ScoreModel::where("scoreId", $id)->findOrEmpty();
                    if ($scoreItem->isEmpty()) {
                        return jb(404, "找不到指定的评分");
                    } else {
                        if ($scoreItem->userId === $user->userId) {
                            $scoreItem->score = $score;
                            $scoreItem->save();
                            return jb(200, "评分修改成功");
                        } else {
                            return jb(403, "没有权限操作这条评分");
                        }
                    }
                } else {
                    return jb(403, "没有修改评分权限");
                }
            } else {
                return jb(401, "没有权限");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("删除评分接口")]
    #[ApiDoc\Url("/api/user/scores")]
    #[ApiDoc\Method("DELETE")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Query("id", type: "int", require: true, desc: "评分id")]
    function deleteScore(Request $request): Json{
        $auth = loginAuth($request);
        $id = $request->get("id");
        if ($auth["status"]) {
            $user = $auth["data"];
            $group = $user->group;
            if ($group) {
                if ($group->deleteScore === "Y") {
                    $scoreItem = ScoreModel::where("scoreId", $id)->findOrEmpty();
                    if ($scoreItem->isEmpty()) {
                        return jb(404, "找不到指定的评分");
                    } else {
                        if ($scoreItem->userId === $user->userId) {
                            $scoreItem->delete = now();
                            $scoreItem->save();
                            return jb(200, "评分删除成功");
                        } else {
                            return jb(403, "没有权限操作这条评分");
                        }
                    }
                } else {
                    return jb(403, "没有删除评分权限");
                }
            } else {
                return jb(401, "没有权限");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("还原评分接口")]
    #[ApiDoc\Url("/api/user/scores")]
    #[ApiDoc\Method("PATCH")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Param("id", type: "int", require: true, desc: "评分id")]
    function restoreScore(Request $request): Json {
        $auth = loginAuth($request);
        $id = $request->post("id");
        if ($auth["status"]) {
            $user = $auth["data"];
            $group = $user->group;
            if ($group) {
                if ($group->restoreScore === "Y") {
                    $scoreItem = ScoreModel::where("scoreId", $id)->findOrEmpty();
                    if ($scoreItem->isEmpty()) {
                        return jb(404, "找不到指定的评分");
                    } else {
                        if ($scoreItem->userId === $user->userId) {
                            $scoreItem->delete = null;
                            $scoreItem->save();
                            return jb(200, "评分还原成功");
                        } else {
                            return jb(403, "没有权限操作这条评分");
                        }
                    }
                } else {
                    return jb(403, "没有还原评分权限");
                }
            } else {
                return jb(401, "没有权限");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("获取当前用户评论列表接口")]
    #[ApiDoc\Url("/api/user/comment")]
    #[ApiDoc\Method("GET")]
    #[ApiDoc\Param("pageSize", type: "int", require: false, desc: "分页大小")]
    #[ApiDoc\Param("pageNum", type: "int", require: false, desc: "分页页码")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
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
        $auth = loginAuth($request);
        $pageSize = (int)$request->get("pageSize", 20);
        $pageNum = (int)$request->get("pageNum", 1);
        if ($auth["status"]) {
            $user = $auth["data"];
            $comment = CommentModel::where("userId", $user->userId)->limit(($pageNum - 1) * $pageSize, $pageSize)->select();
            $count = CommentModel::where("userId", $user->userId)->count();
            foreach ($comment as &$item) {
                $pic = $item->pic;
                if ($pic) $item->name = $pic->name;
                $item->url = $request->domain()."/api/pics/image/".$pic->picId;
            }
            return jb(200, "数据获取成功", $comment, $count);
        }else{
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("更新评论接口")]
    #[ApiDoc\Url("/api/user/comment")]
    #[ApiDoc\Method("PUT")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Param("commentId", type: "int", require: true, desc: "评论id")]
    #[ApiDoc\Param("comment", type: "string", require: true, desc: "评论内容")]
    function updateComment (Request$request): Json {
        $auth = loginAuth($request);
        $commentId = $request->post("commentId");
        $comment = $request->post("comment");
        if ($auth["status"]) {
            $user = $auth["data"];
            $group = $user->group;
            if ($group) {
                if ($group->updateComment === "Y") {
                    $_comment = CommentModel::where("commentId", $commentId)->findOrEmpty();
                    if ($_comment->isEmpty()) {
                        return jb(404, "找不到指定的评论");
                    } else {
                        if ($_comment->userId === $user->userId) {
                            $_comment->comment = $comment;
                            $_comment->verified = "N";
                            $_comment->save();
                            return jb(200, "评论更新成功");
                        } else {
                            return jb(403, "没有权限操作这条评论");
                        }
                    }
                } else {
                    return jb(403, "没有更新评论权限");
                }
            } else {
                return jb(401, "没有权限");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("删除评论接口")]
    #[ApiDoc\Url("/api/user/comment")]
    #[ApiDoc\Method("DELETE")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Query("commentId", type: "int", require: true, desc: "评论id")]
    function deleteComment(Request$request): Json {
        $auth = loginAuth($request);
        $commentId = $request->get("commentId");
        if ($auth["status"]) {
            $user = $auth["data"];
            $group = $user->group;
            if ($group) {
                if ($group->deleteComment === "Y") {
                    $comment = CommentModel::where("commentId", $commentId)->findOrEmpty();
                    if ($comment->isEmpty()) {
                        return jb(404, "找不到指定的评论");
                    } else {
                        if ($comment->userId === $user->userId) {
                            $comment->delete = now();
                            $comment->save();
                            return jb(200, "评论删除成功");
                        } else {
                            return jb(403, "没有权限操作这条评论");
                        }
                    }
                } else {
                    return jb(403, "没有删除评论权限");
                }
            } else {
                return jb(401, "没有权限");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    #[ApiDoc\Title("还原评论接口")]
    #[ApiDoc\Url("/api/user/comment")]
    #[ApiDoc\Method("PATCH")]
    #[ApiDoc\Header("Authorization", type: "string", require: true, desc: "Bearer Token")]
    #[ApiDoc\Param("commentId", type: "int", require: true, desc: "评论id")]
    function restoreComment(Request$request): Json {
        $auth = loginAuth($request);
        $commentId = $request->post("commentId");
        if ($auth["status"]) {
            $user = $auth["data"];
            $group = $user->group;
            if ($group) {
                if ($group->restoreComment === "Y") {
                    $comment = CommentModel::where("commentId", $commentId)->findOrEmpty();
                    if ($comment->isEmpty()) {
                        return jb(404, "找不到指定的评论");
                    } else {
                        if ($comment->userId === $user->userId) {
                            $comment->delete = null;
                            $comment->save();
                            return jb(200, "评论还原成功");
                        } else {
                            return jb(403, "没有权限操作这条评论");
                        }
                    }
                } else {
                    return jb(403, "没有删除评论权限");
                }
            } else {
                return jb(401, "没有权限");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
}
