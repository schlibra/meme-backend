<?php

namespace app\controller;

use app\BaseController;
use app\model\BasicModel;
use app\model\CommentModel;
use app\model\GroupModel;
use app\model\PicsModel;
use app\model\ScoreModel;
use app\model\SecurityModel;
use app\model\ThirdPartyModel;
use app\model\UserModel;
use app\Request;
use Firebase\JWT\JWT;
use think\facade\Cache;
use think\facade\Db;
use think\response\Json;

class Admin extends BaseController
{
    function setBasic(Request$request): Json {
        $siteName = $request->post("siteName");
        $siteLogo = $request->post("siteLogo");
        $language = $request->post("language");
        $enableHomeTyping = $request->post("enableHomeTyping");
        $enableGravatarCDN = $request->post("enableGravatarCDN");
        $gravatarCDNAddress = $request->post("gravatarCDNAddress");
        $enablePicCompress = $request->post("enablePicCompress");
        $picCompressType = $request->post("picCompressType");
        $enablePictureVerify = $request->post("enablePictureVerify");
        $enableCommentVerify = $request->post("enableCommentVerify");
        $enableCaptcha = $request->post("enableCaptcha");
        $enableUserLog = $request->post("enableUserLog");
        $enableAdminLog = $request->post("enableAdminLog");
        $auth = loginAuth($request, true);
        if ($auth["status"]) {
            $basic = BasicModel::findOrEmpty(1);
            if ($basic->isEmpty()) {
                return jb(400, l("admin.settings.data_not_exist"));
            } else {
                if ($siteName) $basic->siteName = $siteName;
                if ($siteLogo) $basic->siteLogo = $siteLogo;
                if ($language) $basic->language = $language;
                if ($enableHomeTyping) $basic->enableHomeTyping = $enableHomeTyping;
                if ($enableGravatarCDN) $basic->enableGravatarCDN = $enableGravatarCDN;
                if ($gravatarCDNAddress) $basic->gravatarCDNAddress = $gravatarCDNAddress;
                if ($enablePicCompress) $basic->enablePicCompress = $enablePicCompress;
                if ($picCompressType) $basic->picCompressType = $picCompressType;
                if ($enablePictureVerify) $basic->enablePictureVerify = $enablePictureVerify;
                if ($enableCommentVerify) $basic->enableCommentVerify = $enableCommentVerify;
                if ($enableCaptcha) $basic->enableCaptcha = $enableCaptcha;
                if ($enableUserLog) $basic->enableUserLog = $enableUserLog;
                if ($enableAdminLog) $basic->enableAdminLog = $enableAdminLog;
                $basic->save();
                return jb(200, "设置更新成功");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function setSecurity(Request$request): Json {
        $enableEmail = $request->post("enableEmail");
        $smtpHost = $request->post("smtpHost");
        $smtpPort = $request->post("smtpPort");
        $smtpUsername = $request->post("smtpUsername");
        $smtpPassword = $request->post("smtpPassword");
        $smtpEncrypt = $request->post("smtpEncrypt");
        $auth = loginAuth($request, true);
        if ($auth["status"]) {
            $security = SecurityModel::findOrEmpty(1);
            if ($security->isEmpty()) {
                return jb(400, l("admin.settings.data_not_exist"));
            } else {
                if ($enableEmail) $security->enableEmail = $enableEmail;
                if ($smtpHost) $security->smtpHost = $smtpHost;
                if ($smtpPort) $security->smtpPort = $smtpPort;
                if ($smtpUsername) $security->smtpUsername = $smtpUsername;
                if ($smtpPassword) $security->smtpPassword = $smtpPassword;
                if ($smtpEncrypt) $security->smtpEncrypt = $smtpEncrypt;
                $security->save();
                return jb(200, "设置更新成功");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function setThirdParty(Request$request): Json {
        $enableSckur = $request->post("enableSckur");
        $sckurApiKey = $request->post("sckurApiKey");
        $sckurClientId = $request->post("sckurClientId");
        $enableGitee = $request->post("enableGitee");
        $giteeClientId = $request->post("giteeClientId");
        $giteeClientSecret = $request->post("giteeClientSecret");
        $enableGithub = $request->post("enableGithub");
        $githubClientId = $request->post("githubClientId");
        $githubClientSecret = $request->post("githubClientSecret");
        $enableGitlab = $request->post("enableGitlab");
        $gitlabClientId = $request->post("gitlabClientId");
        $gitlabClientSecret = $request->post("gitlabClientSecret");
        $enableMicrosoft = $request->post("enableMicrosoft");
        $microsoftClientId = $request->post("microsoftClientId");
        $microsoftClientSecret = $request->post("microsoftClientSecret");
        $auth = loginAuth($request, true);
        if ($auth["status"]) {
            $thirdParty = ThirdPartyModel::findOrEmpty(1);
            if ($thirdParty->isEmpty()) {
                return jb(400, l("admin.settings.data_not_exist"));
            } else {
                if ($enableSckur) $thirdParty->enableSckur = $enableSckur;
                if ($sckurClientId) $thirdParty->sckurClientId = $sckurClientId;
                if ($sckurApiKey) $thirdParty->sckurApiKey = $sckurApiKey;
                if ($enableGitee) $thirdParty->enableGitee = $enableGitee;
                if ($giteeClientId) $thirdParty->giteeClientId = $giteeClientId;
                if ($giteeClientSecret) $thirdParty->giteeClientSecret = $giteeClientSecret;
                if ($enableGithub) $thirdParty->enableGithub = $enableGithub;
                if ($githubClientId) $thirdParty->githubClientId = $githubClientId;
                if ($githubClientSecret) $thirdParty->githubClientSecret = $githubClientSecret;
                if ($enableGitlab) $thirdParty->enableGitlab = $enableGitlab;
                if ($gitlabClientId) $thirdParty->gitlabClientId = $gitlabClientId;
                if ($gitlabClientSecret) $thirdParty->gitlabClientSecret = $gitlabClientSecret;
                if ($enableMicrosoft) $thirdParty->enableMicrosoft = $enableMicrosoft;
                if ($microsoftClientId) $thirdParty->microsoftClientId = $microsoftClientId;
                if ($microsoftClientSecret) $thirdParty->microsoftClientSecret = $microsoftClientSecret;
                $thirdParty->save();
                return jb(200, "设置更新成功");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function getGroup(Request$request): Json {
        $auth = loginAuth($request, true);
        if ($auth["status"]) {
            $group = GroupModel::select();
            foreach ($group as $item) {
                $userCount = UserModel::where("groupId", $item->groupId)->count();
                $item->userCount = $userCount;
                $item->create = explode(" ", $item->create)[0];
            }
            if ($group->isEmpty()) {
                return jb(400, l("common.data_error"));
            } else {
                return jb(200, l("common.get_data_success"), $group->toArray());
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function createGroup(Request$request):Json {
        $auth = loginAuth($request, true);
        $groupName = $request->post("groupName", "");
        $admin = $request->post("admin", "N");
        $uploadPic = $request->post("uploadPic", "N");
        $updatePic = $request->post("updatePic", "N");
        $deletePic = $request->post("deletePic", "N");
        $restorePic = $request->post("restorePic", "N");
        $sendComment = $request->post("sendComment", "N");
        $updateComment = $request->post("updateComment", "N");
        $deleteComment = $request->post("deleteComment", "N");
        $restoreComment = $request->post("restoreComment", "N");
        $sendScore = $request->post("sendScore", "N");
        $updateScore = $request->post("updateScore", "N");
        $deleteScore = $request->post("deleteScore", "N");
        $restoreScore = $request->post("restoreScore", "N");
        if ($auth["status"]) {
            $group = new GroupModel;
            $group->groupName = $groupName;
            $group->default = "N";
            $group->admin = $admin;
            $group->uploadPic = $uploadPic;
            $group->updatePic = $updatePic;
            $group->deletePic = $deletePic;
            $group->restorePic = $restorePic;
            $group->sendComment = $sendComment;
            $group->updateComment = $updateComment;
            $group->deleteComment = $deleteComment;
            $group->restoreComment = $restoreComment;
            $group->sendScore = $sendScore;
            $group->updateScore = $updateScore;
            $group->deleteScore = $deleteScore;
            $group->restoreScore = $restoreScore;
            $group->create = now();
            $group->update = now();
            $group->save();
            return jb(200, "用户组创建成功");
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function deleteGroup(Request$request): Json{
        $auth = loginAuth($request, true);
        $groupId = $request->get("groupId");
        if ($auth["status"]) {
            $group = GroupModel::where("groupId", $groupId)->findOrEmpty();
            if ($group->isEmpty()) {
                return jb(404, l("admin.group.delete.group_not_exist"));
            } else {
                if ($group->default === "Y") {
                    return jb(403, l("admin.group.delete.cant_delete_default"));
                } else {
                    $userCount = UserModel::where("groupId", $groupId)->count();
                    if ($userCount) {
                        return jb(400, l("admin.group.delete.group_not_empty"));
                    } else {
                        $group->delete();
                        return jb(200, l("admin.group.delete.success"));
                    }
                }
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function updateGroup(Request$request):Json {
        $auth = loginAuth($request, true);
        $groupId = $request->post("groupId");
        $groupName = $request->post("groupName");
        $admin = $request->post("admin");
        $default = $request->post("default");
        $uploadPic = $request->post("uploadPic");
        $updatePic = $request->post("updatePic");
        $deletePic = $request->post("deletePic");
        $restorePic = $request->post("restorePic");
        $sendComment = $request->post("sendComment");
        $updateComment = $request->post("updateComment");
        $deleteComment = $request->post("deleteComment");
        $restoreComment = $request->post("restoreComment");
        $sendScore = $request->post("sendScore");
        $updateScore = $request->post("updateScore");
        $deleteScore = $request->post("deleteScore");
        $restoreScore = $request->post("restoreScore");
        if ($auth["status"]) {
            if ($default) {
                $defaultGroup = GroupModel::where("groupId", $default)->findOrEmpty();
                if ($defaultGroup->isEmpty()) {
                    return jb(404, "指定用户组不存在");
                } else {
                    GroupModel::where("default", "Y")->update(["default" => "N"]);
                    GroupModel::where("groupId", $default)->update(["default" => "Y"]);
                    return jb(200, "默认用户组更新成功");
                }
            } else {
                $group = GroupModel::where("groupId", $groupId)->findOrEmpty();
                if ($group->isEmpty()) {
                    return jb(404, "指定的用户组不存在");
                } else {
                    if ($groupName) $group->groupName = $groupName;
                    if ($admin) $group->admin = $admin;
                    if ($uploadPic) $group->uploadPic = $uploadPic;
                    if ($updatePic) $group->updatePic = $updatePic;
                    if ($deletePic) $group->deletePic = $deletePic;
                    if ($restorePic) $group->restorePic = $restorePic;
                    if ($sendComment) $group->sendComment = $sendComment;
                    if ($updateComment) $group->updateComment = $updateComment;
                    if ($deleteComment) $group->deleteComment = $deleteComment;
                    if ($restoreComment) $group->restoreComment = $restoreComment;
                    if ($sendScore) $group->sendScore = $sendScore;
                    if ($updateScore) $group->updateScore = $updateScore;
                    if ($deleteScore) $group->deleteScore = $deleteScore;
                    if ($restoreScore) $group->restoreScore = $restoreScore;
                    $group->update = now();
                    $group->save();
                    return jb(200, "用户组更新成功");
                }
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function getUser(Request$request): Json {
        $auth = loginAuth($request, true);
        if ($auth["status"]) {
            $users = UserModel::select();
            if ($users->isEmpty()){
                return jb(400, "数据异常");
            } else {
                $userList = [];
                foreach ($users as $_user) {
                    $item = array_merge($_user->toArray(), $_user->group->toArray());
                    $item["create"] = explode(" ", $_user->create)[0];
                    unset($item["password"]);
                    $userList[] = $item;
                }
                return jb(200, "数据获取成功", $userList);
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function updateUser(Request$request): Json {
        $auth = loginAuth($request, true);
        $userId = $request->post("userId");
        $username = $request->post("username");
        $password = $request->post("password");
        $nickname = $request->post("nickname");
        $email = $request->post("email");
        $verified = $request->post("verified");
        $groupId = $request->post("groupId");
        $ban = $request->post("ban");
        $reason = $request->post("reason");
        $birth = $request->post("birth");
        $sex = $request->post("sex");
        $description = $request->post("description");
        if ($auth["status"]) {
            $user = UserModel::where(["userId" => $userId, "username" => $username])->findOrEmpty();
            if ($user->isEmpty()) {
                return jb(404, "指定用户不存在");
            } else {
                if ($password) $user->password = password_hash($password, PASSWORD_BCRYPT);
                if ($nickname) $user->nickname = $nickname;
                if ($email) $user->email = $email;
                if ($verified) $user->verified = $verified;
                if ($groupId) $user->groupId = $groupId;
                if ($ban) $user->ban = $ban;
                if ($reason) $user->reason = $reason;
                if ($birth) $user->birth = $birth;
                if ($sex) $user->sex = $sex;
                if ($description) $user->description = $description;
                $user->save();
                return jb(msg: "用户更新成功");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }

    function createUser(Request$request): Json {
        $auth = loginAuth($request, true);
        $username = $request->post("username");
        $password = $request->post("password");
        $nickname = $request->post("nickname");
        $email = $request->post("email");
        $verified = $request->post("verified");
        $groupId = $request->post("groupId");
        $ban = $request->post("ban");
        $reason = $request->post("reason");
        $birth = $request->post("birth");
        $sex = $request->post("sex");
        $description = $request->post("description");
        if ($auth["status"]) {
            $user = new UserModel;
            $user->username = $username;
            $user->password = $password;
            $user->nickname = $nickname;
            $user->email = $email;
            $user->verified = $verified;
            $user->groupId = $groupId;
            $user->ban = $ban;
            $user->reason = $reason;
            $user->birth = $birth;
            $user->sex = $sex;
            $user->description = $description;
            $user->create = now();
            $user->save();
            return jb(msg: "用户创建成功");
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function deleteUser(Request$request): Json {
        $auth = loginAuth($request, true);
        $userId = $request->get("userId");
        if ($auth["status"]) {
            $user = UserModel::where("userId", $userId)->findOrEmpty();
            if ($user->isEmpty()) {
                return jb(404, "找不到指定的用户");
            } else {
                $user->delete();
                return jb(msg: "用户删除成功");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function switchUser(Request$request): Json {
        $auth = loginAuth($request, true);
        $userId = $request->post("userId");
        $username = $request->post("username");
        if ($auth["status"]) {
            $user = UserModel::where("userId", $userId)->findOrEmpty();
            if ($user->isEmpty()) {
                return jb(404, "指定用户不存在");
            } else {
                if ($user->username === $username) {
                    if ($user->ban === "Y") {
                        return jb(403, "目标用户已封禁，请解封后重试");
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
                        $token = JWT::encode($payload, "meme_login_token_key", "HS256");
                        Cache::set($username, $token);
                        return jb(200, "登录成功", null, null, $token);
                    }
                } else {
                    return jb(403, "目标用户名不匹配");
                }
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function getPicture(Request$request): Json {
        $auth = loginAuth($request, true);
        if ($auth["status"]) {
            $pics = PicsModel::select();
            foreach ($pics as &$pic) {
                unset($pic->data);
                $pic->url = $request->domain() . "/api/pics/image/" . $pic->picId;
                $pic->nickname = $pic->user->nickname;
                $commentCount = CommentModel::where("picId", $pic->picId)->count();
                $pic->commentCount = $commentCount;
                $score = ScoreModel::where("picId", $pic->picId)->select();
                $score_count = 0;
                $score_sum = 0;
                $pic->score = 0;
                foreach ($score as $scoreItem) {
                    $score_count++;
                    $score_sum+=$scoreItem->score;
                }
                if ($score_count) $pic->score = $score_sum / $score_count;
            }
            return jb(200, "数据获取成功", $pics->toArray());
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    function getBackup(Request$request):Json {
        $auth = loginAuth($request, true);
        if ($auth["status"]) {
            $data = [
                "group" => GroupModel::select()->toArray(),
                "user" => UserModel::select()->toArray(),
                "pics" => PicsModel::select()->toArray(),
                "score" => ScoreModel::select()->toArray(),
                "comment" => CommentModel::select()->toArray(),
                "basic" => BasicModel::select()->toArray(),
                "security" => SecurityModel::select()->toArray(),
                "thirdParty" => ThirdPartyModel::select()->toArray(),
            ];
            $data = json_encode($data);
            $data = gzcompress($data, 9);
            $data = base64_encode($data);
            $data = memeBackupHeader . chunk_split($data) . memeBackupFooter;
            return jb(200, "备份数据获取成功", $data);
        } else {
            return jb(401, $auth["msg"]);
        }
    }
    public function restoreBackup(Request$request): Json {
        $result = [
            "success" => 0,
            "failed" => 0,
            "skipped" => 0,
        ];
        $auth = loginAuth($request, true);
        $file = $request->file("file");
        if ($auth["status"]) {
            if ($file) {
                $data = file_get_contents($file->getPathname());
                if (str_starts_with($data, memeBackupHeader) && str_ends_with($data, memeBackupFooter)) {
                    $data = str_replace([memeBackupHeader, memeBackupFooter], ["", ""], $data);
                    $data = base64_decode($data);
                    $data = gzuncompress($data);
                    $data = json_decode($data, true);
                    if ($data) {
                        $reset = $this->resetData($request)->getData();
                        if ($reset["code"] !== 200) {
                            return jb($reset["msg"]);
                        }
                        foreach (["group", "user", "pics", "score", "comment"] as $name) {
                            if ($data[$name]) {
                                foreach ($data[$name] as $item) {
                                    $model = match ($name) {
                                        "pics" => new PicsModel,
                                        "user" => new UserModel,
                                        "group" => new GroupModel,
                                        "score" => new ScoreModel,
                                        "comment" => new CommentModel,
                                        default => null,
                                    };
                                    foreach (array_keys($item) as $key) {
                                        $model[$key] = $item[$key];
                                    }
                                    $result[$model->save()?"success":"failed"]++;
                                }
                            } else {
                                $result["skipped"]++;
                            }
                        }
                        foreach (["basic", "security", "thirdParty"] as $name) {
                            if ($data[$name]) {
                                foreach ($data[$name] as $item) {
                                    $model = match ($name) {
                                        "basic" => BasicModel::find(1),
                                        "security" => SecurityModel::find(1),
                                        "thirdParty" => ThirdPartyModel::find(1),
                                        default => null
                                    };
                                    foreach (array_keys($item) as $key) {
                                        $model[$key] = $item[$key];
                                    }
                                    $result[$model->save()?"success":"failed"]++;
                                }
                            }
                        }
                        return jb(200, "数据恢复完成", $result);
                    } else {
                        return jb(400, "数据不完整");
                    }
                } else {
                    return jb(400, "备份文件格式不正确");
                }
            } else {
                return jb(400, "没有选择文件");
            }
        } else {
            return jb(401, $auth["msg"]);
        }
    }

    function resetData(Request$request): Json {
        $auth = loginAuth($request, true);
        if ($auth["status"]) {
            ScoreModel::select()->delete();
            CommentModel::select()->delete();
            PicsModel::select()->delete();
            UserModel::select()->delete();
            GroupModel::select()->delete();
            foreach (["group", "user", "pics", "score", "comment"] as $name) {
                Db::query("alter table `$name` auto_increment=1;");
            }
            return jb(200, "数据重置成功");
        } else {
            return jb(401, $auth["msg"]);
        }
    }
}