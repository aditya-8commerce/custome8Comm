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


class AutoCloseOrder extends Command
{
 
/**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AutoCloseOrder:sender';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set status delivered order where update time > 4 month ago';
 
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        set_time_limit(0);
        $models = OrderHeader::where([["status","shipped"],["update_time","<", Carbon::now()->subMonths(4)],['courier_id', 'not like', "%pickup%"]])->with('details')->orderBy("order_header_id","ASC")->paginate(100);
        if(count($models) > 0){
			foreach($models as $order){
                $Date = $order->update_time;
                $new_date = date('Y-m-d H:i:s', strtotime($Date. ' + 2 day'));

                OrderHeader::where([["order_header_id" , $order->order_header_id]])->update(["status" => "delivered" , "update_time" => $new_date]);


                if(count($order->details) > 0){

                    foreach($order->details as $detail){
                        usleep(25000);
                        OrderDetail::where([["order_detail_id" , $detail->order_detail_id]])->update(["status" => "delivered" , "update_time" => $new_date , "qty_delivered" => $detail->qty_ship]);
                    }

                }
                OrderStatusTracking::insert([
                                "order_no"      => $order->order_no,
                                "status"        => 'delivered',
                                "system"        => 'oms',
                                "remarks"       => "AUTO CLOSE BY SYSTEM ".date('Y-m-d H:i:s'),
                                "create_by"     => "api",
                                "order_header_id"   => $order->order_header_id,
                                "create_time"   => $new_date
                            ]);  
			}
		}else{
			ApiLog::insertLog('Custome Server','AutoCloseOrder','', 'SUCCESS' , '', '\App\Console\Commands\Order\AutoCloseOrder');
		}
    }

}