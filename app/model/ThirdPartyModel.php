<?php

namespace app\model;

use think\Model;

class ThirdPartyModel extends Model
{
    protected $table = "thirdParty";
    protected $pk = "settingId";
    protected $schema = [
        "enableSckur" => "string",
        "sckurApiKey" => "string",
        "sckurClientId" => "string",
        "enableGitee" => "string",
        "giteeClientId" => "string",
        "giteeClientSecret" => "string",
        "enableGithub" => "string",
        "githubClientId" => "string",
        "githubClientSecret" => "string",
        "enableGitlab" => "string",
        "gitlabClientId" => "string",
        "gitlabClientSecret" => "string",
        "enableMicrosoft" => "string",
        "microsoftClientId" => "string",
        "microsoftClientSecret" => "string",
    ];
}