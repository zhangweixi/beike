<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class BaseFriendModel extends Model
{
    protected $table = "friend";
    protected $primaryKey = "friend_id";
}
