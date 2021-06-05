<?php
 
namespace App\Console\Commands\Luxasia;
 
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

use App\Models\ServerCustomeTransaction;
use App\Models\Sku;
use App\Models\SkuMarketplace;
use App\Models\PoHeader;
use App\Models\PoDetail;
use App\Models\PoImportCsv;
use App\Models\PoStatusTracking;
use App\Models\OrderBuffer;
use App\Models\PoBuffer;
use App\Models\CompanyFullfilmentCenter;
use App\Models\Inventory;
use App\Models\FulfillmentCenter;
use App\Models\OrderHeader;
use App\Models\Warehouse\Honeywell\PutSku;
use App\Models\Warehouse\Honeywell\InboundAsn;


class LuxasiaPoSync extends Command
{
    public $company_id              = 'ECLUXASIA';
    public $fulfillment_center_id   = 'WHCPT01';
   /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'LuxasiaPoSync:sender';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Luxasia Sync PO to SCI';
 
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
     * @return mixed
     */
    public function handle()
    {
        set_time_limit(0);
        $directory = base_path('public/LuxasiaFile/replenishment');
		$files = File::allFiles($directory); 
		if(count($files) > 0){
			foreach($files as $path){
				$file 		= pathinfo($path);
                $fileName	= $file['basename'];
                
                $this->checkPOFile($file,$fileName);
                
                sleep(1);
			}
		}else{
            ApiLog::insertLog('Custome Server',$this->company_id,'', 'SUCCESS' , '', '\App\Console\Commands\Luxasia\LuxasiaPoSync');
        }
        
    }

    private function checkPOFile($file,$name){
        if(strtolower($file['extension']) == 'txt'){
            $fp     	= fopen($file['dirname'].'/'.$name,'r');
            $string 	= '';
            $x      	= 0;
            
         
             $datasRows  = array();
             $header     = array();
             $x          = 0;
             $uniqid     = uniqid();
             $all_rows   = [];

             while (!feof($fp)){
                 $current_line   = fgets ($fp);
                 $explode        = explode("|",$current_line);
                 $string         .= $current_line.'\n';

                 if($x == 0){
                     $header     = $explode;
                 }else{
                     $dd = [];
                     for($f=0;$f<count($header);$f++){
                         $headerString = $this->cleanString($header[$f]);
                         if ( ! isset($explode[$f])) {
                             $explode[$f] = null;
                         }
                         $dd[$headerString] = $this->cleanString($explode[$f]);
                     }
                    
                    if(empty(@$dd["PO_NUMBER"])) continue;

                    $this->insertTemporary($uniqid,$dd);
                    $datasRows[] = $dd;
                 }

                 $x++;
                 usleep(25000);
             }

            fclose($fp);
            
             $temporary     = PoImportCsv::select('uuid', 'po_type', 'po_no')->where('uuid',$uniqid)->groupBy('uuid', 'po_type', 'po_no')->get();
             if(count($temporary) > 0){
                foreach($temporary as $t){
                    $datas      = $this->poDatas($t,$datasRows);
                    $datas_sci  = $this->poStructure($t);

                    $all_rows[] = ['company_id' => $this->company_id , 'po_no' => $t->po_no , 'type' => 'row' , 'seq' => 1 , 'channel' => 'api' , 'datas' => $datas , 'datas_sci' => $datas_sci , 'create_time' => date('Y-m-d H:i:s')] ;
					
                }

                
             }


            
            if(count($all_rows) > 0){
                foreach($all_rows as $row){
                    $res = $this->insertPoData($row);
					if($res["status"] != "200"){
                           
                        $subject    = 'Luxasia Replenishment Integration';
                        $content    = $current_line;
                        ApiLog::sendEmail($subject,$content);
                     }
                }
            }
			
            
			$uuid	= Uuid::uuid4()->toString();
            ApiLog::insertOrderBuffer($this->company_id,$uuid,"", 'po',1,"custome" , $string,json_encode($all_rows));
			
            unlink($file['dirname'].'/'.$name);
			
        }else{
            $subject    = 'Luxasia Replenishment Integration';
            $content    = 'Extension file not allowed';
            ApiLog::sendEmail($subject,$content);
        }

    }

    private function poDatas($buffer , $datas){
        $res    = [];
        foreach($datas as $data){
            if($data["PO_NUMBER"] == $buffer->po_no){
                $res[] = $data;
            }
        }

        return $res;
    }
    
