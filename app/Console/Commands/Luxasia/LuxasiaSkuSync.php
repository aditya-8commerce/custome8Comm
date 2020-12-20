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


class LuxasiaSkuSync extends Command
{
    public $company_id              = 'ECLUXASIA';
    public $fulfillment_center_id   = 'WHCPT01';
   /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'LuxasiaSkuSync:sender';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SKU from Luxasia to SCI';
 
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
        $directory = base_path('public/LuxasiaFile/sku');
        $files = File::allFiles($directory); 
		if(count($files) > 0){
			foreach($files as $path){
				$file 		= pathinfo($path);
                $fileName	= $file['basename'];
                
                return $this->checkSKUFile($file,$fileName);
                sleep(1);
			}
		}else{
            ApiLog::insertLog('Custome Server',$this->company_id,'', 'SUCCESS' , '','\App\Console\Commands\Luxasia\LuxasiaSkuSync');
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
                     if($res["status"] != "200"){
                        $subject    = 'Luxasia SKU Integration';
                        $content    = $current_line;
                        ApiLog::sendEmail($subject,$content);
                     }
                 }
                 $x++;

                 usleep(25000);
             }

            fclose($fp);
            
            $uuid	= Uuid::uuid4()->toString();
            ApiLog::insertOrderBuffer($this->company_id,$uuid,"", 'sku',1,"custome" , json_encode($all_rows),'');
            unlink($file['dirname'].'/'.$name);
            
        }else{
            $subject    = 'Luxasia SKU Integration';
            $content    = 'Extension file not allowed';
            ApiLog::sendEmail($subject,$content);
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
                $sku->sku_short_description = strtoupper($array['STORE ID']);
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
}