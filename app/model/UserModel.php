<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;
use think\model\relation\HasOne;

/**
 * @mixin Model
 * @property mixed userId
 * @property mixed username
 * @property mixed password
 * @property mixed nickname
 * @property mixed email
 * @property mixed verified
 * @property mixed groupId
 * @property array|mixed group
 * @property mixed ban
 * @property mixed reason
 * @property mixed birth
 * @property mixed sex
 * @property mixed description
 * @property mixed create
 */
class UserModel extends Model
{
    protected $table = "user";
    protected $pk = "userId";
    protected $schema = [
        "userId" => "int",
        "username" => "string",
        "password" => "string",
        "nickname" => "string",
        "email" => "string",
        "verified" => "string",
        "groupId" => "int",
        "ban" => "string",
        "reason" => "string",
        "birth" => "int",
        "sex" => "string",
        "description" => "string",
        "create" => "datetime"
    ];

    public function group(): HasOne {
        return $this->hasOne(GroupModel::class, "groupId", "groupId");
    }
}
