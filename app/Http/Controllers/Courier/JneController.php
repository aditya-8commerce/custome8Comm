<?php
namespace App\Http\Controllers\Courier; 

use App\Http\Controllers\Controller;
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


class JneController extends Controller
{
    public function __construct(){
        $this->channel  = CourierChannel::whereIn("courier_id",["jne","jne_trucking"])->get();
    }

    public function index(){
        return IndexRes::resultData(200,['message' => 'API Custome 8commerce for Jne'],[]);
    }


    public function trackingByAwb(){
        $awb_no     = @$_GET['awb_no'];

        if(empty($awb_no)){
            return IndexRes::resultData(422,[],["messages" => 'AWB Number required']);
        }else{
            $datas  = [];
            
            foreach($this->channel as $channel){
                $model      = new Jne($channel);
                $res        = $model->tracking($awb_no);
                if(!empty(@$res["cnote"]["cnote_no"])){
                    $datas[]    = $res;
                    break;
                }
                usleep(25000);
            }

            // $channel    = CourierChannel::where([["courier_channel_id" , 2]])->first();
            // $model      = new Jne($channel);
            // $datas      = $model->tracking($awb_no);

            return IndexRes::resultData(200,['message' => $datas  ],[]);
        }
    }

    public function trackingAwb(){
        $orders     = OrderHeader::where('status','shipped')->whereIn('courier_id',["jne","jne_trucking","jne_yes"])->with('details')->orderBy("order_header_id","ASC")->paginate(50);
        if(count($orders) > 0){
            // return IndexRes::resultData(200,['message' => 'no data'],[]);
            foreach($orders as $order){

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


                

                            echo $order->order_header_id.' => '.$order->awb_no.' => '.$res["cnote"]["pod_status"];
                            echo "<br>";
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


                

                            echo $order->order_header_id.' => '.$order->awb_no.' => '.$res["cnote"]["pod_status"];
                            echo "<br>";

                    }else{
                        echo "<br>";
                        echo $order->order_header_id.' => '.json_encode($res);
                        echo "<br>";
                    }
                    usleep(25000);
                }
 
            }
        }else{
            return IndexRes::resultData(200,['message' => 'no data'],[]);
        }
    }
}