    private function poStructure($data){
        $gets           = PoImportCsv::where([['uuid',$data->uuid],['po_no',$data->po_no]])->get();
        $po             = PoImportCsv::where([['uuid',$data->uuid],['po_no',$data->po_no]])->first();
        $fulfillment    = FulfillmentCenter::where('fulfillment_center_id',$this->fulfillment_center_id)->first();
        $details        = [];
        $res['header']  = [
            'po_type' => $po->po_type , 'po_no' => $po->po_no , 'crossdock_no' => $po->crossdock_no , 
            'po_date' => $po->po_date , 'company_id' => $this->company_id , 'eta_date' => $po->eta_date,
            'vehicle_no' => $po->vehicle_no , 'driver_name' => $po->driver_name , 'status' => 'draft' ,
            'create_by' => 'api', 'dest_name' => $fulfillment->name , 'dest_address1' => $fulfillment->address,
            'dest_address2' => $fulfillment->address2 , 'dest_province' => $fulfillment->province , 'dest_city' => $fulfillment->city , 
            'dest_area' => $fulfillment->area , 'dest_sub_area' => $fulfillment->sub_area , 'dest_village' => $fulfillment->village ,
            'dest_remarks' => '-', 'dest_postal_code' => $fulfillment->postal_code ,'dest_country' => 'Indonesia' , 'ori_name' => $po->ori_name ,  'ori_address1' => $po->ori_address1 ,
            'ori_address2' => $po->ori_address2 , 'ori_province' => $po->ori_province , 'ori_city' => $po->ori_city ,
            'ori_area' => $po->ori_area , 'ori_sub_area' => $po->ori_sub_area , 'ori_postal_code' => $po->ori_postal_code ,
            'ori_village' => $po->ori_village , 'ori_remarks' => $po->ori_remarks , 'fulfillment_center_id' =>$this->fulfillment_center_id , 
            'ori_country' => $po->ori_country
                        ];
        foreach($gets as $get){
            $sku        = Sku::where('sku_code',$get->sku_code)->first();
            if($sku){
				// $amount_order =	$get->qty_order * $sku->price;
				
                $details[]  = ['sku_code' => $sku->sku_code , 'sku_description' => $sku->sku_description ,'qty_order' => $get->qty_order , 'price' => 0 , 'amount_order' => 0 , 'remarks' => $get->sku_remarks , 'status' => ''];
            }else{
                $details[]  = ['sku_code' => $get->sku_code , 'sku_description' => 'sku not found' ,'qty_order' => $get->qty_order , 'price' => 0 , 'amount_order' => 0 , 'remarks' => $get->sku_remarks , 'status' => ''];
                $subject    = 'Luxasia Replenishment Integration';
                $content    = 'sku not found .<br><br>'.json_encode($get);
                ApiLog::sendEmail($subject,$content);
            }
        }

        $res['details'] = $details;

        return $res; 

    }

    private function insertTemporary($uniqid,$array){
        $po                     = new PoImportCsv;
        $po->uuid   			= $uniqid;
        $po->po_type 	        = 'normal';
        $po->po_no 			    = strtoupper($array['PO_NUMBER']);
        $po->crossdock_no 	    = '';
        $po->po_date 	        = date('Y-m-d');
        $po->eta_date 	        = date('Y-m-d', strtotime(date('Y-m-d'). ' + 2 days'));
        $po->vehicle_no 	    = '-';
        $po->driver_name 	    = '-';
        $po->ori_name    	    = strtoupper($array['VENDOR_NAME']);
        $po->ori_address1 	    = strtoupper($array['VENDOR_ADDRESS']);
        $po->ori_address2  	    = '-';
        $po->ori_province       = '-';
        $po->ori_city           = '-';
        $po->ori_area           = '-';
        $po->ori_sub_area       = '-';
        $po->ori_postal_code    = '-';
        $po->ori_village        = '-';
        $po->ori_remarks        = '-';
        $po->ori_country        = '-';
        $po->sku_code           = strtoupper($array['ItemCode']);
        $po->qty_order          = $this->stringToInterger2($array['Qty']);
        $po->sku_remarks        = '-';
        $po->save();
    }


