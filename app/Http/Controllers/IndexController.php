<?php
namespace App\Http\Controllers;  


use Ramsey\Uuid\Uuid;

use App\Models\Warehouse\Honeywell\PutSku;
use App\Models\Sku;
use App\Models\PoHeader;

class IndexController extends Controller
{
	
/* 
error status code
	200	success
	404	Not Found (page or other resource doesn’t exist)
	401	Not authorized (not logged in)
	403	Logged in but access to requested area is forbidden
	400	Bad request (something wrong with URL or parameters)
	422	Unprocessable Entity (validation failed)
	500	General server error
*/

    public function __construct(){
         
    }

	public function index(){
		return response()
		->json(['status'=>200 ,'datas' => ['message' => 'API Custom 8commerce'], 'errors' => []])
		->setStatusCode(200)
		->withHeaders(['Content-Type' => 'application/json',]);
	}
	
	public function pagenotfound(){
		return response()
		->json(['status'=>404 ,'datas' => [], 'errors' => ['message' => 'Not Found!']])
		->setStatusCode(404)
		->withHeaders(['Content-Type' => 'application/json',]);
	 
	}
	
	public function servererror(){
		return response()
		->json(['status'=>500 ,'datas' => [], 'errors' => ['message' => 'General server error!']])
		->setStatusCode(500)
		->withHeaders(['Content-Type' => 'application/json',]);
	} 
	
	public function coba(){ 
       $check      = PoHeader::where([['company_id', '=' , 'RBIZ_TEST'], ['po_no', '=' , '2400000059'], ["status","<>","cancelled"]])->first();
        if($check) {
           echo 'ada';
        }else{
			echo 'tidak';
		}
	} 
	
}
