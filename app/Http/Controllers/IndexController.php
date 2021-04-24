<?php
namespace App\Http\Controllers;  


use Ramsey\Uuid\Uuid;
use App\Res\IndexRes;

use App\Models\Warehouse\Honeywell\PutSku;
use App\Models\Sku;
use App\Models\OrderHeader;
use App\Models\PoHeader;
use App\Http\Controllers\ApiLogController as ApiLog;

use App\Models\TripDetails;
use App\Models\TripHeader;
use App\Models\TripStatusTracking;
use Carbon\Carbon;
use File;

use App\Models\OrderTypeMaster;

class IndexController extends Controller
{
	
/* 
error status code
	200	success
	404	Not Found (page or other resource doesn’t exist)
	401	Not authorized (not logged in)
	403	Logged in but access to requested area is forbidden
	400	Bad request (something wrong with URL or parameters)
	422	Unprocessable Entity (validation failed)
	500	General server error
*/

    public function __construct(){
         
    }

	public function index(){
		return response()
		->json(['status'=>200 ,'datas' => ['message' => 'API Custom 8commerce'], 'errors' => []])
		->setStatusCode(200)
		->withHeaders(['Content-Type' => 'application/json',]);
	}
	
	public function pagenotfound(){
		return response()
		->json(['status'=>404 ,'datas' => [], 'errors' => ['message' => 'Not Found!']])
		->setStatusCode(404)
		->withHeaders(['Content-Type' => 'application/json',]);
	 
	}
	
	public function servererror(){
		return response()
		->json(['status'=>500 ,'datas' => [], 'errors' => ['message' => 'General server error!']])
		->setStatusCode(500)
		->withHeaders(['Content-Type' => 'application/json',]);
	} 
	
	public function coba(){
		$src = "/var/www/html/Version_1/public/LuxasiaFile/so/Archive/SALES_IB_01032021_0100.TXT";  // source folder or file
		$dest = "/home/Aditya/SALES_IB_01032021_0100.TXT";   // destination folder or file        
		if(!copy($src,$dest)){
			echo "File can't be copied! \n"; 
		}else{
			echo "File has been copied! \n";
		}
	}


	public function tripCreateBright(){
		$today 			= new Carbon();
		if($today->dayOfWeek == Carbon::SUNDAY){
			ApiLog::insertLog('Custome Server',$this->company_id,'', 'SUCCESS' , '', '\App\Console\Commands\Bright\BrightAutoCreateTrip');
		}else{
			if($today->dayOfWeek == Carbon::MONDAY){
				$newDateTime 	= Carbon::now()->addDays(-2)->format('Y-m-d');
			}else{
				$newDateTime 	= Carbon::now()->addDays(-1)->format('Y-m-d');
			}

			$models	= OrderHeader::where([['trip_id' , 0],['create_by','api'],["company_id","ECBRIGHT"]])->whereBetween('create_time', [$newDateTime." 00:00:00", $newDateTime." 23:59:59"])->whereIn('status', ["new","packing","packing_complete"])->get();

			if(count($models) > 0){
				foreach($models as $order){
					$order_header_id		= $order->order_header_id;
					$courier_id				= $order->courier_id;
					$company_id				= $order->company_id;
					$fulfillment_center_id	= $order->fulfillment_center_id;
					
					$checkTrip			= TripHeader::with(['tripDetail'])->where([['status', 'draft'],["courier_id",$courier_id],["company_id",$company_id],["fulfillment_center_id",$fulfillment_center_id],["trip_date", Carbon::now()->format('Y-m-d')]])->first();


					if($checkTrip){
						// add on trip detail
						$this->addTripDetail($checkTrip,$order);
					}else{
						// create new data trip
						$this->createTrip($order);

					}
					usleep(25000);
				}
			}else{
				ApiLog::insertLog('Custome Server',$this->company_id,'', 'SUCCESS' , '', '\App\Console\Commands\Bright\BrightAutoCreateTrip');
			}
		}
	} 

	private function addTripDetail($trip,$order){
		
		$courier_id				= $order->courier_id;
		$company_id				= $order->company_id;
		$fulfillment_center_id	= $order->fulfillment_center_id;
		try {
			$trip_id	= $trip->trip_id;

			$tripDetail						= new TripDetails;
			$tripDetail->trip_id			= $trip_id;
			$tripDetail->status				= 'draft';
			$tripDetail->order_header_id	= $order->order_header_id;
			$tripDetail->save();

			OrderHeader::where('order_header_id', $order->order_header_id)->update(array(
				"trip_id" 	  =>  $trip_id
			));

		} catch (\Exception $e) {
			ApiLog::sendEmail('ECBRIGHT createTrip Fail', json_encode($e),array());
		}
	}

	private function createTrip($order){
		$courier_id				= $order->courier_id;
		$company_id				= $order->company_id;
		$fulfillment_center_id	= $order->fulfillment_center_id;

		try {
			$tripHeader								= new TripHeader;
			$tripHeader->status						= 'draft';
			$tripHeader->trip_date					= Carbon::now()->format('Y-m-d');
			$tripHeader->courier_id					= $courier_id;
			$tripHeader->username					= 'api';
			$tripHeader->company_id					= $company_id;
			$tripHeader->fulfillment_center_id		= $fulfillment_center_id;


			$getLastTripHeader	= TripHeader::where([["courier_id",$courier_id],["company_id",$company_id],["fulfillment_center_id",$fulfillment_center_id]])->whereNotNull('km_finish')->latest('trip_id')->first();
			if($getLastTripHeader){
				$tripHeader->driver_name	= $getLastTripHeader->driver_name;
				$tripHeader->vehicle_no		= $getLastTripHeader->vehicle_no;
				$tripHeader->km_start		= $getLastTripHeader->km_finish;
			}

			$tripHeader->save();

			$trip_id	= $tripHeader->trip_id;

			$tripDetail						= new TripDetails;
			$tripDetail->trip_id			= $trip_id;
			$tripDetail->status				= 'draft';
			$tripDetail->order_header_id	= $order->order_header_id;
			$tripDetail->save();

			$triTracking					= new TripStatusTracking;
			$triTracking->trip_id			= $trip_id;
			$triTracking->status			= 'draft';
			$triTracking->system			= 'oms';
			$triTracking->create_by			= 'api';
			$triTracking->save();


		} catch (\Exception $e) {
			ApiLog::sendEmail('ECBRIGHT createTrip Fail', json_encode($e),array());
		}
	}
	
}
