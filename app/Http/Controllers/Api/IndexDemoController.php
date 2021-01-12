<?php
namespace App\Http\Controllers\Api; 

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
use Illuminate\Support\Str;
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
use App\Models\User;
use App\Models\Warehouse\Honeywell\PutSku;
use App\Models\Warehouse\Honeywell\InboundAsn;


class IndexDemoController extends Controller
{
    public $user   = [];

    public function __construct(){
        
    }

    public function index(){
        return IndexRes::resultData(200,['message' => 'API 8commerce Integrations'],[]);
    }

    public function getSo(Request $request)
    {
        $checkUser = $this->getAuthorization($request);
        if($checkUser){
            return IndexRes::resultData(200,['message' => $this->user],[]);
        }else{
            return IndexRes::resultData(422,[],['message' => 'Your Authorization not found']);
        }
    }

    private function getAuthorization($request){
        $ip             = $request->ip();
        $header         = $request->bearerToken();
        try
        {
            $decoded = base64_decode($header, true);

            if ( base64_encode($decoded) === $header ) {
                if(strpos($decoded, ':') !== false) {
                    $explode    = explode(':', $decoded);
                    $user = User::where([['username',$explode[0]],["password",sha1($explode[1])],["user_role_id","api-user"],["status","active"]])->first();
                    // $user = User::where([['username',$explode[0]],["password",sha1($explode[1])],["user_role_id","api-user"],["status","active"],["mobile_token_expiry",$api]])->first(); // for production
                    if($user){
                        $this->user = $user;
                        return true;
                    }else{
                        return false;
                    }
                 } else {
                    return false;
                }
            }
            else {
                return false;
            }
        }
        catch(Exception $e)
        {
            // If exception is caught, then it is not a base64 encoded string
            return false;
        }
    }

}