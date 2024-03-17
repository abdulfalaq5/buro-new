<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RunListDetail extends Model
{
    protected $primaryKey = 'id';
    public $incrementing  = true;
    protected $table      = 'run_list_details';
    public $timestamps    = true;
}