    private function insertPoData($datas){
        $check      = PoHeader::where([['company_id', '=' , $datas["company_id"]], ['po_no', '=' , $datas["po_no"]], ["status","<>","cancelled"]])->first();
        if($check) {
           $res = ['status' => 402 , 'message' =>[], 'errors' => ['message' => $check]];	
        }else{
            $po                         = new PoHeader;
            $po->po_type                = $datas["datas_sci"]["header"]["po_type"];
            $po->po_no                  = $datas["datas_sci"]["header"]["po_no"];
            $po->crossdock_no           = $datas["datas_sci"]["header"]["crossdock_no"];
            $po->po_date                = $datas["datas_sci"]["header"]["po_date"];
            $po->company_id             = $datas["datas_sci"]["header"]["company_id"];
            $po->eta_date               = $datas["datas_sci"]["header"]["eta_date"];
            $po->vehicle_no             = $datas["datas_sci"]["header"]["vehicle_no"];
            $po->driver_name            = $datas["datas_sci"]["header"]["driver_name"];
            $po->status                 = $datas["datas_sci"]["header"]["status"];
            $po->create_by              = $datas["datas_sci"]["header"]["create_by"];
            $po->dest_name              = $datas["datas_sci"]["header"]["dest_name"];
            $po->dest_address1          = $datas["datas_sci"]["header"]["dest_address1"];
            $po->dest_address2          = $datas["datas_sci"]["header"]["dest_address2"];
            $po->dest_province          = $datas["datas_sci"]["header"]["dest_province"];
            $po->dest_city              = $datas["datas_sci"]["header"]["dest_city"];
            $po->dest_area              = $datas["datas_sci"]["header"]["dest_area"];
            $po->dest_sub_area          = $datas["datas_sci"]["header"]["dest_sub_area"];
            $po->dest_postal_code       = $datas["datas_sci"]["header"]["dest_postal_code"];
            $po->dest_village           = $datas["datas_sci"]["header"]["dest_village"];
            $po->dest_remarks           = $datas["datas_sci"]["header"]["dest_remarks"];
            $po->ori_name               = $datas["datas_sci"]["header"]["ori_name"];
            $po->ori_address1           = $datas["datas_sci"]["header"]["ori_address1"];
            $po->ori_address2           = $datas["datas_sci"]["header"]["ori_address2"];
            $po->ori_province           = $datas["datas_sci"]["header"]["ori_province"];
            $po->ori_city               = $datas["datas_sci"]["header"]["ori_city"];
            $po->ori_area               = $datas["datas_sci"]["header"]["ori_area"];
            $po->ori_sub_area           = $datas["datas_sci"]["header"]["ori_sub_area"];
            $po->ori_postal_code        = $datas["datas_sci"]["header"]["ori_postal_code"];
            $po->ori_village            = $datas["datas_sci"]["header"]["ori_village"];
            $po->ori_remarks            = $datas["datas_sci"]["header"]["ori_remarks"];
            $po->fulfillment_center_id  = $datas["datas_sci"]["header"]["fulfillment_center_id"];
            $po->dest_country           = $datas["datas_sci"]["header"]["dest_country"];
            $po->ori_country            = $datas["datas_sci"]["header"]["ori_country"];
            $po->save();
            
            $id = $po->po_header_id;

            foreach($datas["datas_sci"]["details"] as $detail){
                $poDetail                   = new PoDetail;
                $poDetail->po_header_id     = $id;
                $poDetail->sku_code         = $detail["sku_code"];
                $poDetail->sku_description  = $detail["sku_description"];
                $poDetail->qty_order        = $detail["qty_order"];
                $poDetail->price            = $detail["price"];
                $poDetail->amount_order     = $detail["amount_order"];
                $poDetail->status           = $detail["status"];
                $poDetail->remarks          = $detail["remarks"];
                $poDetail->save();
            }
			
			
            $poTracking                   = new PoStatusTracking;
            $poTracking->po_header_id     = $id;
            $poTracking->po_no            = $datas["datas_sci"]["header"]["po_no"];
            $poTracking->status           = $datas["datas_sci"]["header"]["status"];
            $poTracking->system           = 'OMS';
            $poTracking->remarks          = '';
            $poTracking->create_by        = 'API';
            $poTracking->save();
			
			//  $wms = new InboundAsn;
            //  $result = $wms->index($po);
            //  if($result["Response"]["return"]["returnCode"] == "0000"){
            //     $po->status     = "new";
			// 	$po->save();
				
			// 	$poTracking                   = new PoStatusTracking;
			// 	$poTracking->po_header_id     = $id;
			// 	$poTracking->po_no            = $datas["datas_sci"]["header"]["po_no"];
			// 	$poTracking->status           = "new";
			// 	$poTracking->system           = 'OMS';
			// 	$poTracking->remarks          = '';
			// 	$poTracking->create_by        = 'API';
			// 	$poTracking->save();
				
			// 	$res = ['status' => 200 , 'message' =>['message' => $result["Response"]["return"]["returnDesc"]], 'errors' => []];
            //  }else{
			// 	$subject    = 'Luxasia Replenishment Integration';
            //     $content    = 'Data not insert to WMS.<br><br>'.json_encode($datas);
            //     ApiLog::sendEmail($subject,$content);
				
			// 	$res = ['status' => 402 , 'message' =>[], 'errors' => $result];
            // }
	        $res = ['status' => 200 , 'message' =>['message' =>"ok"], 'errors' => []];
			$this->insertPoBuffer($datas);
		}
		return $res;
    }

 
    
    private function insertPoBuffer($datas){
        $check      = PoBuffer::where([['company_id' , $datas["company_id"]], ['po_no' , $datas["po_no"]],["seq","1"]])->first();
        if(!$check) {
            $po             = new PoBuffer;
            $po->company_id = $datas["company_id"];
            $po->po_no      = $datas["po_no"];
            $po->type       = $datas["type"];
            $po->seq        = $datas["seq"];
            $po->channel    = $datas["channel"];
            $po->datas      = json_encode($datas["datas"]);
            $po->datas_sci  = json_encode($datas["datas_sci"]);
            $po->create_time= $datas["create_time"];
            $po->save();            
        }
    }
    
	private function stringToInterger($string){
		return preg_replace('/[^0-9]/', '', $string);
    }
    
	private function stringToInterger2($string){
		$explode = explode(".", $string);
		return $explode[0];
    }
    
    private function cleanString($string){
        $string = trim(preg_replace('/\s+/', ' ', $string));
        return $string;
    }
}