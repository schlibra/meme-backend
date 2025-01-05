<?php

namespace app\model;

use think\Model;
use think\model\relation\HasOne;

/**
 * @mixin Model
 * @property mixed picId
 * @property mixed name
 * @property mixed description
 * @property mixed userId
 * @property array|mixed user
 * @property mixed data
 * @property mixed compressed
 * @property mixed compressType
 * @property mixed type
 * @property mixed verified
 * @property mixed create
 * @property mixed update
 * @property mixed delete
 */
class PicsModel extends Model
{
    protected $table = "pics";
    protected $pk = "picId";
    protected $schema = [
        "picId"         => "int",
        "name"          => "string",
        "description"   => "string",
        "userId"        => "int",
        "data"          => "string",
        "compressed"    => "string",
        "compressType"  => "string",
        "type"          => "string",
        "verified"      => "string",
        "create"        => "datetime",
        "update"        => "datetime",
        "delete"        => "datetime",
    ];

    public function user(): HasOne{
        return $this->hasOne(UserModel::class, "userId", "userId");
    }
}