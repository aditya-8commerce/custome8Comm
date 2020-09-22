<?php
namespace App\Http\Controllers; 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\OmsEmailNotification;

use App\Models\ApiLog;
use App\Models\OrderHeader;
use App\Models\OrderStatusTracking;
use App\Models\Courier;
use App\Models\OrderBuffer;
use App\Models\Log;
use App\Models\Couriers\Wahana;
use App\Models\Couriers\Tiki;
use App\Models\Couriers\Sap;
use App\Models\Couriers\Jnt;
use App\Models\Couriers\Jne;
use App\Models\Couriers\SiCepat;
use App\Models\Couriers\PosIndonesia;
use App\Models\Warehouse\Honeywell;


class ApiLogController extends Controller
{
    public function __construct(){

    }

	public static function insertLog($hostname,$channel,$message,$level_name,$context,$url){
		$insert = [
            'instance'    => $hostname,
            'channel'     => $channel,
            'message'     => $message,
            'level'       => $level_name,
            'context'     => $context,
            'url'         => $url,
            'ip'          => app('request')->server('REMOTE_ADDR'),
            'user_agent'  => app('request')->server('HTTP_USER_AGENT')
        ];

        ApiLog::create($insert);
	}

	public static function insertOrderBuffer($company_id,$order_no,$shop_id,$type,$seq,$channel,$datas,$datas_sci){
		$insert = [
            'company_id'    => $company_id,
            'order_no'     	=> $order_no,
            'shop_id'     	=> $shop_id,
            'type'       	=> $type,
            'seq'     		=> $seq,
            'channel'       => $channel,
            'datas'         => $datas,
            'datas_sci'  	=> $datas_sci,
            'create_time'  	=> date('Y-m-d h:i:s')
        ];

        OrderBuffer::create($insert);
	}
	
    public static function sendEmail($subject,$content,$attachment=array()){
        $to = array('nugroho.aditya@8commerce.com','operation@8commerce.com','it@8commerce.com');
        // $to = array('nugroho.aditya@8commerce.com');
            Mail::to($to)->send(new OmsEmailNotification($subject,$content ,$attachment));
            try {
                return response()->json("Email Sent!");
            } catch (\Exception $e) {
                return response()->json($e->getMessage());
            }
    }
	
    public static function saveTracking($order_header_id,$order_no,$status,$system,$create_by,$remarks){
        $orderStatusTracking= new OrderStatusTracking;
        $orderStatusTracking->order_header_id	= $order_header_id;
        $orderStatusTracking->order_no			= $order_no;
        $orderStatusTracking->status			= $status;
        $orderStatusTracking->system			= $system;
        $orderStatusTracking->create_by			= $create_by;
        $orderStatusTracking->remarks			= $remarks;
        $orderStatusTracking->save();
    }
	
    public static function addErrorIssue($module,$status,$response,$message,$param1=''){
        $log = new Log;
        $log->module        	= $module;
        $log->status			= $status;
        $log->response			= $response;
        $log->message			= $message;
        $log->fixed 			= 0;
        $log->param1    		= $param1;
        $log->ip    		    = '127.0.0.1';
        $log->save();
    }
    
    public static function generateAwb($order_header_id){
        $model = OrderHeader::with('details', 'courier')->find($order_header_id);
        $checkCourier = Courier::where("courier_id",$model->courier_id)->first();
        if($checkCourier->type == "normal" && empty($model->awb_no)){
            switch(strtolower($model->courier_id)) {
                case 'wahana': $api = new Wahana; break;
                case 'tiki': $api = new Tiki; break;
                case 'sap': $api = new Sap; break;
                case 'j&t': $api = new Jnt; break;
                case 'jnt': $api = new Jnt; break;
                case 'jne': $api = new Jne; break;
                case 'jne_trucking': $api = new Jne; break;
                case 'jne_yes': $api = new Jne; break;
                case '8commerce': $api = new Jne; break;
                case 'sicepat': $api = new SiCepat; break;
                case 'sicepat_best': $api = new SiCepat; break;
                case 'sicepat_cargo': $api = new SiCepat; break;
                case '240': $api = new PosIndonesia; break;
                case '447': $api = new PosIndonesia; break;
                case 'PDG': $api = new PosIndonesia; break;
                case 'PVG': $api = new PosIndonesia; break;
            }
            
            $getAwb = $api->createOrder($model);
            if(!empty($getAwb["awb_no"])){
                $model->update(["awb_no" => $getAwb["awb_no"]]); 
            }else{
                $subject    = 'Error Get Awb';
                $content    = "order_no : ".$model->order_header_id."<br> error get awb : <br>".$getAwb["message"];
                $attachment = [];

                $this->saveTracking($model->order_header_id , $model->order_no , $model->status , $model->create_by , 'api' , 'get awb error => '.$getAwb["message"]);
                $this->sendEmail($subject,$content,$attachment);
            }
        }
        $warehouse = new Honeywell;
		$resWarehouse = $warehouse->putSO($model,'B2CO','RETAIL');
    }


	public static function wrongChar($data){
		$string = strtolower($data);
		$wrongChar = array('kota ','kab ', 'kab. ','kepulauan ', 'daerah ','kabupaten '); 
		$split = str_replace($wrongChar,'',$string);
		return $split;
    }

    
	
	public static function cleanString($string){
		$string = ApiLogController::IsNullOrEmptyString($string);
		$string = str_replace(array('[\', \']'), '', $string);
		$string = preg_replace('/\[.*\]/U', '', $string);
		$string = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', '-', $string);
		$string = htmlentities($string, ENT_COMPAT, 'utf-8');
		$string = preg_replace('/&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);/i', '\\1', $string );
		$string = preg_replace(array('/[^a-z0-9]/i', '/[-]+/') , ' ', $string); 
		$string = strtoupper(trim($string, ' ')); 
		if($string == false){
			return '-';  		
		}else{
			return $string;  			
		}
	}
	
	public static function IsNullOrEmptyString($str){
		if((!isset($str) || trim($str) === '')){ 
			return '-';
		}else{
			return $str	;
		}
    }

}
