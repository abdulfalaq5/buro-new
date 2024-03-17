<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\RunList;
use App\RunListDetail;
use App\Bucket;
use App\TempPrice;

class MakeRunListNew extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:list';
    public $data_bucket_id_all  = [];
    public $data_order_id_all  = [];
    public $maxPrice  = 8000;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generated data di run list sampai bucket';

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
        echo " => PROSES RUN LIST \n\n";
        $dateNow =date('Y-m-d'); //diganti jadi "2024-03-11" sesuai tgl yg diinginkan
        $tomorrow = $this->getSchedule($dateNow);
        $this->deleteBucket($dateNow);
        $this->deleteRunList($dateNow, $tomorrow);
        $dataOrder = $this->insertNotFront($dateNow, $tomorrow);
        $dataOrderFront = $this->insertFront($dateNow, $tomorrow);
        if ($dataOrder == true || $dataOrderFront == true) {
            $this->deleteBucket($dateNow);
            if($this->insertBucket($dateNow)){
                $this->hitungJumlahPrice();
            }
        }

        $this->deleteTempPrice();

        if ($dataOrder) {
            echo " => SUKSES RUN LIST NOT FRONT \n\n";
        } else {
            echo " => TIDAK ADA DATA ORDER NOT FRONT TERBARU \n\n";
        }
        if ($dataOrderFront) {
            echo " => SUKSES RUN LIST FRONT \n\n";
        } else {
            echo " => TIDAK ADA DATA ORDER FRONT TERBARU \n\n";
        }
    }

    public function getSchedule($dateNow)
    {
        $hari = date('D', strtotime($dateNow));
        switch ($hari) {
            case 'Sun':
                $tambah = '+1 days';
                break;

            case 'Mon':
                $tambah = '+1 days';
                break;

            case 'Tue':
                $tambah = '+1 days';
                break;

            case 'Wed':
                $tambah = '+1 days';
                break;

            case 'Thu':
                $tambah = '+4 days';
                break;

            case 'Fri':
                $tambah = '+3 days';
                break;

            case 'Sat':
                $tambah = '+2 days';
                break;

            default:
                $tambah = '+1 days';
                break;
        }
        $tomorrow = date('Y-m-d', strtotime($tambah, strtotime($dateNow)));
        return $tomorrow;
    }

    public function insertNotFront($dateNow, $tomorrow)
    {
        //cek tanggal order, cari yg belum lewat hari ini
        $dataOrder = DB::table('orders as o')
            ->select('coi.order_item_id', 'coi.component_id', 'coi.color_id', 'coi.id as component_order_item_id', 'oi.order_id', 'v.price', 'oi.quantity')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->join('component_order_items as coi', 'coi.order_item_id', '=', 'oi.id')
            ->join('variants as v', 'v.id', '=', 'oi.variant_id')
            ->where('o.shipping_due_date', '>', $dateNow)
            ->where('coi.component_id', '!=', 13)
            ->where('o.status', 20)
            ->orderBy('coi.color_id', 'asc')
            ->get();
        $jmlDataOrder = count($dataOrder);
        if (!empty($dataOrder) && $jmlDataOrder > 0) {
            $color_id_same = '';
            $color_id_same_old = '';
            $run_list_id = '';
            $total_jml = 0;
            $total_all_run_list = 0;
            for ($i = 0; $i < $jmlDataOrder; $i++) {
                $color_id_same = $dataOrder[$i]->color_id;
                if ($i != 0) { //cek data kedua dan seterusnya
                    $color_id_same_old = $dataOrder[$i - 1]->color_id;
                } else { //untuk data pertama saja
                    $color_id_same_old = $dataOrder[$i]->color_id;
                }

                $getdataVariant =  DB::table('order_items as oi')
                    ->select('v.price', 'oi.quantity')
                    ->join('variants as v', 'v.id', '=', 'oi.variant_id')
                    ->where('oi.order_id', $dataOrder[$i]->order_id)
                    ->groupBy('oi.id')
                    ->get();

                $total_jml = 0;
                foreach ($getdataVariant as $key => $value) {
                    $price = !empty($value->price) ? $value->price : 0;
                    $qty = !empty($value->quantity) ? $value->quantity : 0;
                    $total_jml = $total_jml + ($price * $qty);
                }

                $getTempPrice = TempPrice::where('order_id', $dataOrder[$i]->order_id)->first();
                if(!$getTempPrice){
                    $tempPrice = new TempPrice;
                    $tempPrice->order_id = $dataOrder[$i]->order_id;
                    $tempPrice->price = $total_jml;
                    $tempPrice->save();
                    $total_all_run_list += $total_jml;
                }
                
                //group by color
                if ($color_id_same == $color_id_same_old && $i != 0) { //proses insert ke run list detail
                    $runListDetail = new RunListDetail;
                    $runListDetail->run_list_id = $run_list_id;
                    $runListDetail->component_order_item = $dataOrder[$i]->component_order_item_id;
                    $runListDetail->save();
                } else { //proses insert ke run list
                    if($total_all_run_list >= $this->maxPrice){
                        $tomorrow = $this->getSchedule($tomorrow);
                    }
                    $dataNumber = $this->getNum($tomorrow);
                    $runList = new RunList;
                    $runList->week = $dataNumber['week'];
                    $runList->number = $dataNumber['number'];
                    $runList->schedule = $tomorrow;
                    $runList->save();
                    $run_list_id = $runList->id;

                    $runListDetail = new RunListDetail;
                    $runListDetail->run_list_id = $run_list_id;
                    $runListDetail->component_order_item = $dataOrder[$i]->component_order_item_id;
                    $runListDetail->save();
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public function insertFront($dateNow, $tomorrow)
    {
        //cek tanggal order, cari yg belum lewat hari ini
        $dataOrder = DB::table('orders as o')
            ->select('coi.order_item_id', 'coi.component_id', 'coi.color_id', 'coi.id as component_order_item_id', 'oi.order_id', 'v.price', 'oi.quantity')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->join('component_order_items as coi', 'coi.order_item_id', '=', 'oi.id')
            ->join('variants as v', 'v.id', '=', 'oi.variant_id')
            ->where('o.shipping_due_date', '>', $dateNow)
            ->where('coi.component_id', 13)
            ->where('o.status', 20)
            ->orderBy('coi.color_id', 'asc')
            ->get();
        $jmlDataOrder = count($dataOrder);
        if (!empty($dataOrder) && $jmlDataOrder > 0) {
            $color_id_same = '';
            $color_id_same_old = '';
            $run_list_id = '';
            $total_jml = 0;
            $total_all_run_list = 0;
            for ($i = 0; $i < $jmlDataOrder; $i++) {
                $color_id_same = $dataOrder[$i]->color_id;
                if ($i != 0) { //cek data kedua dan seterusnya
                    $color_id_same_old = $dataOrder[$i - 1]->color_id;
                } else { //untuk data pertama saja
                    $color_id_same_old = $dataOrder[$i]->color_id;
                }

                $getdataVariant =  DB::table('order_items as oi')
                    ->select('v.price', 'oi.quantity')
                    ->join('variants as v', 'v.id', '=', 'oi.variant_id')
                    ->where('oi.order_id', $dataOrder[$i]->order_id)
                    ->groupBy('oi.id')
                    ->get();

                $total_jml = 0;
                foreach ($getdataVariant as $key => $value) {
                    $price = !empty($value->price) ? $value->price : 0;
                    $qty = !empty($value->quantity) ? $value->quantity : 0;
                    $total_jml = $total_jml + ($price * $qty);
                }

                $getTempPrice = TempPrice::where('order_id', $dataOrder[$i]->order_id)->first();
                if(!$getTempPrice){
                    $tempPrice = new TempPrice;
                    $tempPrice->order_id = $dataOrder[$i]->order_id;
                    $tempPrice->price = $total_jml;
                    $tempPrice->save();
                    $total_all_run_list += $total_jml;
                }
                
                //group by color
                if ($color_id_same == $color_id_same_old && $i != 0) { //proses insert ke run list detail
                    $runListDetail = new RunListDetail;
                    $runListDetail->run_list_id = $run_list_id;
                    $runListDetail->component_order_item = $dataOrder[$i]->component_order_item_id;
                    $runListDetail->save();
                } else { //proses insert ke run list
                    if($total_all_run_list >= $this->maxPrice){
                        $tomorrow = $this->getSchedule($tomorrow);
                    }
                    $dataNumber = $this->getNum($tomorrow);
                    $runList = new RunList;
                    $runList->week = $dataNumber['week'];
                    $runList->number = $dataNumber['number'];
                    $runList->schedule = $tomorrow;
                    $runList->save();
                    $run_list_id = $runList->id;

                    $runListDetail = new RunListDetail;
                    $runListDetail->run_list_id = $run_list_id;
                    $runListDetail->component_order_item = $dataOrder[$i]->component_order_item_id;
                    $runListDetail->save();
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public function deleteTempPrice()
    {
        TempPrice::where('id', '!=', 0)->delete();
        return true;
    }

    public function deleteRunList($dateNow, $tomorrow)
    {
        $dataOrder = DB::table('orders as o')
            ->select('rl.id', 'rl.schedule')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->join('component_order_items as coi', 'coi.order_item_id', '=', 'oi.id')
            ->join('run_list_details as rld', 'rld.component_order_item', '=', 'coi.id')
            ->join('run_lists as rl', 'rl.id', '=', 'rld.run_list_id')
            ->where('o.shipping_due_date', '>', $dateNow)
            ->where('o.status', 20)
            ->orderBy('coi.color_id', 'asc')
            ->get();
        if (!empty($dataOrder) && count($dataOrder) > 0) {
            foreach ($dataOrder as $key => $value) {
                RunList::where('id', $value->id)->delete();
                RunListDetail::where('run_list_id', $value->id)->delete();
            }
        }
    }

    public function getNum($dateNow)
    {
        $Weeknummer = date("W", strtotime($dateNow));
        $dataLast = RunList::select('number')->where('week', $Weeknummer)->orderBy('id', 'desc')->first();
        if (!empty($dataLast->number)) {
            $number = (int) $dataLast->number + 1;
        } else {
            $number = 1;
        }
        return [
            'week' => $Weeknummer,
            'number' => $number
        ];
    }

    public function insertBucket($dateNow)
    {
        try {
            $dataRunList = RunList::select(
                'run_lists.week',
                'run_lists.number',
                'run_lists.schedule',
                'oi.price',
                'oi.id',
                'rld.run_list_id'
            )
                ->join('run_list_details as rld', 'rld.run_list_id', '=', 'run_lists.id')
                ->join('component_order_items as coi', 'coi.id', '=', 'rld.component_order_item')
                ->join('order_items as oi', 'coi.order_item_id', '=', 'oi.id')
                ->join('orders as o', 'oi.order_id', '=', 'o.id')
                ->where('o.shipping_due_date', '>', $dateNow)
                ->where('o.status', 20)
                ->orderBy('rld.run_list_id', 'asc')
                ->get();
    
            $jml_data = count($dataRunList);
            if ($jml_data > 0) {
                $schedule = '';
                $schedule_old = '';
                $run_list_id = 0;
                $run_list_id_old = 0;
                for ($i = 0; $i < $jml_data; $i++) {
                    $schedule = $dataRunList[$i]->schedule;
                    $run_list_id = $dataRunList[$i]->run_list_id;
                    if ($i != 0) { //cek data kedua dan seterusnya
                        $schedule_old = $dataRunList[$i - 1]->schedule;
                        $run_list_id_old = $dataRunList[$i - 1]->run_list_id;
                    } else { //untuk data pertama saja
                        $schedule_old = $dataRunList[$i]->schedule;
                        $run_list_id_old = $dataRunList[$i]->run_list_id;
                    }
    
                    if ($schedule == $schedule_old && $i != 0) { //update
                        $week_u = $dataRunList[$i]->week;
                        $number_u = $dataRunList[$i]->number;
                        $tgl = $dataRunList[$i]->schedule;
                        $date_u = date('Y-m-d H:i:s', strtotime($tgl));
                        $getBucket = Bucket::where('date', $date_u)->first();
                        $listData = $week_u . '.' . $number_u;
                        if ($run_list_id != $run_list_id_old && $i != 0) {
                            $run_list_u = $getBucket->run_list . ', Runs ' . $listData;
                            Bucket::where('id', $getBucket->id)->update([
                                'run_list' => $run_list_u
                            ]);
                        }
    
                        //update run list untuk insert bucket id
                        RunList::where('id', $run_list_id)->update([
                            'bucket_id' => $getBucket->id
                        ]);
                    } else { //insert
                        $week = $dataRunList[$i]->week;
                        $number = $dataRunList[$i]->number;
                        $date = $dataRunList[$i]->schedule;
                        $day_in = date('l', strtotime($date));
                        //$date_in = date('d/m/Y', strtotime($date));
                        $date_in = date('Y-m-d H:i:s', strtotime($date));
                        $run_list = 'Run ' . $week . '.' . $number;
                        $value = 0; //(int) $dataRunList[$i]->price;
    
                        $save = new Bucket;
                        $save->week = $week;
                        $save->day = $day_in;
                        $save->date = $date_in;
                        $save->run_list = $run_list;
                        $save->value = $value;
                        $save->save();
    
                        //update run list untuk insert bucket id
                        RunList::where('id', $run_list_id)->update([
                            'bucket_id' => $save->id
                        ]);
                        
                        $this->data_bucket_id_all[] = $save->id;
                        $this->data_order_id_all[] = $dataRunList[$i]->id;
                    }
                }
            }
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function deleteBucket($dateNow)
    {
        $dataRunList = RunList::select(
            'run_lists.week',
            'run_lists.number',
            'run_lists.schedule',
            'coi.price'
        )
            ->join('run_list_details as rld', 'rld.run_list_id', '=', 'run_lists.id')
            ->join('component_order_items as coi', 'coi.id', '=', 'rld.component_order_item')
            ->join('order_items as oi', 'coi.order_item_id', '=', 'oi.id')
            ->join('orders as o', 'oi.order_id', '=', 'o.id')
            ->where('o.shipping_due_date', '>', $dateNow)
            ->where('o.status', 20)
            ->get();
        
        $jml_data = count($dataRunList);
        if ($jml_data > 0) {
            foreach ($dataRunList as $key => $value) {
                $date_get = date('Y-m-d H:i:s', strtotime($value->schedule));
                $getBucket = Bucket::where('date', $date_get)->first();
                if (!empty($getBucket->id)) {
                    Bucket::where('id', $getBucket->id)->delete();
                }
            }
        }
    }

    public function hitungJumlahPrice()
    {
        $jml= count($this->data_order_id_all);
        if($jml > 0){
            for ($i=0; $i < $jml; $i++) { 
                $getTempPrice = TempPrice::where('order_id', $this->data_order_id_all[$i])->first();
                $nomTotal = !empty($getTempPrice) ? $getTempPrice->price : 0;

                $getBucket = Bucket::where('id', $this->data_bucket_id_all[$i])->first();
                if (!empty($getBucket->id)) {
                    Bucket::where('id', $getBucket->id)->update([
                        'value' => $nomTotal//(int)$getBucket->value + (int)$total_jml
                    ]);
                }
            }
        }
    }
}
