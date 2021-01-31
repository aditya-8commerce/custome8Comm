<?php
 
namespace App\Console\Commands\Courier;
 
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Response,Auth,Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use App\Mail\OmsEmailNotification;
use Illuminate\Support\Facades\File;
use PDF;
use PHPExcel; 
use PHPExcel_IOFactory;
use Ramsey\Uuid\Uuid;


use App\Http\Controllers\ApiLogController as ApiLog;
use App\Res\IndexRes;

use App\Models\OrderHeader;
use App\Models\OrderStatusTracking;
use App\Models\OrderDetail;
use App\Models\Courier\Jne;
use App\Models\CourierChannel;


class JneTrip extends Command
{
 
/**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'JneTrip:sender';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Jne Trip';
 
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->channel  = CourierChannel::whereIn("courier_id",["jne","jne_trucking"])->get();
    }

    public function handle()
    {
        set_time_limit(0);
        $models     = OrderHeader::where('status','shipped')->whereIn('courier_id',["jne","jne_trucking","jne_yes"])->with('details')->orderBy("order_header_id","ASC")->paginate(30);
            
        if(count($models) > 0){
			foreach($models as $order){
                foreach($this->channel as $channel){
                    $model      = new Jne($channel);
                    $res        = $model->tracking($order->awb_no);
                    if(@$res["cnote"]["pod_status"] == "DELIVERED"){
                        $old_date_timestamp = strtotime($res["cnote"]["cnote_pod_date"]);
                        $new_date = date('Y-m-d H:i:s', $old_date_timestamp);  
                        OrderHeader::where([["order_header_id" , $order->order_header_id]])->update(["status" => "delivered" , "update_time" => $new_date]);


                        usleep(25000);
                            OrderStatusTracking::insert([
                                "order_no"      => $order->order_no,
                                "status"        => 'delivered',
                                "system"        => 'oms',
                                "remarks"       => $res["cnote"]["last_status"],
                                "create_by"     => "api",
                                "order_header_id"   => $order->order_header_id,
                                "create_time"   => $new_date
                            ]);
 
                     }elseif(@$res["cnote"]["pod_status"] == "RETURN TO SHIPPER"){
                        $old_date_timestamp = strtotime($res["cnote"]["cnote_pod_date"]);
                        $new_date = date('Y-m-d H:i:s', $old_date_timestamp);  
                        OrderHeader::where([["order_header_id" , $order->order_header_id]])->update(["status" => "return-reject" , "update_time" => $new_date]);


                        usleep(25000);
                            OrderStatusTracking::insert([
                                "order_no"      => $order->order_no,
                                "status"        => 'return-reject',
                                "system"        => 'oms',
                                "remarks"       => $res["cnote"]["last_status"].' => '.json_encode($res["history"][count($res["history"])-3]),
                                "create_by"     => "api",
                                "order_header_id"   => $order->order_header_id,
                                "create_time"   => $new_date
                            ]);

 

                    }else{
                        continue;
                    }
                    usleep(25000);
                }        
			}
		}else{
			ApiLog::insertLog('Custome Server','JNE','', 'SUCCESS' , '', '\App\Console\Commands\Courier\JneTrip');
		}
    }

}