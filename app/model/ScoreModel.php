<?php

namespace app\model;

use think\Model;
use think\model\relation\HasOne;

/**
 * @mixin Model
 * @property mixed scoreId
 * @property mixed picId
 * @property array|mixed pic
 * @property mixed userId
 * @property array|mixed user
 * @property mixed score
 * @property mixed create
 * @property mixed update
 * @property mixed delete
 * @property mixed name
 */
class ScoreModel extends Model
{
    protected $table = "score";
    protected $pk = "scoreId";
    protected $schema = [
        "scoreId"   => "int",
        "picId"     => "int",
        "userId"    => "int",
        "score"     => "float",
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