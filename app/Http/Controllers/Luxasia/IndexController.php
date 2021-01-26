<?php
namespace App\Http\Controllers\Luxasia; 

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
use App\Models\CustomeBuffer;
use App\Models\Warehouse\Honeywell\PutSku;
use App\Models\Warehouse\Honeywell\InboundAsn;


class IndexController extends Controller
{
    public $company_id              = 'ECLUXASIA';
    public $fulfillment_center_id   = 'WHCPT01';


    public function __construct(){
        
    }
    

	public function index(){
        return IndexRes::resultData(200,['message' => 'API Custome 8commerce for Luxasia '.$this->company_id],[]);
    }

/**
 * SKU function
 */

	public function sku(){
		$directory = public_path('LuxasiaFile/sku');
		$files = File::allFiles($directory); 
		if(count($files) > 0){
			foreach($files as $path){
				$file 		= pathinfo($path);
                $fileName	= $file['basename'];
                
				return $this->checkSKUFile($file,$fileName);
			}
		}else{
			return IndexRes::resultData(200,['message' => 'no data'],[]);
		}
    }
    
      private function checkSKUFile($file,$name){
		   
		   if(strtolower($file['extension']) == 'txt'){
			   $fp     	= fopen($file['dirname'].'/'.$name,'r');
			   $string 	= '';
			   $x      	= 0;
			   
            //    $fread = fread($fp,filesize($file['dirname'].'/'.$name));
                $all_rows   = array();
                $header     = array();
                $x          = 0;

                while (!feof($fp)){
                    $current_line   = fgets ($fp);
                    $explode        = explode("|",$current_line);
                    $string         .= $current_line.'\n';

                    $all_rows[] = $explode;
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
                        $all_rows[] = $dd;
                        $res = $this->insertSKU($dd);
                        if($res["status"] == "200"){
                            echo json_encode($res);
                        }else{
                            ApiLog::insertLog('Controllers\Luxasia\IndexController','production',json_encode($res), 'ERROR' , $current_line,'/',$this->company_id);
                        }
                    }
                    $x++;
                }

               fclose($fp);
               
               $uuid	= Uuid::uuid4()->toString();
               ApiLog::insertOrderBuffer($this->company_id,$uuid,"", 'sku',1,"custome" , json_encode($all_rows),'');
                unlink($file['dirname'].'/'.$name);
			   
		   }else{
			   return IndexRes::resultData(402,['message' => 'extension file not allowed'],[]);
		   }
      }

    private function insertSKU($array){
        if(!empty(@$array['ARTICLE CODE'])){
            $check =  Sku::where([['sku_code','=',$array['ARTICLE CODE']],['company_id','=',$this->company_id]])->first();
            if($check){
                $check->sku_description = $array['DESCRIPTION'];
                $check->price 			= $array['RETAIL PRICE'];
                $check->barcode	    	= $array['BAR CODE (EAN)'];
                $check->update();

                $wms = new PutSku;
                $result = $wms->updateSku($check);
                if($result["Response"]["return"]["returnCode"] == "0000"){
                    $res = ['status' => 200 , 'message' =>['message' => $result["Response"]["return"]["returnDesc"]], 'errors' => []];
                }else{
                    $subject    = 'Luxasia SKU Integration';
                    $content    = 'Data not insert to WMS.<br><br>'.json_encode($array);
                    ApiLog::sendEmail($subject,$content);
					
                    $res = ['status' => 402 , 'message' =>[], 'errors' => $result];
                }

            }else{
                $sku = new Sku;
                $sku->sku_code 			= strtoupper($array['ARTICLE CODE']);
                $sku->sku_description 	= strtoupper($array['DESCRIPTION']);
                $sku->company_id 		= strtoupper($this->company_id);
                $sku->price 			= $array['RETAIL PRICE'];
                $sku->insured 			= 0;
                $sku->width 			= 1;
                $sku->height 			= 1;
                $sku->length 			= 1;
                $sku->special_packaging = '';
                $sku->weight 			= 1;
                $sku->type 				= 'normal';
                $sku->conv_pcs			= 0;
                $sku->conv_bundle 		= 0;
                $sku->conv_box 			= 0;
                $sku->conv_cbm 			= 0;
                $sku->conv_pallet 		= 0;
                $sku->category_id 		= 1;
                $sku->image		 		= '';
                $sku->sku_short_description = strtoupper(@$array['BRAND DESCRIPTION']);
                $sku->net_weight 		= 1;
                $sku->cube 				= 1;
                $sku->is_shelf_life		= 1;
                $sku->inbound_life_days = 1;
                $sku->outbond_life_days = 1;
                $sku->shelf_life		= 1;
                $sku->shelf_life_type	= "M";
                $sku->qty_per_carton	= 1;
                $sku->uom				= "KG";
                $sku->carton_per_pallet	= 1;
                $sku->barcode	    	= $array['BAR CODE (EAN)'];
                $sku->freight_class		= "COLD NON FOOD";
                $sku->save();
        
                $wms = new PutSku;
                $result = $wms->index($sku);
                $this->insertSkuMarketplace($array,$sku);
                if($result["Response"]["return"]["returnCode"] == "0000"){
                    $datas                      = [];
                    $companyFullfilmentCenter   = CompanyFullfilmentCenter::where('company_id',$sku->company_id)->get();
                    if(count($companyFullfilmentCenter) > 0){
                        foreach($companyFullfilmentCenter as $data){
                            $datas[]    = [
                                'company_id' => $sku->company_id, 'sku_code' => $sku->sku_code, 'stock_date' => date('Y-m-d'), 
                                'stock_available' => 0, 'stock_hold' => 0, 'stock_on_hand' => 0, 
                                'stock_booked' => 0, 'stock_booked_pending' => 0, 'fulfillment_center_id' => $data->fulfillment_center_id, 'job_id' => 'api'];
                        }

                        Inventory::insert($datas);
                    }
                    $res = ['status' => 200 , 'message' =>['message' => $result["Response"]["return"]["returnDesc"]], 'errors' => []];
                }else{
                    $subject    = 'Luxasia SKU Integration';
                    $content    = 'Data not insert to WMS.<br><br>'.json_encode($array);
                    ApiLog::sendEmail($subject,$content);
                    $res = ['status' => 402 , 'message' =>[], 'errors' => $result];
                }
            }
        }else{
            $subject    = 'Luxasia SKU Integration';
            $content    = 'sku code blank.<br><br>'.json_encode($array);
            ApiLog::sendEmail($subject,$content);
            $res = ['status' => 402 , 'message' =>[], 'errors' => ['message' => 'sku code blank']];
        }
        
        return $res;
      }

      private function insertSkuMarketplace($datas,$sku){
        $check = SkuMarketplace::where([['sku_code',$sku->sku_code], ['company_id',$sku->company_id],['marketplace_id','API']])->first();
        if(!$check){
            $SkuMarketplace                 = new SkuMarketplace();
            $SkuMarketplace->sku_code       = $sku->sku_code;
            $SkuMarketplace->marketplace_id = 'API';
            $SkuMarketplace->status         = 1;
            $SkuMarketplace->company_id     = $sku->company_id;
            $SkuMarketplace->datas          = json_encode($datas);
            $SkuMarketplace->save();
        }else{
            $check->datas          = json_encode($datas);
            $check->save();
        }
      }


    private function cleanString($string){
        $string = trim(preg_replace('/\s+/', ' ', $string));
        return $string;
    }

      


