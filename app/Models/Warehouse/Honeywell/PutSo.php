<?php
namespace App\Models\Warehouse\Honeywell;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Client as Client;
use Psr\Http\Message\RequestInterface;
use App\Http\Controllers\ApiLogController as ApiLog;
use App\Models\OrderTypeMaster;


class PutSo
{
	/*
	** demo
	*/
    public $endpointWMSTest   = 'http://147.139.135.96:19192/datahub/FluxWmsJsonApi/';
	
	/*
	** production
	*/
    public $endpointWMS   = 'http://147.139.134.156:19192/datahub/FluxWmsJsonApi/';
    protected $apiLog;

    public function __construct()
    {
        $this->apiLog           = new ApiLog;
        $this->client           = new Client();
    }
	
	/*
	**  SKU Information
	*/
    public function index($datas, $order_type = '',$consigneeId='') {
        $InterfaceMethod    = 'POST'; 
        $method             = 'putSalesOrder'; 
        $messageid          = "SO"; 
        $client_customerid  = 'FLUXWMSJSON';  // Fixed value
        $client_db          = "FLUXWMSJSON"; // Fixed value
        $apptoken           = "80AC1A3F-F949-492C-A024-7044B28C8025"; 
        $appkey             = "test";
        $timestamp          = date('Y-m-d H:i:s'); // Fixed value
        $header             = [];
        $detailsItem        = []; 
		$no = 1;

        if(empty($order_type)){
            $order_type     = 'NOM';
            $consigneeId    = 'NOM';	

            if(strtolower($datas->order_source)=='b2b'){
                $order_type     = 'B2BO'; 
                $consigneeId    = 'B2B';
            }else{
                $orderType  = OrderTypeMaster::where('order_type_name','=',$datas->order_type)->first();
                if($orderType){
                    $order_type     = $orderType->order_type_code; 
                    $consigneeId    = $orderType->order_type_code; 
                }
                
            }

        }

        if (strtolower($datas->company_id)=='ecing' || strtolower($datas->company_id)=='ecingsal' || strtolower($datas->company_id)=='rbizb2b')
		{
			if(strtolower($datas->order_type)=='transfer_outbound'){
				$order_type     = 'TROF';
				$consigneeId    = 'TROF';				
			}else{
				$order_type     = 'B2BO';
				$consigneeId    = 'B2B';
			}
        }
        
        if(strtolower($order_type) == 'b2bo'){
            $consigneeId    = 'B2B';
        }

        
        $$userDefine2   = $this->checkUserDefine2(strtolower($datas->courier_id));
        

		$dest_name	= strtoupper($data->dest_name);

		if(strtolower($data->company_id) == 'ecbright'){
			$consigneeId 	= strtoupper($data->dest_name);
			$dest_name		= strtoupper($data->dest_remarks);
		}
		

        foreach($datas->orderDetails as $k=>$row) {
			if(empty($row->price) || $row->price === NULL){ 
				$p = 0;
			}else{ 
				$p = $row->price;
			}
            
            if(strtolower($datas->order_type)=='put_to_store' || strtolower($datas->order_type)=='crossdock'){
				$detailsItem[] = array(
						"lineNo"	   => $k+1, 
						"customerId"   => $datas->company_id, 
						"sku"          => $row->sku_code,
						"lotAtt08"	   => 'OK', // enum ['OK','HOLD']
						"qtyOrdered"   => $row->qty_order,
						"lotAtt07"	   => $row->crossdock_no,
						// "lotAtt10"	   => strtoupper($datas->order_no),
						"price"		   => $p
				);
			}else{
				$detailsItem[] = array(
						"lineNo"	   => $k+1, 
						"customerId"   => $datas->company_id, 
						"sku"          => $row->sku_code,
						"lotAtt08"	   => 'OK', // enum ['OK','HOLD']
						"qtyOrdered"   => $row->qty_order,
						"lotAtt07"	   => $row->crossdock_no,
						"price"		   => $p
					);					
			}
        }
        $datasRecord = array(
            "xmldata" =>  array(
                "header" => [
                    array(
                        "orderNo"         	   => strtoupper($datas->order_no), // tbl_order_header.order_no
                        "soReference1"         => strtoupper($datas->order_no), // tbl_order_header.order_no
                        "soReference2"         => strtoupper($datas->order_no), // tbl_order_header.order_no
                        "orderType"            => strtoupper($order_type),
                        "customerId"           => strtoupper($datas->company_id), // tbl_po_header.company_id
                        "orderTime"            => strtoupper($datas->create_time),
                        "soReference5"         => strtoupper($datas->awb_no),
                        "deliveryNo"           => strtoupper($datas->awb_no),
                        "consigneeId"          => strtoupper($consigneeId),
                        "consigneeName"        => $dest_name,
                        "consigneeCountry"     => strtoupper($datas->dest_country),
                        "consigneeProvince"    => strtoupper($datas->dest_province),
                        "consigneeCity"        => strtoupper($datas->dest_city),
                        "consigneeTel1"        => strtoupper($datas->dest_mobile),
                        "consigneeTel2"        => strtoupper($datas->dest_phone),
                        "consigneeZip"         => strtoupper($datas->dest_postal_code),
                        "consigneeMail"        => strtoupper($datas->dest_email),
                        "consigneeAddress1"    => substr(strtoupper($datas->dest_address1),0,200),
                        "consigneeAddress2"    => strtoupper($datas->dest_area),
                        "consigneeAddress3"    => strtoupper($datas->dest_sub_area),
                        "userDefine2"          => $userDefine2,
                        "notes"                => strtoupper($datas->dest_remarks),
                        "hedi01"               => strtoupper($datas->order_source),
                        "hedi02"               => strtoupper($datas->order_amount),
                        "hedi03"               => strtoupper($datas->dest_name),
                        "hedi05"               => ($datas->cod?'Y':'N'),
                        "hedi07"               => $datas->shipping_amount,
                        "hedi09"               => $datas->insurance_amount,
                        "warehouseId"          => strtoupper($datas->fulfillment_center_id),
                        "carrierId"            => strtoupper($datas->courier_id),
                        "carrierName"          => strtoupper($datas->courier_id),
                        "detailsItem"          => $detailsItem
                    )
                ]
            )
        );

        try {
            $response = $this->client->request('POST', $this->endpointWMS, [
                'form_params' => [
                    'method'                => $method,
                    'client_customerid'     => $client_customerid,
                    'client_db'             => $client_db,
                    'messageid'             => $messageid,
                    'apptoken'              => $apptoken,
                    'appkey'                => $appkey,
                    'sign'                  => 1,
                    'timestamp'             => $timestamp,
                    'data'                  => json_encode($datasRecord)
                ]
            ]);

            return json_decode($response->getBody(),TRUE);

        }catch (ClientException $e) {
            $responseError = $e->getResponse();
            $this->apiLog->insertLog('Warehouse\Honeywell\InboundAsn','production',(string)$responseError->getBody(), 'ERROR' , json_encode($datas),'/','honeywell');
            return $responseError;
        }
    }

