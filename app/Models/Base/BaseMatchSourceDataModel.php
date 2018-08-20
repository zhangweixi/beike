<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class BaseMatchSourceDataModel extends Model
{
    protected $primaryKey   = "match_source_id";
    protected $table        = "match_source_data";
}
