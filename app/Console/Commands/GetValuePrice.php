<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Bucket;

class GetValuePrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:value';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get value price satu minggu aktif';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo "\n";
        echo " # PROSES GET VALUE : \n";
        $dateNow = date('Y-m-d');
        $Weeknummer = date("W", strtotime($dateNow));
        $total_Monday = $this->total($Weeknummer, 'Monday');
        $total_Tuesday = $this->total($Weeknummer, 'Tuesday');
        $total_Wednesday = $this->total($Weeknummer, 'Wednesday');
        $total_Thursday = $this->total($Weeknummer, 'Thursday');
        echo "\n";
        echo " => TOTAL MONDAY = $total_Monday \n\n";
        echo " => TOTAL TUESDAY = $total_Tuesday \n\n";
        echo " => TOTAL WEDNESDAY = $total_Wednesday \n\n";
        echo " => TOTAL THURSDAY = $total_Thursday \n\n";
    }

    public function total($Weeknummer, $day)
    {
        $getDataBucket = Bucket::select(DB::raw("SUM(value) as total"))
        ->where('week', $Weeknummer)
        ->where('day', $day)
        ->first();
        return !empty($getDataBucket->total) ? $getDataBucket->total : 0;
    }
}
