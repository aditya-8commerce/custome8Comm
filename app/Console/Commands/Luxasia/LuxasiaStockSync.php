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


class LuxasiaStockSync extends Command
{
    public $company_id              = 'ECLUXASIA';
    public $fulfillment_center_id   = 'WHCPT01';
   /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'LuxasiaStockSync:sender';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stock From SCI to Luxasia';
 
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


}