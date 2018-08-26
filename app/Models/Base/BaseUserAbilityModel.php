<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class BaseUserAbilityModel extends Model
{
    protected $table= "user_global_ability";
    protected $primaryKey = "user_id";

}
