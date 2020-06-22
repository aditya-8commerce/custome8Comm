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

	public function importSKU(){
		$directory = public_path('public/LuxasiaFile/sku');
		$files = File::allFiles($directory); 
		if(count($files) > 0){
			foreach($files as $path){
				$file 		= pathinfo($path);
				$fileName	= $file['basename'];
				this->checkSKUFile($fileName);
			}
		}else{
			return IndexRes::resultData(200,['message' => 'no data'],[]);
		}
		
        // $directory ='public/LuxasiaFile/sku';
        // if ($this->is_dir_empty($directory)) {
            // return IndexRes::resultData(200,['message' => 'no data'],[]);
        // }else{
            // foreach(glob($directory.'/*.*') as $fileName) {
				// $this->checkSKUFile($fileName);
            // }
        // }
    }

    private function is_dir_empty($dir) {
        if (!is_readable($dir)) return NULL; 
        return (count(scandir($dir)) == 2);
      }
    
      private function checkSKUFile($file){
		   
		  echo json_encode($file);
		  
		  /*
        $fp     = fopen($file,'r');
        $string = '';
        $x      = 0;
        while (!feof($fp)){ 
            $current_line   = fgets ($fp);
            $string         .= $current_line.'\n';

            $explode        = explode("|",$current_line);
            if(count($explode) == '27'){
                $checkSKU   = Sku::where([["sku_code",$explode[0]],["company_id",$this->company_id]])->first();
                if($checkSKU){
                    // update data
                    $res = $this->updateSKU($explode,$checkSKU);
                    if($res["Response"]["return"]["returnCode"] == "0000"){
                        echo json_encode($res);
                    }else{
                        $this->apiLog->insertLog('Controllers\Luxasia\IndexController','production',$res["Response"]["return"]["returnDesc"], 'ERROR' , $current_line,'/',$this->company_id);
                        echo json_encode($res);
                    }
                }else{
                    // insert new data
                    $res = $this->insertSKU($explode);
                    if($res["Response"]["return"]["returnCode"] == "0000"){
                        echo json_encode($res);
                    }else{
                        $this->apiLog->insertLog('Controllers\Luxasia\IndexController','production',$res["Response"]["return"]["returnDesc"], 'ERROR' , $current_line,'/',$this->company_id);
                        echo json_encode($res);
                    }
                }
            }else{
                $this->apiLog->insertLog('Controllers\Luxasia\IndexController','production','string specification not complete', 'ERROR' , $current_line,'/',$this->company_id);
                continue;
            }
            $x++;
        }
        fclose($fp);

        ServerCustomeTransaction::create(["company_id" => $this->company_id , "datas" => $string , "file_name" => $file]);
        unlink($file);
		
		*/
      }

      private function insertSKU($array){          
		$sku = new Sku;
		$sku->sku_code 			= $array[0];
		$sku->sku_description 	= $array[1];
		$sku->company_id 		= $this->company_id;
		$sku->price 			= $array[15];
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
		$sku->sku_short_description 		= $array[10];
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
		$sku->barcode	    	= $array[4];
		$sku->freight_class		= "COLD NON FOOD";
		$sku->save();

		$wms = new PutSku;
        $res = $wms->index($sku); 
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