    private function checkUserDefine2($courierId){
        $array  = ['gosend-sameday','gosend-samedaycl','gosend-instant','gosend-instantcl','grab-instant','grab-instantcl',
        'grab-sameday','grab-samedaycl','anteraja','sicepat','sicepat_best','sicepat_cashless','sicepat_best_cashless',
        'sicepat_bestcl','sicepatcl'];

        if(in_array($courierId, $array)){
            return 'SAME';
        }else{
            return 'REGULAR';
        }
    }


	private function cartonGroup($company_id){
		
		$cartonGroup	= $company_id;
		
		if(strtoupper($company_id)=='ECCOCO' || strtoupper($company_id)=='ECCOCO_2' || strtoupper($company_id)=='ECGANEN' || strtoupper($company_id)=='ECTUP'){
			$cartonGroup		= 'STANDARD';
		}
		
		if(strtoupper($company_id)=='ECINGCOM' || strtoupper($company_id)=='ECINGSAL' || strtoupper($company_id)=='ECING_TSTER'){
			$cartonGroup		= 'ECING';
		}
		
		if(strtoupper($company_id)=='ECZAP_2'){
			$cartonGroup		= 'ECZAP';
		}
		
		if(strtoupper($company_id)=='RBIZB2B' || strtoupper($company_id)=='RBIZBUSTAR' || strtoupper($company_id)=='RBIZNAMEERA'){
			$cartonGroup		= 'RBIZ';
		}
		
		if(strtoupper($company_id)=='LOGISTIC_99'){
			$cartonGroup		= 'LOG99';
		}
		
		return $cartonGroup;
	}
}