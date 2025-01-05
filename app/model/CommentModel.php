<?php

namespace app\model;

use think\Model;
use think\model\relation\HasOne;

/**
 * @mixin Model
 * @property mixed commentId
 * @property mixed picId
 * @property array|mixed pic
 * @property mixed userId
 * @property array|mixed user
 * @property mixed reply
 * @property mixed comment
 * @property mixed verified
 * @property mixed create
 * @property mixed update
 * @property mixed delete
 */
class CommentModel extends Model
{
    protected $table = "comment";
    protected $pk = "commentId";
    protected $schema = [
        "commentId" => "int",
        "picId"     => "int",
        "userId"    => "int",
        "reply"     => "int",
        "comment"   => "string",
        "verified"  => "string",
        "create"    => "datetime",
        "update"    => "datetime",
        "delete"    => "datetime"
    ];
    public function pic(): HasOne {
        return $this->hasOne(PicsModel::class, "picId", "picId");
    }

    public function user(): HasOne {
        return $this->hasOne(UserModel::class, "userId", "userId");
    }
}