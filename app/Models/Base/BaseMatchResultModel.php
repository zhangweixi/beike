<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class BaseMatchResultModel extends Model
{
    protected $table = 'match_result';
    protected $primaryKey = 'match_id';
}
