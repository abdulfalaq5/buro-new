<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TempPrice extends Model
{
    protected $primaryKey = 'id';
    public $incrementing  = true;
    protected $table      = 'temp_prices';
    public $timestamps    = true;
}
