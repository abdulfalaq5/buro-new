<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\RunList;
use App\RunListDetail;
use App\Bucket;
use App\TempPrice;

class MakeRunList extends Command
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
    public $jml_bucket  = 0;

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
        $dateNow = date('Y-m-d'); //diganti jadi "2024-03-11" sesuai tgl yg diinginkan
        $tomorrow = $this->getSchedule($dateNow);
        $this->deleteBucket($dateNow);
        $this->deleteRunList($dateNow, $tomorrow);
        $dataOrder = $this->insertNotFront($dateNow, $tomorrow);
        $dataOrderFront = $this->insertFront($dateNow, $tomorrow);
        if ($dataOrder == true || $dataOrderFront == true) {
            $this->deleteBucket($dateNow);
            if ($this->insertBucket($dateNow)) {
                $this->hitungJumlahPrice();
            }
        }

        $this->deleteTempPrice();
        $this->checkDay();

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
            ->whereRaw("coi.id NOT IN (SELECT rid.component_order_item FROM run_lists ri
            JOIN run_list_details rid ON rid.run_list_id = ri.id 
            JOIN buckets b ON b.id = ri.bucket_id 
            WHERE DATE(b.date) > $dateNow)")
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
                if (!$getTempPrice) {
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
                    if ($total_all_run_list >= $this->maxPrice) {
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
            ->whereRaw("coi.id NOT IN (SELECT rid.component_order_item FROM run_lists ri
            JOIN run_list_details rid ON rid.run_list_id = ri.id 
            JOIN buckets b ON b.id = ri.bucket_id 
            WHERE DATE(b.date) > $dateNow)")
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
                if (!$getTempPrice) {
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
                    if ($total_all_run_list >= $this->maxPrice) {
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
                $date_param = date('Y-m-d', strtotime($value->schedule));
                if ($date_param > $dateNow) {
                    RunList::where('id', $value->id)->delete();
                    RunListDetail::where('run_list_id', $value->id)->delete();
                }
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
                'o.id',
                'rld.run_list_id'
            )
                ->join('run_list_details as rld', 'rld.run_list_id', '=', 'run_lists.id')
                ->join('component_order_items as coi', 'coi.id', '=', 'rld.component_order_item')
                ->join('order_items as oi', 'coi.order_item_id', '=', 'oi.id')
                ->join('orders as o', 'oi.order_id', '=', 'o.id')
                ->where('o.shipping_due_date', '>', $dateNow)
                ->where('o.status', 20)
                ->whereRaw("coi.id NOT IN (SELECT rid.component_order_item FROM run_lists ri
                JOIN run_list_details rid ON rid.run_list_id = ri.id 
                JOIN buckets b ON b.id = ri.bucket_id 
                WHERE DATE(b.date) > $dateNow)")
                ->orderBy('rld.run_list_id', 'asc')
                ->get();

            $jml_data = count($dataRunList);
            if ($jml_data > 0) {
                $schedule = '';
                $schedule_old = '';
                $run_list_id = 0;
                $run_list_id_old = 0;
                $bucket_id = '';
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
                        $bucket_id = $getBucket->id;
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
                        $bucket_id = $save->id;
                        $this->jml_bucket += 1;
                    }

                    $this->data_order_id_all[] = $bucket_id . "|" . $dataRunList[$i]->id;
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
                $date_param = date('Y-m-d', strtotime($value->schedule));
                if ($date_param > $dateNow) {
                    $getBucket = Bucket::where('date', $date_get)->first();
                    if (!empty($getBucket->id)) {
                        Bucket::where('id', $getBucket->id)->delete();
                    }
                }
            }
        }
    }

    public function hitungJumlahPrice()
    {
        $data_order_id_all = array_unique($this->data_order_id_all);
        sort($data_order_id_all);
        $jml = count($data_order_id_all);
        if ($jml > 0) {
            $total = 0;
            for ($i = 0; $i < $jml; $i++) {
                $getdataArray = explode("|", $data_order_id_all[$i]);
                $bucket_id = !empty($getdataArray[0]) ? $getdataArray[0] : 0;
                $order_id = !empty($getdataArray[1]) ? $getdataArray[1] : 0;
                if ($i != 0) { //cek data kedua dan seterusnya
                    $getdataArray = explode("|", $data_order_id_all[$i - 1]);
                    $bucket_id_old = !empty($getdataArray[0]) ? $getdataArray[0] : 0;
                    $order_id_old = !empty($getdataArray[1]) ? $getdataArray[1] : 0;
                } else { //untuk data pertama saja
                    $getdataArray = explode("|", $data_order_id_all[$i]);
                    $bucket_id_old = !empty($getdataArray[0]) ? $getdataArray[0] : 0;
                    $order_id_old = !empty($getdataArray[1]) ? $getdataArray[1] : 0;
                }

                if ($this->jml_bucket == 1) { //jumlahnya 1
                    $total = 0;
                    $getTempPrice = TempPrice::where('order_id', $order_id)->first();
                    $nomTotal = !empty($getTempPrice) ? $getTempPrice->price : 0;
                    $total += $nomTotal;

                    $getBucket = Bucket::where('id', $bucket_id)->first();
                    if (!empty($getBucket->id)) {
                        Bucket::where('id', $getBucket->id)->update([
                            'value' => $getBucket->value + $total
                        ]);
                    }
                } else {
                    $total = 0;
                    $getTempPrice = TempPrice::where('order_id', $order_id)->first();
                    $nomTotal = !empty($getTempPrice) ? $getTempPrice->price : 0;
                    $total += $nomTotal;

                    $getBucket = Bucket::where('id', $bucket_id)->first();
                    if (!empty($getBucket->id)) {
                        Bucket::where('id', $getBucket->id)->update([
                            'value' => $total
                        ]);
                    }
                }
            }
        }

        return true;
    }

    public function checkDay()
    {
        $jmlLoop = 370; //satu tahun 365 hari
        $date = "2024-01-00";
        $end = "2025-01-00";
        for ($i = 1; $i <= $jmlLoop; $i++) {
            $tambahHariNow = "+$i days";
            $dateNow = date('Y-m-d', strtotime($tambahHariNow, strtotime($date)));
            $n = $i + 1;
            $tambahHariTomorrow = "+$n days";
            $tomorrow = date('Y-m-d', strtotime($tambahHariTomorrow, strtotime($dateNow)));
            if ($tomorrow == $end) { //sampain tahun depan
                break;
            }

            //proses insert
            $tgl_schedul = $this->getScheduleSeed($dateNow);
            $this->InsertNowDay($tgl_schedul);
        }
        return true;
    }

    public function InsertNowDay($tgl_schedul)
    {
        $senin = $tgl_schedul['senin'];
        $selasa = $tgl_schedul['selasa'];
        $rabu = $tgl_schedul['rabu'];
        $kamis = $tgl_schedul['kamis'];

        //senin
        $date_in = date('Y-m-d H:i:s', strtotime($senin));
        $cekDataBucketSenin = Bucket::where('date', $date_in)->first();
        if (empty($cekDataBucketSenin)) {
            $Weeknummer = date("W", strtotime($senin));
            $day_in = date('l', strtotime($senin));

            $save = new Bucket;
            $save->week = $Weeknummer;
            $save->day = $day_in;
            $save->date = $date_in;
            $save->save();
        }

        //selsasa
        $date_in = date('Y-m-d H:i:s', strtotime($selasa));
        $cekDataBucketSelasa = Bucket::where('date', $date_in)->first();
        if (empty($cekDataBucketSelasa)) {
            $Weeknummer = date("W", strtotime($selasa));
            $day_in = date('l', strtotime($selasa));

            $save = new Bucket;
            $save->week = $Weeknummer;
            $save->day = $day_in;
            $save->date = $date_in;
            $save->save();
        }

        //rabu
        $date_in = date('Y-m-d H:i:s', strtotime($rabu));
        $cekDataBucketRabu = Bucket::where('date', $date_in)->first();
        if (empty($cekDataBucketRabu)) {
            $Weeknummer = date("W", strtotime($rabu));
            $day_in = date('l', strtotime($rabu));

            $save = new Bucket;
            $save->week = $Weeknummer;
            $save->day = $day_in;
            $save->date = $date_in;
            $save->save();
        }

        //kamis
        $date_in = date('Y-m-d H:i:s', strtotime($kamis));
        $cekDataBucketKamis = Bucket::where('date', $date_in)->first();
        if (empty($cekDataBucketKamis)) {
            $Weeknummer = date("W", strtotime($kamis));
            $day_in = date('l', strtotime($kamis));

            $save = new Bucket;
            $save->week = $Weeknummer;
            $save->day = $day_in;
            $save->date = $date_in;
            $save->save();
        }

        return true;
    }

    public function getScheduleSeed($dateNow)
    {
        $hari = date('D', strtotime($dateNow));
        switch ($hari) {
            case 'Sun':
                $tambah_senin = '+1 days';
                $tambah_selasa = '+2 days';
                $tambah_rabu = '+3 days';
                $tambah_kamis = '+4 days';

                $tambah_senin_new = '+8 days';
                $tambah_selasa_new = '+9 days';
                $tambah_rabu_new = '+10 days';
                $tambah_kamis_new = '+11 days';
                break;

            case 'Mon':
                $tambah_senin = '+0 days';
                $tambah_selasa = '+1 days';
                $tambah_rabu = '+2 days';
                $tambah_kamis = '+3 days';

                $tambah_senin_new = '+7 days';
                $tambah_selasa_new = '+8 days';
                $tambah_rabu_new = '+9 days';
                $tambah_kamis_new = '+10 days';
                break;

            case 'Tue':
                $tambah_senin = '-1 days';
                $tambah_selasa = '+0 days';
                $tambah_rabu = '+1 days';
                $tambah_kamis = '+2 days';

                $tambah_senin_new = '+7 days';
                $tambah_selasa_new = '+8 days';
                $tambah_rabu_new = '+9 days';
                $tambah_kamis_new = '+10 days';
                break;

            case 'Wed':
                $tambah_senin = '-2 days';
                $tambah_selasa = '-1 days';
                $tambah_rabu = '+0 days';
                $tambah_kamis = '+1 days';

                $tambah_senin_new = '+5 days';
                $tambah_selasa_new = '+6 days';
                $tambah_rabu_new = '+7 days';
                $tambah_kamis_new = '+8 days';
                break;

            case 'Thu':
                $tambah_senin = '-3 days';
                $tambah_selasa = '-2 days';
                $tambah_rabu = '-1 days';
                $tambah_kamis = '+0 days';

                $tambah_senin_new = '+4 days';
                $tambah_selasa_new = '+5 days';
                $tambah_rabu_new = '+6 days';
                $tambah_kamis_new = '+7 days';
                break;

            case 'Fri':
                $tambah_senin = '-4 days';
                $tambah_selasa = '-3 days';
                $tambah_rabu = '-2 days';
                $tambah_kamis = '-1 days';

                $tambah_senin_new = '+3 days';
                $tambah_selasa_new = '+4 days';
                $tambah_rabu_new = '+5 days';
                $tambah_kamis_new = '+6 days';
                break;

            case 'Sat':
                $tambah_senin = '-5 days';
                $tambah_selasa = '-4 days';
                $tambah_rabu = '-3 days';
                $tambah_kamis = '-2 days';

                $tambah_senin_new = '+2 days';
                $tambah_selasa_new = '+3 days';
                $tambah_rabu_new = '+4 days';
                $tambah_kamis_new = '+5 days';
                break;

            default:
                $tambah = '+1 days';
                break;
        }
        $senin = date('Y-m-d', strtotime($tambah_senin, strtotime($dateNow)));
        $selasa = date('Y-m-d', strtotime($tambah_selasa, strtotime($dateNow)));
        $rabu = date('Y-m-d', strtotime($tambah_rabu, strtotime($dateNow)));
        $kamis = date('Y-m-d', strtotime($tambah_kamis, strtotime($dateNow)));

        $senin_next = date('Y-m-d', strtotime($tambah_senin_new, strtotime($dateNow)));
        $selasa_next = date('Y-m-d', strtotime($tambah_selasa_new, strtotime($dateNow)));
        $rabu_next = date('Y-m-d', strtotime($tambah_rabu_new, strtotime($dateNow)));
        $kamis_next = date('Y-m-d', strtotime($tambah_kamis_new, strtotime($dateNow)));

        return [
            'senin' => $senin,
            'selasa' => $selasa,
            'rabu' => $rabu,
            'kamis' => $kamis,
            'senin_next' => $senin_next,
            'selasa_next' => $selasa_next,
            'rabu_next' => $rabu_next,
            'kamis_next' => $kamis_next,
        ];
    }
}
