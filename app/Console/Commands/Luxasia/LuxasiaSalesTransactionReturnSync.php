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
use App\Models\CustomeBuffer;
use App\Models\Warehouse\Honeywell\PutSku;
use App\Models\Warehouse\Honeywell\InboundAsn;


class LuxasiaSalesTransactionReturnSync extends Command
{
    public $company_id              = 'ECLUXASIA';
    public $fulfillment_center_id   = 'WHCPT01';
   /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'LuxasiaSalesTransactionReturnSync:sender';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SO to Luxasia';
 
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
	    $directory      = base_path('public/LuxasiaFile/so');
        $fileName       = 'SALES_IB_'.date('dmY_Hi').'.TXT';
        $orders         = PoHeader::where([['company_id', $this->company_id], ['po_type','so_return'],['status','received']])->whereRaw('DATE(update_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)')->with('details')->get();
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