<?php

use Illuminate\Database\Seeder;
use App\Bucket;

class InsertBucketScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=InsertBucketScheduleSeeder
     *
     * @return void
     * 
     * cek data di minggu ini dan minggu depan apakah sudah ada data
     * jika belum maka langusng isert data saja
     * jika sudah cek hari apa saja yg sudah, insert data di hari yg belum
     * jika semua sudah maka tidak ada proses insert data
     */
    public function run()
    {
        $jmlLoop = 370; //satu tahun 365 hari
        $date = "2024-01-00";
        $end = "2025-01-00";
        for ($i=1; $i <= $jmlLoop; $i++) { 
            $tambahHariNow = "+$i days";
            $dateNow = date('Y-m-d', strtotime($tambahHariNow, strtotime($date)));
            $n = $i + 1;
            $tambahHariTomorrow = "+$n days";
            $tomorrow = date('Y-m-d', strtotime($tambahHariTomorrow, strtotime($dateNow)));
            if($tomorrow == $end){ //sampain tahun depan
                break;
            }
    
            //proses insert
            $tgl_schedul = $this->getSchedule($dateNow);
            $this->InsertNowDay($tgl_schedul);
        }
       
       // $this->InsertNextDay($tgl_schedul);
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
        if(empty($cekDataBucketSenin)){
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
        if(empty($cekDataBucketSelasa)){
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
        if(empty($cekDataBucketRabu)){
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
        if(empty($cekDataBucketKamis)){
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

    public function getSchedule($dateNow)
    {
        $hari = date('D', strtotime($dateNow));
        switch ($hari) {
            case 'Sun':
                $tambah_senin = '+1 days';
                $tambah_selasa = '+2 days';
                $tambah_rabu = '+3 days';
                $tambah_kamis = '+4 days';

                $tambah_senin_new ='+8 days';
                $tambah_selasa_new ='+9 days';
                $tambah_rabu_new = '+10 days';
                $tambah_kamis_new = '+11 days';
                break;

            case 'Mon':
                $tambah_senin = '+0 days';
                $tambah_selasa = '+1 days';
                $tambah_rabu = '+2 days';
                $tambah_kamis = '+3 days';

                $tambah_senin_new ='+7 days';
                $tambah_selasa_new ='+8 days';
                $tambah_rabu_new = '+9 days';
                $tambah_kamis_new = '+10 days';
                break;

            case 'Tue':
                $tambah_senin = '-1 days';
                $tambah_selasa = '+0 days';
                $tambah_rabu = '+1 days';
                $tambah_kamis = '+2 days';

                $tambah_senin_new ='+7 days';
                $tambah_selasa_new ='+8 days';
                $tambah_rabu_new = '+9 days';
                $tambah_kamis_new = '+10 days';
                break;

            case 'Wed':
                $tambah_senin = '-2 days';
                $tambah_selasa = '-1 days';
                $tambah_rabu = '+0 days';
                $tambah_kamis = '+1 days';

                $tambah_senin_new ='+5 days';
                $tambah_selasa_new ='+6 days';
                $tambah_rabu_new = '+7 days';
                $tambah_kamis_new = '+8 days';
                break;

            case 'Thu':
                $tambah_senin = '-3 days';
                $tambah_selasa = '-2 days';
                $tambah_rabu = '-1 days';
                $tambah_kamis = '+0 days';

                $tambah_senin_new ='+4 days';
                $tambah_selasa_new ='+5 days';
                $tambah_rabu_new = '+6 days';
                $tambah_kamis_new = '+7 days';
                break;

            case 'Fri':
                $tambah_senin = '-4 days';
                $tambah_selasa = '-3 days';
                $tambah_rabu = '-2 days';
                $tambah_kamis = '-1 days';

                $tambah_senin_new ='+3 days';
                $tambah_selasa_new ='+4 days';
                $tambah_rabu_new = '+5 days';
                $tambah_kamis_new = '+6 days';
                break;

            case 'Sat':
                $tambah_senin = '-5 days';
                $tambah_selasa = '-4 days';
                $tambah_rabu = '-3 days';
                $tambah_kamis = '-2 days';

                $tambah_senin_new ='+2 days';
                $tambah_selasa_new ='+3 days';
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
