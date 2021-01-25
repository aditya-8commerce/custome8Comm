<?php
namespace App\Http\Controllers\Xstore; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
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
 
use App\Models\OrderHeader;
use App\Models\OrderDetail;
use App\Models\OrderStatusTracking;
use App\Models\Store;

class IndexController extends Controller
{

    public function __construct(){
        
    }
    

	public function index(){
        return IndexRes::resultData(200,['message' => 'API Custome 8commerce for Xstore'],[]);
    }

	public function login(Request $request){
        $validator = Validator::make(
            $request->all(),
            array(
                'company_id'	        => 'required|max:255',
                'store_code'	        => 'required|max:255',
                'pic_email'	            => 'required|max:255',
            )
        );

        if($validator->fails()){
            return IndexRes::resultData(422,[],["messages" => $validator->errors()]);
        }else{
            $selectedStore = Store::where([['store_code', '=', $request->store_code],["company_id", "=" , $request->company_id],["pic_email", "=" , $request->pic_email]])->first();
            if ($selectedStore) {
                   $token = $this->jwt($selectedStore);
                   $data = ['token' => $token, 'type' => 'bearer'];
                   return IndexRes::resultData(200,$data,[]);     
           }else {
                return IndexRes::resultData(422,[],["messages" =>'user not found']);      
           }
        }
    }

    private function jwt(Store $store) {
    
        $payload = [
            'iss' => "bearer",
            'sub' => $store,
            'iat' => time(),
            'exp' => time() + 1440*60 // token kadaluwarsa setelah 3600 detik
        ];
        
        return JWT::encode($payload, env('APP_KEY'));
    
    }
}