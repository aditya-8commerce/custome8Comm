<?php
namespace App\Http\Controllers\Sci; 

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
use Concerns\InteractsWithInput;

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
use App\Models\OrderTypeMaster;
use App\Models\Warehouse\Honeywell\PutSku;
use App\Models\Warehouse\Honeywell\InboundAsn;


class IndexDemoController extends Controller
{
    public $user   = [];

    public function __construct(){
        
    }

    public function index(Request $request){
        $token = $request->bearerToken();
        try{
            if($token){
                $isBase64Encoded    = ApiLog::isBase64Encoded($token);
                if($isBase64Encoded){
                    $checkToken  = ApiLog::isExplode($isBase64Encoded);
                    if($checkToken){
                        $checkUser = $this->checkUser($checkToken);
                        if($checkUser){
                            return IndexRes::resultData(200,$checkUser,[]);
                        }else{
                            return IndexRes::resultData(422,[],['message' => 'Invalid Username Or Password']);
                        }
                    }else{
                        return IndexRes::resultData(422,[],['message' => 'Invalid Token']);
                    }
                }else{
                    return IndexRes::resultData(422,[],['message' => 'Invalid Bearer Token']);
                }
            }else{
                return IndexRes::resultData(422,[],['message' => 'Invalid Authorization']);
            }
        }catch(Exception $e){
            return IndexRes::resultData(422,[],['message' => $e]);
        }
    }


    public function getOrderType(Request $request){
        $token = $request->bearerToken();
        try{
            if($token){
                $isBase64Encoded    = ApiLog::isBase64Encoded($token);
                if($isBase64Encoded){
                    $checkToken  = ApiLog::isExplode($isBase64Encoded);
                    if($checkToken){
                        $checkUser = $this->checkUser($checkToken);
                        if($checkUser){
                            $model  = OrderTypeMaster::select('order_type_name','order_type')->get();
                            return IndexRes::resultData(200,$model,[]);
                        }else{
                            return IndexRes::resultData(422,[],['message' => 'Invalid Username Or Password']);
                        }
                    }else{
                        return IndexRes::resultData(422,[],['message' => 'Invalid Token']);
                    }
                }else{
                    return IndexRes::resultData(422,[],['message' => 'Invalid Bearer Token']);
                }
            }else{
                return IndexRes::resultData(422,[],['message' => 'Invalid Authorization']);
            }
        }catch(Exception $e){
            return IndexRes::resultData(422,[],['message' => $e]);
        }
    }

    public static function checkUser($array) 
    {
        try
        {
            $model = User::select('name', 'email','user_role_id','status','phone','mobile','company_id','fulfillment_center_id')->where([["username" , $array[0]] , ["password", sha1($array[1])],["user_role_id","api-user"],["status","active"]])->first();
            if ($model) {
                return $model;
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