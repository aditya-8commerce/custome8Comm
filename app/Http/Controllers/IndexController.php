<?php
namespace App\Http\Controllers;  


use App\Models\Warehouse\Honeywell\PutSku;
use App\Models\Sku;

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
		$get = '[{"Order_type":"O","Inbound_order":"0180074629","Inbound_item":"000010","Store_id":"0056","StorageLocation":"0001","ItemCode":"BVF50211","Qty":"170.000","UOM":"PC","PO_NUMBER":"2000025606","PO_ITEM":"000010","DELIV_DATE":"18.06.2020","VENDOR_CODE":"0010000011","VENDOR_NAME":"BVLGARI PARFUMS SA","VENDOR_ADDRESS":"RUE DE MONRUZ 34,CASE POSTALE 94,NEUCHATEL,2000","Remarks1":"","Remarks2":"","Remarks3":""},{"Order_type":"O","Inbound_order":"0180074629","Inbound_item":"000020","Store_id":"0056","StorageLocation":"0001","ItemCode":"000000000000095653","Qty":"455.000","UOM":"PC","PO_NUMBER":"2000025606","PO_ITEM":"000020","DELIV_DATE":"18.06.2020","VENDOR_CODE":"0010000011","VENDOR_NAME":"BVLGARI PARFUMS SA","VENDOR_ADDRESS":"RUE DE MONRUZ 34,CASE POSTALE 94,NEUCHATEL,2000","Remarks1":"","Remarks2":"","Remarks3":""},{"Order_type":"O","Inbound_order":"0180074629","Inbound_item":"000030","Store_id":"0056","StorageLocation":"0001","ItemCode":"BVF47810","Qty":"180.000","UOM":"PC","PO_NUMBER":"2000025606","PO_ITEM":"000030","DELIV_DATE":"18.06.2020","VENDOR_CODE":"0010000011","VENDOR_NAME":"BVLGARI PARFUMS SA","VENDOR_ADDRESS":"RUE DE MONRUZ 34,CASE POSTALE 94,NEUCHATEL,2000","Remarks1":"","Remarks2":"","Remarks3":""},{"Order_type":"O","Inbound_order":"0180074629","Inbound_item":"000040","Store_id":"0056","StorageLocation":"0001","ItemCode":"BVF47814","Qty":"180.000","UOM":"PC","PO_NUMBER":"2000025606","PO_ITEM":"000040","DELIV_DATE":"18.06.2020","VENDOR_CODE":"0010000011","VENDOR_NAME":"BVLGARI PARFUMS SA","VENDOR_ADDRESS":"RUE DE MONRUZ 34,CASE POSTALE 94,NEUCHATEL,2000","Remarks1":"","Remarks2":"","Remarks3":""},{"Order_type":"O","Inbound_order":"0180074630","Inbound_item":"000010","Store_id":"0056","StorageLocation":"0001","ItemCode":"BVF52808","Qty":"10.000","UOM":"PC","PO_NUMBER":"2000025607","PO_ITEM":"000010","DELIV_DATE":"18.06.2020","VENDOR_CODE":"0010000011","VENDOR_NAME":"BVLGARI PARFUMS SA","VENDOR_ADDRESS":"RUE DE MONRUZ 34,CASE POSTALE 94,NEUCHATEL,2000","Remarks1":"","Remarks2":"","Remarks3":""},{"Order_type":"","Inbound_order":"","Inbound_item":"","Store_id":"","StorageLocation":"","ItemCode":"","Qty":"","UOM":"","PO_NUMBER":"","PO_ITEM":"","DELIV_DATE":"","VENDOR_CODE":"","VENDOR_NAME":"","VENDOR_ADDRESS":"","Remarks1":"","Remarks2":"","Remarks3":""}]';
		$arr = json_decode($get,TRUE);
		echo json_encode($get);
	} 
	
}
