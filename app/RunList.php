<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RunList extends Model
{
    protected $primaryKey = 'id';
    public $incrementing  = true;
    protected $table      = 'run_lists';
    public $timestamps    = true;
}
