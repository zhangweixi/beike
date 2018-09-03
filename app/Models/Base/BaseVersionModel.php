<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class BaseVersionModel extends Model
{
    protected $table = "version";
    protected $primaryKey = "id";

}
