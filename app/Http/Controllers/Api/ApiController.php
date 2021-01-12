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
use Illuminate\Support\Facades\Validator;
use PDF;
use PHPExcel; 
use PHPExcel_IOFactory;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use SH1HashServiceProvider;
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


class ApiController extends Controller
{
    public function __construct(){
        
    }

    public function index(){
        return IndexRes::resultData(200,['message' => 'API 8commerce Integrations'],[]);
    }

    public function getToken(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            array(
                'username'	            => 'required|max:45',
                'password'				=> 'required|max:255',
            )
        );
        if($validator->fails()){
            return IndexRes::resultData(422,[],["message" => $validator->errors()]);
        }else{
            $ip = $request->ip();
            $user = User::where([['username',$request->username],["password",sha1($request->password)],["user_role_id","api-user"],["status","active"]])->first();

            if (!$user) {
                $user = [];
            }
            return IndexRes::resultData(200,['token' => $user],[]);
        }

        // $token = Str::random(60);

        // $request->user()->forceFill([
        //     'mobile_token' => hash('sha256', $token),
        // ])->save();

        // $token = Hash::make($request->password);
        // $token = SH1HashServiceProvider::make($request->password);

        // return IndexRes::resultData(200,['token' => $token],[]);
    }

}