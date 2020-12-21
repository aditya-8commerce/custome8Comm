<?php
namespace App\Http\Controllers\Xpress; 

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
use Predis\Response\Status;

class IndexController extends Controller
{
    

    public function __construct(){
        
    }
    

	public function index(){
        return IndexRes::resultData(200,['message' => 'API Custome 8commerce for Xpress'],[]);
    }

	public function login(Request $request){
        $check  = TripHeader::with(['tripDetail.order','fulfillmentCenter','company'])->where([['trip_id' , $request->trip_id],['vehicle_no', $request->vehicle_no]])->whereIn('status',['new','started'])->first();
        if($check){
            if($check->status == 'new'){
                $check->status      = 'started';
                $check->start_time  = Carbon::now();
                $check->save();

                if(count($check->tripDetail) > 0){
                    foreach($check->tripDetail as $detail){
                        $detail->status     =  $check->status;
                        $detail->start_time =  Carbon::now();
                        $detail->save();
                    }
                }

                $tripTracking = new TripStatusTracking;
                $tripTracking->status       = $check->status;
                $tripTracking->system       = 'oms';
				$tripTracking->remarks      = "";
				$tripTracking->create_by    = $check->driver_name;
				$tripTracking->trip_id      = $check->trip_id;
				$tripTracking->save();
            }
             return IndexRes::resultData(200,['message' => 'Login Successfully', 'data' => $request->trip_id],[]);
        }else{
            return IndexRes::resultData(422,[], ['message' => 'Data Not Found']);
        }
    }


	public function detailTripOrder($tripDetailId){
		$query  = TripDetails::with(['tripHeader','order.details'])->where('trip_detail_id' , $tripDetailId)->whereIn('status',['new','started'])->first();
		if($query){
			return IndexRes::resultData(200,$query,[]);
		}else{
			return IndexRes::resultData(422,[],["message" =>'data not found']);
		}
	}
	
	public function SearchOrder(Request $request , $tripId){
        $sort_field         = "trip_detail_id";
        $sort_type          = "DESC";
        $perPage        	= $request->per_page;
        $order_no     		= $request->order_no;
        $dest_name     		= $request->dest_name;

        $query  = TripDetails::with(['tripHeader','order.details'])->where('trip_id' , $tripId)->whereIn('status',['new','started'])->orderBy($sort_field,$sort_type);

        if ($order_no) {
            $like = "%{$order_no}%";
            $query = $query->whereHas('order', function($query) use ($like){
                $query->where('order_no', 'LIKE', $like);
            });
        }
		
        if ($dest_name) {
            $like = "%{$dest_name}%";
            $query = $query->whereHas('order', function($query) use ($like){
                $query->where('dest_name', 'LIKE', $like);
            });
        }

        $res = $query->paginate($perPage);

        return IndexRes::resultData(200,$res,[]);
    }

	public function updateStatus(Request $request , $tripDetailId){
        $validator = Validator::make(
            $request->all(),
            array(
                'remarks'	            => 'required',
                'status'				=> 'required|in:finished,failed,rejected',
            )
        );

            if($validator->fails()){
                return IndexRes::resultData(422,[],["message" => $validator->errors()]);
            }else{
                $check  = TripDetails::with(['tripHeader','order.details'])->where('trip_detail_id' , $tripDetailId)->first();
                if($check){
                    if($request->status == 'finished'){
                        $check->status          = $request->status;
                        $check->remarks         = $request->remarks;
                        $check->pod             = $request->pod;
                        $check->longitude       = $request->longitude;
                        $check->latitude        = $request->latitude;
                        $check->finish_time     = Carbon::now();
                        $check->save();

                        OrderHeader::where('order_header_id',$check->order_header_id)->update([
                            'status'        => 'delivered',
                            'update_time'   => Carbon::now()
                        ]);

                        OrderStatusTracking::insert([
                            "order_no"      => $check->order->order_no,
                            "status"        => 'delivered',
                            "system"        => 'oms',
                            "remarks"       => $request->remarks.' delivered by '.$check->tripHeader->driver_name.' - '.$check->tripHeader->vehicle_no,
                            "create_by"     => $check->tripHeader->driver_name,
                            "order_header_id"   => $check->order_header_id
                        ]);
                        
                        OrderDetail::where('order_header_id',$check->order_header_id)->update([
                            'status'        => 'delivered',
                            'update_time'   => Carbon::now()
                        ]);
                        
                        return IndexRes::resultData(200,['message' => 'update successfully'],[]);
                    }elseif($request->status == 'failed'){
                        $check->status          = $request->status;
                        $check->remarks         = $request->remarks;
                        $check->pod             = $request->pod;
                        $check->longitude       = $request->longitude;
                        $check->latitude        = $request->latitude;
                        $check->finish_time     = Carbon::now();
                        $check->save();

                        OrderHeader::where('order_header_id',$check->order_header_id)->update([
                            'trip_id'       => 0,
                            'update_time'   => Carbon::now()
                        ]);
                       
                        
                        OrderStatusTracking::insert([
                            "order_no"      => $check->order->order_no,
                            "status"        => 'shipped',
                            "system"        => 'oms',
                            "remarks"       => $request->remarks.' failed by '.$check->tripHeader->driver_name.' - '.$check->tripHeader->vehicle_no,
                            "create_by"     => $check->tripHeader->driver_name,
                            "order_header_id"   => $check->order_header_id
                        ]);

                        return IndexRes::resultData(200,['message' => 'update successfully'],[]);

                    }elseif($request->status == 'rejected'){

                        $check->status          = $request->status;
                        $check->remarks         = $request->remarks;
                        $check->pod             = $request->pod;
                        $check->longitude       = $request->longitude;
                        $check->latitude        = $request->latitude;
                        $check->finish_time     = Carbon::now();
                        $check->save();
                        
                        OrderHeader::where('order_header_id',$check->order_header_id)->update([
                            'status'        => 'return-reject',
                            'update_time'   => Carbon::now()
                        ]);
                       
                        
                        OrderStatusTracking::insert([
                            "order_no"      => $check->order->order_no,
                            "status"        => 'return-reject',
                            "system"        => 'oms',
                            "remarks"       => $request->remarks.' failed by '.$check->tripHeader->driver_name.' - '.$check->tripHeader->vehicle_no,
                            "create_by"     => $check->tripHeader->driver_name,
                            "order_header_id"   => $check->order_header_id
                        ]);

                        OrderDetail::where('order_header_id',$check->order_header_id)->update([
                            'status'        => 'return-reject',
                            'update_time'   => Carbon::now()
                        ]);
                        return IndexRes::resultData(200,['message' => 'update successfully'],[]);
                    }else{
                        return IndexRes::resultData(422,[],["message" =>'status not found']);
                    }
                }else{
                    return IndexRes::resultData(422,[],["message" =>'data not found']);
                }
            }
    }

}