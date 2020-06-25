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

use App\Http\Controllers\ApiLogController as ApiLog;
use App\Res\IndexRes;
 
use App\Models\ServerCustomeTransaction;
use App\Models\Sku;
use App\Models\PoHeader;
use App\Models\PoDetail;
use App\Models\CompanyFullfilmentCenter;
use App\Models\Inventory;
use App\Models\Warehouse\Honeywell\PutSku;
use App\Models\Warehouse\Honeywell\InboundAsn;


class IndexController extends Controller
{
    public $company_id              = 'RBIZ_TEST';
    public $fulfillment_center_id   = 'WHCPT01';


    public function __construct(){
        
    }
    

	public function index(){
        return IndexRes::resultData(200,['message' => 'API Custome 8commerce for Luxasia'],[]);
    }

/**
 * SKU function
 */

	public function importSKU(){
		$directory = public_path('public/LuxasiaFile/sku');
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
                            $subject    = 'Luxasia SKU Integration';
                            $content    = 'Data not insert.<br><br>'.json_encode($dd);
                            ApiLog::sendEmail($subject,$content);
                        }
                    }
                    $x++;
                }

               fclose($fp);
               
               
               ApiLog::insertLog('Controllers\Luxasia\IndexController\importSKU','production',$name, 'INFO' , json_encode($all_rows),'/luxasia/import-sku');
                // dd($file);
                unlink($file['dirname'].'/'.$name);
			   
		   }else{
			   return IndexRes::resultData(402,['message' => 'extension file not allowed'],[]);
		   }
      }

    private function insertSKU($array){
        if($array['ARTICLE CODE'] != ''){
            $check =  Sku::where([['sku_code',$array['ARTICLE CODE'],['company_id',$this->company_id]]])->first();
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
                $sku->sku_short_description = '';
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
                    $res = ['status' => 402 , 'message' =>[], 'errors' => $result];
                }
            }
        }else{
            $res = ['status' => 402 , 'message' =>[], 'errors' => ['message' => 'sku code blank']];
        }
        
        return $res;
      }

      private function updateSKU($array,$sku){
		$sku->sku_description 	= $array[1];
		$sku->price 			= $array[15];
        $sku->sku_short_description 		= $array[10];
        $sku->update(['sku_id',$sku->sku_id]);
		$wms = new PutSku;
		$res = $wms->index($sku,'update'); 
        return $res;
      }


    private function cleanString($string){
        $string = trim(preg_replace('/\s+/', ' ', $string));
        return $string;
    }

      


/**
 * PO function
 */

	public function importPo(){
        $directory ='public/LuxasiaFile/po';
        if ($this->is_dir_empty($directory)) {
            return IndexRes::resultData(200,['message' => 'no data'],[]);
        }else{
            foreach(glob($directory.'/*.*') as $fileName) {
               $this->checkPOFile($fileName);
            }
        }
    }

    private function checkPOFile($file){
        $fp     = fopen($file,'r');
        $string = '';
        $x      = 0;
        while (!feof($fp)){ 
            $current_line   = fgets ($fp);
            $string         .= $current_line.'\n';
            $explode        = explode("|",$current_line);

            if(count($explode) == '17'){
                $checkPo   = PoHeader::where([["po_no",$explode[8]],["company_id",$this->company_id]])->first();
                if($checkPo){
                    $this->apiLog->insertLog('Controllers\Luxasia\IndexController','production','po_no duplicate', 'ERROR' , $current_line,'/',$this->company_id);
                    continue;
                }else{
                    $this->insertPo($explode);
                }
            }else{
                $this->apiLog->insertLog('Controllers\Luxasia\IndexController','production','string specification not complete', 'ERROR' , $current_line,'/',$this->company_id);
                continue;
            }
            $x++;
        }
        fclose($fp);

        // ServerCustomeTransaction::create(["company_id" => $this->company_id , "datas" => $string , "file_name" => $file]);
        // unlink($file);

    }

    private function insertPo($array){      
        echo $this->company_id;
        
      }

      public function importSo(){
          $directory ='public/LuxasiaFile/so';
          if ($this->is_dir_empty($directory)) {
              return IndexRes::resultData(200,['message' => 'no data'],[]);
          }else{
              foreach(glob($directory.'/*.*') as $fileName) {
                 $this->checkSOFile($fileName);
              }
          }
      }

      private function checkSOFile($file){

      }

}
