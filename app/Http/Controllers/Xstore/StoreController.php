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
 
use App\Models\OrderHeader;
use App\Models\OrderDetail;
use App\Models\OrderStatusTracking;
use App\Models\Store;

class StoreController extends Controller
{

    public function __construct(){
		$this->middleware('jwt.store');
    }

    public function profile(Request $request){
        $auth	= $request->auth;
        return IndexRes::resultData(200,$auth,[]);
    }

    public function orders(Request $request){
        $auth	= $request->auth;
        $sort_field         = "order_header_id";
        $sort_type          = "DESC";
        $perPage        	= 10;
        $order_no     		= $request->order_no;

        $query  = OrderHeader::with(['details','statusTracking'])->whereHas('details', function($query){
            $query->whereNotNull("qty_ship")->orWhere("qty_ship",">",0);
        })->where([["dest_name" , $auth->store_code],["status", "shipped"]])->orderBy($sort_field,$sort_type);
        
        if ($order_no) {
            $like = "%{$order_no}%";
            $query = $query->where('order_no', 'LIKE', $like);
        }

        $res = $query->paginate($perPage);
        return IndexRes::resultData(200,$res,[]);
    }

    public function orderDetails(Request $request,$orderHeaderId){
        $auth	= $request->auth;
        $sort_field         = "order_detail_id";
        $sort_type          = "DESC";
        $store_code   		= $auth->store_code;
        $search     		= $request->search;

        $query  = OrderDetail::whereHas('header', function($query) use ($store_code,$orderHeaderId){
            $query->where([["dest_name" , $store_code],["status", "shipped"]]);
        })->where([["order_header_id" , $orderHeaderId],["qty_ship",">",0]])->whereNotNull("qty_ship")->orderBy($sort_field,$sort_type);
        
        if ($search) {
            $like = "%{$search}%";
            $query = $query->where('sku_code', 'LIKE', $like)->orWhere('special_packaging', 'LIKE', $like);
        }

        $res = $query->get();
        return IndexRes::resultData(200,$res,[]);
    }

    public function receivedOrderDetails(Request $request,$orderHeaderId,$orderDetailId){
        $auth	            = $request->auth;
        $store_code   		= $auth->store_code;
        
        
        $validator = Validator::make(
            $request->all(),
            array(
                'qty_delivered'	        => 'required|numeric',
                'status'	            => 'required|max:45|in:rejected,delivered',
            )
        );

        if($validator->fails()){
            return IndexRes::resultData(422,[],["messages" => $validator->errors()]);
        }else{
            $check  = OrderDetail::whereHas('header', function($query) use ($store_code,$orderHeaderId){
                $query->where([["dest_name" , $store_code],["status", "shipped"]]);
            })->where([["order_header_id" , $orderHeaderId],["qty_ship",">",0]])->whereNotNull(["qty_ship","qty_delivered"])->whereNotIn('order_detail_id', [$orderDetailId])->get()->count();
            if($check > 0){
                try{
                    OrderDetail::where([["order_header_id" , $orderHeaderId],["order_detail_id" , $orderDetailId]])
                        ->update(['qty_delivered' => $request->qty_delivered , "status" => "delivered"]);
                    $res    = ["messages" =>'Successfully Update'];
                    return IndexRes::resultData(200,$res,[]);
                }catch(\Exception $e){
                    return IndexRes::resultData(200,[],["message" => $e->getMessage()]);
                }
            }else{

                try{
                    OrderDetail::where([["order_header_id" , $orderHeaderId],["order_detail_id" , $orderDetailId]])
                        ->update(['qty_delivered' => $request->qty_delivered , "status" => "delivered"]);
                    $res    = ["messages" =>'Successfully Update'];
                    
                    $order  = OrderHeader::where('order_header_id',$orderHeaderId)->first();
                    $order->status = "delivered";
                    $order->save();
                    
                    OrderStatusTracking::insert([
                        "order_no"      => $order->order_no,
                        "status"        => 'delivered',
                        "system"        => 'oms',
                        "remarks"       => 'delivered by '.$auth->store_code." ".$auth->pic_name,
                        "create_by"     => $auth->pic_name,
                        "order_header_id"   => $order->order_header_id
                    ]);
                    return IndexRes::resultData(200,$res,[]);
                }catch(\Exception $e){
                    return IndexRes::resultData(200,[],["message" => $e->getMessage()]);
                }
            }
        }
    }

}