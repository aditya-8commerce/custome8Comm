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


class LuxasiaReceiptsPoSync extends Command
{
    public $company_id              = 'ECLUXASIA';
    public $fulfillment_center_id   = 'WHCPT01';
   /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'LuxasiaReceiptsPoSync:sender';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'PO Received to Luxasia';
 
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
                $fp     	= fopen($directory.'/'.$fileName,'w');
                $text       = "Order_type|Inbound_order|Inbound_item|Store_id|StorageLocation|ItemCode|Qty|UOM|PO_NUMBER|PO_ITEM|DELIV_DATE|HEADER_TEXT|ITEM_TEXT|VBN|MANU_DATE|EXPR_DATE|Remarks1|Remarks2|Remarks3";
                $VBN        = 0;
                foreach($datas as $data){
                    foreach($data["datas"] as $d){
                        $checkSCIpoDetail   = $this->checkSCIpoDetail($d, $data["datas_sci"]);
                        if($checkSCIpoDetail){
                            $text       .= "\n".$d["Order_type"]."|".$d["Inbound_order"]."|".$d["Inbound_item"]."|".$d["Store_id"]."|".$d["StorageLocation"]."|".$d["ItemCode"]."|".$checkSCIpoDetail["qty_received"]."|".$d["UOM"]."|".$d["PO_NUMBER"]."|".$d["PO_ITEM"]."|".$d["DELIV_DATE"]."||||||||";
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
            }
        }else{
            ApiLog::insertLog('Custome Server',$this->company_id,'', 'SUCCESS' , '','\App\Console\Commands\Luxasia\LuxasiaReceiptsPoSync');
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
}