<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class BaseMatchDataProcessModel extends Model
{
    protected $table        = "match_data_parse_process";
    protected $primaryKey   = "match_id";
    protected $guarded      = [];
}
