<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class BaseWifiModel extends Model
{
    protected $table = "wifi";
    protected $primaryKey = "wf_id";

    protected $guarded = [];
}
