<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class BaseStarTypeModel extends Model
{
    protected $table = 'star_type';
    public $timestamps = false;
    protected $guarded = [];
}
