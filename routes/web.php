<?php

$router->group(['prefix' => 'v1'], function () use ($router) {
	$router->get('/',['as' => 'index','uses' => 'IndexController@index']); 
	$router->get('/page-not-found',['as' => 'pagenotfound','uses' => 'IndexController@pagenotfound']); 
	$router->get('/server-error',['as' => 'servererror','uses' => 'IndexController@servererror']);
	$router->get('/version', function () use ($router) {
		return $router->app->version();
	});

	$router->get('/coba',['as' => 'coba','uses' => 'IndexController@coba']); 
	 


	$router->group(['prefix' => 'luxasia', 'namespace' => 'Luxasia'], function () use ($router) {
		
		$router->get('/',['as' => 'luxasiaIndex','uses' => 'IndexController@index']);
		$router->get('sku',['as' => 'luxasiaImportSKU','uses' => 'IndexController@sku']);
		$router->get('po',['as' => 'luxasiaImportPo','uses' => 'IndexController@po']);
		$router->get('receipts-po',['as' => 'luxasiaReceiptsPo','uses' => 'IndexController@receiptsPo']);
		$router->get('stock',['as' => 'luxasiaStock','uses' => 'IndexController@stock']);
		$router->get('stock-transfer',['as' => 'luxasiaStockTransfer','uses' => 'IndexController@stockTransfer']);
		$router->get('so',['as' => 'luxasiaSo','uses' => 'IndexController@so']);
		$router->get('so-return',['as' => 'luxasiaSo','uses' => 'IndexController@soReturn']);
	});
	

	$router->group(['prefix' => 'xpress', 'namespace' => 'Xpress'], function () use ($router) {
		
		$router->get('/',['as' => 'xpressIndex','uses' => 'IndexController@index']);
		$router->post('/login',['as' => 'xpressLogin','uses' => 'IndexController@login']);
		$router->get('/search-order/{tripId}',['as' => 'xpressSearchOrder','uses' => 'IndexController@SearchOrder']);
		$router->get('/search-order-detail/{tripId}',['as' => 'xstoreSearchOrderDetail','uses' => 'IndexController@SearchOrderDetail']);
		$router->get('/trip-detail/{tripDetailId}',['as' => 'xpressDetailTripOrder','uses' => 'IndexController@detailTripOrder']);
		$router->post('/update-status/{tripDetailId}',['as' => 'xpressUpdateStatus','uses' => 'IndexController@updateStatus']);
	});

	$router->group(['prefix' => 'xstore', 'namespace' => 'Xstore'], function () use ($router) {
		$router->get('/',['as' => 'xstoreIndex','uses' => 'IndexController@index']);
		$router->post('/login',['as' => 'xstoreLogin','uses' => 'IndexController@login']);
		$router->get('/search-order/{storeId}',['as' => 'xstoreSearchOrder','uses' => 'IndexController@SearchOrder']);
		$router->get('/order-detail/{storeId}/{orderId}',['as' => 'xstoreDetailOrder','uses' => 'IndexController@DetailOrder']);
		$router->get('/search-detail-order/{storeId}/{orderId}',['as' => 'xstoreSearchDetailOrder','uses' => 'IndexController@SearchDetailOrder']);
		$router->post('/update-qty-delivered/{storeId}/{idOrderDetail}',['as' => 'xstoreUpdateQtyDelivered','uses' => 'IndexController@updateQtyDelivered']);
	});


	$router->group(['prefix' => 'sci', 'namespace' => 'Sci'], function () use ($router) {		
		$router->group(['prefix' => 'demo'], function () use ($router) {
			$router->get('/',['as' => 'apiIndexDemo','uses' => 'IndexDemoController@index']);
			$router->post('/',['as' => 'apiIndexPostDemo','uses' => 'IndexDemoController@index']);
			$router->get('/order-type',['as' => 'apiOrderTypeDemo','uses' => 'IndexDemoController@getOrderType']);
			
			
		});
		
	});


});
 