<?php
namespace App\Http\Controllers\Xstore; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Response,Auth,Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use App\Mail\OmsEmailNotification;
use Illuminate\Support\Facades\File;
use PDF;
use PHPExcel; 
use PHPExcel_IOFactory;

use App\Http\Controllers\ApiLogController as ApiLog;
use App\Res\IndexRes;
 
use App\Models\ServerCustomeTransaction;
use App\Models\TripHeader;
use App\Models\TripDetails;
use App\Models\TripStatusTracking;
use App\Models\OrderHeader;
use App\Models\OrderDetail;
use App\Models\OrderStatusTracking;
use App\Models\Store;
use Predis\Response\Status;

class IndexController extends Controller
{
    

    public function __construct(){
        
    }


	public function index(){
        return IndexRes::resultData(200,['message' => 'API Custome 8commerce for Xstore'],[]);
    }

    public function login(Request $request){
        $check  = Store::where([['store_code' , $request->store_code],['company_id', $request->company_id],['pic_email', $request->pic_email]])->first();
        if($check){
            return IndexRes::resultData(200,['message' => 'Login Successfully', 'data' => $check->store_id],[]);
        }else{
            return IndexRes::resultData(422,[], ['message' => 'Data Not Found']);
        }
    }
    
	
	public function SearchOrder(Request $request , $storeId){
        $sort_field         = "order_header_id";
        $sort_type          = "DESC";
        $perPage        	= $request->per_page;
        $order_no     		= $request->order_no;
        $check  = Store::find($storeId);
        if($check){
            $orders = OrderHeader::with(['details'])->whereHas('details', function ($query) {
                return $query->where('qty_delivered', '=', NULL);
            })->where([['dest_name' , $check->store_code],['company_id', $check->company_id]])->whereIn('status', ['shipped','delivered'])->orderBy($sort_field,$sort_type);
            if ($order_no) {
                $like = "%{$order_no}%";
                $orders = $orders->where('order_no', 'LIKE', $like);
            }
    
            $res = $orders->paginate($perPage);
    
            return IndexRes::resultData(200,$res,[]);
        }else{
            return IndexRes::resultData(500,[], ['message' => 'Data Not Found']);
        }
    }

	
	public function DetailOrder($storeId,$orderId){
        $check  = Store::find($storeId);
        if($check){
            $destName   = $check->store_code;
            $models  =  OrderHeader::with(['details','statusTracking','company','fulfillmentCenter'])->where([['order_header_id' , $orderId],['dest_name' , $destName]])->first();
            if($models){
                return IndexRes::resultData(200,$models,[]);
            }else{
                return IndexRes::resultData(404,[], ['message' => 'Data Not Found']);
            }
        }else{
            return IndexRes::resultData(500,[], ['message' => 'Data Not Found']);
        }
    }
	
	public function SearchDetailOrder(Request $request , $storeId,$orderId){
        $sort_field         = "order_detail_id";
        $sort_type          = "DESC";
        $perPage        	= $request->per_page;
        $sku_code     		= $request->sku_code;
        $sku_description    = $request->sku_description;
        $check  = Store::find($storeId);
        if($check){
            $destName   = $check->store_code;
            
            $orders = OrderDetail::with(['header'])->whereHas('header', function ($query)  use($destName){
                return $query->where('dest_name', '=', $destName);
            })->where([['order_header_id' , $orderId],['qty_delivered', NULL]])->orderBy($sort_field,$sort_type);
            if ($sku_code) {
                $like = "%{$sku_code}%";
                $orders = $orders->where('sku_code', 'LIKE', $like);
            }
            if ($sku_description) {
                $like = "%{$sku_description}%";
                $orders = $orders->where('sku_description', 'LIKE', $like);
            }
    
            $res = $orders->paginate($perPage);
    
            return IndexRes::resultData(200,$res,[]);
        }else{
            return IndexRes::resultData(500,[], ['message' => 'Data Not Found']);
        }
    }

    public function updateQtyDelivered(Request $request,$storeId,$idOrderDetail){
        $this->validate($request, [
            'qty_delivered' 		=> 'required|numeric|min:0',
        ]);

        $check  = Store::find($storeId);
        if($check){
            $destName   = $check->store_code;
            $models  = OrderDetail::with(['header'])->whereHas('header', function ($query)  use($destName){
                return $query->where('dest_name', '=', $destName);
            })->where([['order_detail_id' , $idOrderDetail]])->first();
            if($models){
                $remarks    = "has received by ".$destName;
                if($this->IsNullOrEmptyString($models->qty_delivered)){
                    $models->qty_delivered   = $request->qty_delivered;
                    $models->remarks         = $remarks;
                    $models->save();

                    return IndexRes::resultData(200,['message' => 'Received Successfully', 'data' => []],[]);
                }else{
                    return IndexRes::resultData(422,[], ['message' => ["qty_delivered" => ["Qty cannot change."]]]);
                }
            }else{
                return IndexRes::resultData(404,[], ['message' => 'Data Not Found']);
            }
        }else{
            return IndexRes::resultData(500,[], ['message' => 'Data Not Found']);
        }
    }

    // Function for basic field validation (present and neither empty nor only white space
    private function IsNullOrEmptyString($str){
        return (!isset($str) || trim($str) === '');
    }
}