/**
 * Replenishment order
 * {
 *   dirname: "E:\project\custome-8commerce\App/public/public/LuxasiaFile/replenishment",
 *   basename: "REPL_OUB_20200608_091802.txt",
 *   extension: "txt",
 *   filename: "REPL_OUB_20200608_091802"
 *   }
 */

	public function po(){
        set_time_limit(0);
		$directory = base_path('public/Test');
        $files = File::allFiles($directory); 
        
		if(count($files) > 0){
			foreach($files as $path){
				$file 		= pathinfo($path);
                $fileName	= $file['basename'];
				return $this->checkPOFile($file,$fileName);
			}
		}else{
			return IndexRes::resultData(200,['message' => 'no data'],[]);
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
                    $this->insertPoBuffer($row);
                    // $res = $this->insertPoData($row);
					// if($res["status"] == "200"){
                    //         echo json_encode($res);
                    // }else{
                    //    ApiLog::insertLog('Controllers\Luxasia\IndexController','production',json_encode($res), 'ERROR' , $current_line,'/',$this->company_id);
                    // }
                }
            }
			
            
			$uuid	= Uuid::uuid4()->toString();
            ApiLog::insertOrderBuffer($this->company_id,$uuid,"", 'po',1,"custome" , $string,json_encode($all_rows));
			
            unlink($file['dirname'].'/'.$name);
			
        }else{
            return IndexRes::resultData(402,['message' => 'extension file not allowed'],[]);
        }
    }

    
    private function insertPoData($datas){
        $check      = PoHeader::where([['company_id', '=' , $datas["company_id"]], ['po_no', '=' , $datas["po_no"]], ["status","<>","cancelled"]])->first();
        if($check) {
           $res = ['status' => 402 , 'message' =>[], 'errors' => ['message' => $check]];	
        }else{
            $po             = new PoHeader;
            $po->po_type    = $datas["datas_sci"]["header"]["po_type"];
            $po->po_no      = $datas["datas_sci"]["header"]["po_no"];
            $po->crossdock_no = $datas["datas_sci"]["header"]["crossdock_no"];
            $po->po_date    = $datas["datas_sci"]["header"]["po_date"];
            $po->company_id = $datas["datas_sci"]["header"]["company_id"];
            $po->eta_date   = $datas["datas_sci"]["header"]["eta_date"];
            $po->vehicle_no = $datas["datas_sci"]["header"]["vehicle_no"];
            $po->driver_name= $datas["datas_sci"]["header"]["driver_name"];
            $po->status     = $datas["datas_sci"]["header"]["status"];
            $po->create_by  = $datas["datas_sci"]["header"]["create_by"];
            $po->dest_name  = $datas["datas_sci"]["header"]["dest_name"];
            $po->dest_address1  = $datas["datas_sci"]["header"]["dest_address1"];
            $po->dest_address2  = $datas["datas_sci"]["header"]["dest_address2"];
            $po->dest_province  = $datas["datas_sci"]["header"]["dest_province"];
            $po->dest_city  = $datas["datas_sci"]["header"]["dest_city"];
            $po->dest_area  = $datas["datas_sci"]["header"]["dest_area"];
            $po->dest_sub_area      = $datas["datas_sci"]["header"]["dest_sub_area"];
            $po->dest_postal_code   = $datas["datas_sci"]["header"]["dest_postal_code"];
            $po->dest_village       = $datas["datas_sci"]["header"]["dest_village"];
            $po->dest_remarks       = $datas["datas_sci"]["header"]["dest_remarks"];
            $po->ori_name           = $datas["datas_sci"]["header"]["ori_name"];
            $po->ori_address1       = $datas["datas_sci"]["header"]["ori_address1"];
            $po->ori_address2       = $datas["datas_sci"]["header"]["ori_address2"];
            $po->ori_province       = $datas["datas_sci"]["header"]["ori_province"];
            $po->ori_city           = $datas["datas_sci"]["header"]["ori_city"];
            $po->ori_area           = $datas["datas_sci"]["header"]["ori_area"];
            $po->ori_sub_area       = $datas["datas_sci"]["header"]["ori_sub_area"];
            $po->ori_postal_code    = $datas["datas_sci"]["header"]["ori_postal_code"];
            $po->ori_village        = $datas["datas_sci"]["header"]["ori_village"];
            $po->ori_remarks        = $datas["datas_sci"]["header"]["ori_remarks"];
            $po->fulfillment_center_id        = $datas["datas_sci"]["header"]["fulfillment_center_id"];
            $po->dest_country       = $datas["datas_sci"]["header"]["dest_country"];
            $po->ori_country        = $datas["datas_sci"]["header"]["ori_country"];
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
			
			 $wms = new InboundAsn;
             $result = $wms->index($po);
             if($result["Response"]["return"]["returnCode"] == "0000"){
                $po->status     = "new";
				$po->save();
				
				$poTracking                   = new PoStatusTracking;
				$poTracking->po_header_id     = $id;
				$poTracking->po_no            = $datas["datas_sci"]["header"]["po_no"];
				$poTracking->status           = "new";
				$poTracking->system           = 'OMS';
				$poTracking->remarks          = '';
				$poTracking->create_by        = 'API';
				$poTracking->save();
				
				$res = ['status' => 200 , 'message' =>['message' => $result["Response"]["return"]["returnDesc"]], 'errors' => []];
             }else{
				$subject    = 'Luxasia PO Integration';
                $content    = 'Data not insert to WMS.<br><br>'.json_encode($datas);
                ApiLog::sendEmail($subject,$content);
				
				$res = ['status' => 402 , 'message' =>[], 'errors' => $result];
            }

			$this->insertPoBuffer($datas);
		}
		return $res;
    }

 
    
    private function insertPoBuffer($datas){
        $check      = PoBuffer::where([['company_id' , $datas["company_id"]], ['po_no' , $datas["po_no"]]])->first();
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
    
	private function stringToInterger($string){
		return preg_replace('/[^0-9]/', '', $string);
    }
    
	private function stringToInterger2($string){
		$explode = explode(".", $string);
		return $explode[0];
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
                ApiLog::insertLog('Controllers\Luxasia\IndexController','production',json_encode($get), 'ERROR' ,'sku not found','/',$this->company_id);
                $subject    = 'Luxasia PO Integration';
                $content    = 'sku not found .<br><br>'.json_encode($get);
                ApiLog::sendEmail($subject,$content);
            }
        }

        $res['details'] = $details;

        return $res; 

    }
    

    /**
     * Goods receipt
     */

	public function receiptsPo(){
        set_time_limit(0);
	    $directory  = base_path('public/LuxasiaFile/receipt');
        $fileName   = 'GR_IB_'.date('dmY_Hi').'.TXT';

        $bufferDatas    = PoBuffer::where('company_id', $this->company_id)->whereIn('seq',[1,2,3])->get();
        if(count($bufferDatas) > 0){
            $datas  = [];

            foreach($bufferDatas as $buffer){
                $check = PoHeader::where([['po_no',$buffer->po_no] , ['company_id' , $this->company_id], ['status','received']])->with('details')->first();
                if($check){
                    $datas[]    = ['id' => $buffer->id,'datas' => json_decode($buffer->datas,true),'datas_sci' => $check];
                }else{
                    continue;
                }
            }
            
            if(count($datas) > 0){
                // echo json_encode($datas);
     
                $fp     	= fopen($directory.'/'.$fileName,'w');
                $text       = "Order_type|Inbound_order|Inbound_item|Store_id|StorageLocation|ItemCode|Qty|UOM|PO_NUMBER|PO_ITEM|DELIV_DATE|HEADER_TEXT|ITEM_TEXT|VBN|MANU_DATE|EXPR_DATE|Remarks1|Remarks2|Remarks3";
                $VBN        = 0;
                foreach($datas as $data){
                    foreach($data["datas"] as $d){
                        $checkSCIpoDetail   = $this->checkSCIpoDetail($d, $data["datas_sci"]);
                        if($checkSCIpoDetail){
                            $text       .= "\n".$d["Order_type"]."|".$d["Inbound_order"]."|".$d["Inbound_item"]."|".$d["Store_id"]."|Available|".$d["ItemCode"]."|".$checkSCIpoDetail["qty_received"]."|".$d["UOM"]."|".$d["PO_NUMBER"]."|".$d["PO_ITEM"]."|".$d["DELIV_DATE"]."||||||||";
                        }else{
                            continue;
                        }
                        $VBN++;
                    }

                    $this->updatePoBuffer($data["id"],['seq' => 10]);
                }

            //    echo $text;
                fwrite($fp, $text);
                fclose($fp);
            }else{
                return IndexRes::resultData(200,['message' => 'no data'],[]);
            }
        }else{
            return IndexRes::resultData(200,['message' => 'no data'],[]);
        }
    }

    private function checkSCIpoDetail($buffer , $sci){
        $detail     = [];
        foreach($sci['details'] as $det){
            if($det['sku_code'] == $buffer['ItemCode']){
                $detail = $det;
                break;
            }
        }

        return $detail;
    }

    private function updatePoBuffer($id , $update){
        PoBuffer::where('id', $id)->update($update);
    }

    
    /**
     * Stock on hand
    */

	public function stock(){
         set_time_limit(0);
	    $directory      = base_path('public/LuxasiaFile/inventory_balance');
        $fileName       = 'SOH_IB_'.date('dmY_Hi').'.TXT';
        $inventory      = Inventory::where('company_id', $this->company_id)->get();
        $now            = date('mdY');
        if(count($inventory) > 0){                
            $fp     	= fopen($directory.'/'.$fileName,'w');
            $text       = "Transaction_Date|Store_id|StorageLocation|ItemCode|VBN|MANU_DATE|EXPR_DATE|Qty|Remarks1|Remarks2|Remarks3";
            $VBN        = 0;
            foreach($inventory as $i){
                $text       .= "\n".$now."|0056|0001|".$i->sku_code."||||".$i->stock_available."|||";
                $VBN++;
            }
             fwrite($fp, $text);
             fclose($fp);
        }else{
            ApiLog::insertLog('Custome Server',$this->company_id,'', 'SUCCESS' , '','\App\Console\Commands\Luxasia\LuxasiaStockSync');
        }         
    }

    /**
     * Stock Transfer
    */
     
	public function stockTransfer(){


        $order          = CustomeBuffer::where([['company_id', $this->company_id]])->whereRaw('DATE(create_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)')->whereNotNull('sku_code')->get();

        echo json_encode($order);

        /*
        set_time_limit(0);
	    $directory      = base_path('public/LuxasiaFile/stock_transfer');
        $fileName       = 'TR_IB_'.date('dmY_Hi').'.TXT';
        $order          = OrderHeader::where([['company_id', $this->company_id], ['order_type','transfer_outbound'],['status','shipped']])->whereRaw('DATE(update_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)')->with('details')->get();
        $now            = date('mdY');
        if(count($order) > 0){    
            $fp     	= fopen($directory.'/'.$fileName,'w');
            $text       = "Transaction_Date|Store_id|ItemCode|VBN|MANU_DATE|EXPR_DATE|Qty|From_StorageLocation|To_StorageLocation|Remarks|Remarks1|Remarks2|Remarks3";
            // $text       = "Transaction_Date|Store_id|ItemCode|VBN|MANU_DATE|EXPR_DATE|Qty|WH01|WH02|Remarks|Remarks1|Remarks2|Remarks3<br>";
            
            foreach($order as $o){
                $Transaction_Date   = date_format(date_create($o->create_time),"Ymd");
                foreach($o->details as $det){
                    $text        .= "\n".$Transaction_Date."|0056|".$det->sku_code."||||".$det->qty_ship."|WH01|WH02|".substr($o->order_no,0,40)."|||";
                }
                usleep(25000);
            }
            echo $text;
            //  fwrite($fp, $text);
            //  fclose($fp);
        }else{
            ApiLog::insertLog('Custome Server',$this->company_id,'', 'SUCCESS' , '','\App\Console\Commands\Luxasia\LuxasiaStockTransferSync');
        }
        
        */
        /*
        set_time_limit(0);
	    $directory      = public_path('LuxasiaFile/stock_transfer');
        $fileName       = 'TR_IB_'.date('dmY_Hi').'.TXT';
        $buffer         = OrderBuffer::select('order_no')->where([['company_id',$this->company_id],['seq', 10],['type','row']])->get()->toArray();
        $arrNo          = $this->getValueFromBuffer($buffer);
        $order          = OrderHeader::where([['company_id', $this->company_id], ['order_type','transfer_outbound'],['status','shipped']])->whereRaw('DATE(update_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)')->with('details')->get();
        $order          = OrderHeader::where([['company_id', $this->company_id], ['order_type','transfer_outbound'],['status','shipped']])->whereNotIn('order_no', $arrNo)->with('details')->get();
        $now            = date('mdY');
  
        if(count($order) > 0){                
            $fp     	= fopen($directory.'/'.$fileName,'w');
            $text       = "Transaction_Date|Store_id|ItemCode|VBN|MANU_DATE|EXPR_DATE|Qty|From_StorageLocation|To_StorageLocation|Remarks|Remarks1|Remarks2|Remarks3\n";
            // $text       = "Transaction_Date|Store_id|ItemCode|VBN|MANU_DATE|EXPR_DATE|Qty|From_StorageLocation|To_StorageLocation|Remarks|Remarks1|Remarks2|Remarks3<br>";
            $VBN        = 0;
            foreach($order as $o){
                $Transaction_Date   = date_format(date_create($o->create_time),"Ymd");
                foreach($o->details as $det){
                    $text        .= $Transaction_Date."|".substr($o->fulfillment_center_id,0,10)."|".$det->sku_code."|".$VBN."|".date("Ymd")."|".date("Ymd")."|".$det->qty_ship."|".substr($o->fulfillment_center_id,0,4)."|".substr($o->fulfillment_center_id,0,4)."|".substr($o->dest_remarks,0,40)."|||\n";
                    $VBN++;
                }
                OrderBuffer::create(['order_no' => $o->order_no , 'company_id' => $this->company_id , 'seq' => 10, 'type' => 'row' , 'channel' => 'API' , 'create_time' => date('Y-m-d H:i:s')]);
            }
            // echo $text;
             fwrite($fp, $text);
             fclose($fp);
        }else{
            return IndexRes::resultData(200,['message' => 'no data'],[]);
        }

        */
    }
    private function getValueFromBuffer($datas){
        $res = [];
        if(count($datas) > 0){
            foreach($datas as $data){
                array_push($res,$data['order_no']);
            }
        }
        return $res;
    }
    
    
    /**
     * Sales Transaction Return
     */
	public function soReturn(){
        set_time_limit(0);
	    $directory      = base_path('public/LuxasiaFile/so');
        $fileName       = 'SALES_IB_'.date('dmY_Hi').'.TXT';
        $orders         = PoHeader::where([['company_id', 'ECBRIGHT'], ['po_type','so_return'],['status','received']])->whereRaw('DATE(update_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)')->with('details')->get();
        $now            = date('mdY');
        if(count($orders) > 0){    
            $fp     	= fopen($directory.'/'.$fileName,'w');
            $text       = "Transaction Type|Order Number|Sequence Number|CustomerNumber|StoreID|DocumentDate|ItemCode|Quantity|Confirm_Qty|Retail price (* Qty)|Discount (*Qty)|Net Value (* Qty)|GST Amount|Discount_Code|Discount_name|Created_on|Created_time|Ship_to_FName|Ship_to_LName|Ship_to_Mobile|Ship_to_Email|Ship_to_Address|Ship_to_Postcode|Ship_to_City|Ship_to_Country|Ship_to_Special_Text|Bill_to_FName|Bill_to_LName|Bill_to_Mobile|Bill_to_Email|Bill_to_Address|Bill_to_Postcode|Bill_to_City|Bill_to_Country|Bill_to_Special_Text|Remarks1|Remarks2|Remarks3";
            foreach($orders as $order){
                $Sequence = 0;
                foreach($order->details as $det){
                    $Retailprice = $this->checkPrice($det->price) * $det->qty_ship;
                    $text        .= "\nR|".$order->po_no."|".$Sequence."|".$order->ori_name."|0056|".date_format(date_create($order->update_time),"Ymd")."|".$det->sku_code."|".$det->qty_order."|".$det->qty_received."|".$Retailprice."|0|".$Retailprice."|0|||".date_format(date_create($order->create_time),"Ymd")."|".date_format(date_create($order->create_time),"His")."|".substr($order->dest_name,0,35)."||".substr($order->dest_phone,0,20)."|".substr($order->dest_email,0,100)."|".substr($this->stringCheck($order->dest_address1),0,150)."|".substr($order->dest_postal_code,0,10)."|".substr($order->dest_city,0,20)."|ID|".substr($this->stringCheck($order->dest_remarks),0,50)."|".substr($order->dest_name,0,35)."||".substr($order->dest_phone,0,20)."||".substr($this->stringCheck($order->dest_address1),0,150)."|".substr($order->dest_postal_code,0,10)."|".substr($order->dest_city,0,20)."|ID|".substr($this->stringCheck($order->dest_remarks),0,50)."|||";
                    $Sequence++;
                }
            }
            echo $text;
            //  fwrite($fp, $text);
            //  fclose($fp);
        }else{
            ApiLog::insertLog('Custome Server',$this->company_id,'', 'SUCCESS' , '','\App\Console\Commands\Luxasia\LuxasiaSalesTransactionSync');
        }

    }


    /**
     * Sales Transaction
     */

	public function so(){

        set_time_limit(0);
	    $directory      = base_path('public/LuxasiaFile/so');
        $fileName       = 'SALES_IB_'.date('dmY_Hi').'.TXT';
        // $orders         = OrderHeader::where([['company_id', 'ECLUXASIA'], ['order_type','normal'],['status','shipped']])->whereRaw('DATE(update_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)')->with('details')->get();
        $orders         = OrderHeader::where([['company_id', 'ECLUXASIA'], ['order_type','normal']])->whereIn('order_no', ["210117FSK468UG","210119NVME798C","210121TCRVC8D2","21012326QA5HS0","2101256PHTYPBV","21012572WY41RR"])->with('details')->get();
        $now            = date('mdY');
        if(count($orders) > 0){    
            $fp     	= fopen($directory.'/'.$fileName,'w');
            $text       = "Transaction Type|Order Number|Sequence Number|CustomerNumber|StoreID|DocumentDate|ItemCode|Quantity|Confirm_Qty|Retail price (* Qty)|Discount (*Qty)|Net Value (* Qty)|GST Amount|Discount_Code|Discount_name|Created_on|Created_time|Ship_to_FName|Ship_to_LName|Ship_to_Mobile|Ship_to_Email|Ship_to_Address|Ship_to_Postcode|Ship_to_City|Ship_to_Country|Ship_to_Special_Text|Bill_to_FName|Bill_to_LName|Bill_to_Mobile|Bill_to_Email|Bill_to_Address|Bill_to_Postcode|Bill_to_City|Bill_to_Country|Bill_to_Special_Text|Remarks1|Remarks2|Remarks3";
            foreach($orders as $order){
                $Sequence = 0;
                foreach($order->details as $det){
                    $Retailprice    = $this->checkPrice($det->price) * $det->qty_ship;
                    $discount_price = $this->checkPrice($det->discount_price);
                    $text        .= "\nS|".$order->order_no."|".$Sequence."|".$order->order_source."|0056|".date_format(date_create($order->update_time),"Ymd")."|".$det->sku_code."|".$det->qty_order."|".$det->qty_ship."|".$Retailprice."|".$discount_price."|".$order->amount_order."|0|".substr($this->stringCheck($order->promo_code),0,20)."||".date_format(date_create($order->create_time),"Ymd")."|".date_format(date_create($order->create_time),"His")."|".substr($order->dest_name,0,35)."||".substr($order->dest_phone,0,20)."|".substr($order->dest_email,0,100)."|".substr($this->stringCheck($order->dest_address1),0,150)."|".substr($order->dest_postal_code,0,10)."|".substr($order->dest_city,0,20)."|ID|".substr($this->stringCheck($order->dest_remarks),0,50)."|".substr($order->dest_name,0,35)."||".substr($order->dest_phone,0,20)."|".substr($order->dest_email,0,100)."|".substr($this->stringCheck($order->dest_address1),0,150)."|".substr($order->dest_postal_code,0,10)."|".substr($order->dest_city,0,20)."|ID|".substr($this->stringCheck($order->dest_remarks),0,50)."|||";
                    $Sequence++;
                }
            }
            // echo $text;
             fwrite($fp, $text);
             fclose($fp);
        }else{
            ApiLog::insertLog('Custome Server',$this->company_id,'', 'SUCCESS' , '','\App\Console\Commands\Luxasia\LuxasiaSalesTransactionSync');
        }
    }

    private function checkPrice($price){
        $res = 0;
        if(isset($price) || trim($price) === ''){
            $res    = $price;
        }
        
        return $res;
    }



    private function stringCheck($str){        
        return preg_replace("/[^A-Za-z0-9-,().\_ ]/", '', $str);
    }
}