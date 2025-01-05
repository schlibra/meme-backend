<?php

namespace app\model;

use think\Model;

class BindModel extends Model {
    protected $table = "bind";
    protected $pk = "userId";
    protected $schema = [
        "userId" => "int",
        "sckurBind" => "string",
        "sckurUsername" => "string",
        "sckurNickname" => "string",
        "sckurAvatar" => "string",
        "giteeBind" => "string",
        "giteeUsername" => "string",
        "giteeNickname" => "string",
        "giteeAvatar" => "string",
        "githubBind" => "string",
        "githubUsername" => "string",
        "githubNickname" => "string",
        "githubAvatar" => "string",
        "gitlabBind" => "string",
        "gitlabUsername" => "string",
        "gitlabNickname" => "string",
        "gitlabAvatar" => "string",
        "microsoftBind" => "string",
        "microsoftUsername" => "string",
        "microsoftNickname" => "string",
        "microsoftAvatar" => "string",
    ];
}