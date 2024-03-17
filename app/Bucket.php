<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bucket extends Model
{
    protected $primaryKey = 'id';
    public $incrementing  = true;
    protected $table      = 'buckets';
    public $timestamps    = true;
}
