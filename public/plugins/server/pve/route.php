<?php 

use think\facade\Route;

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

# 前台,可登录不可登录的接口
Route::group('console/v1',function (){

	// 订购页面
    Route::get('product/:id/pve/order_page', "\\server\\pve\\controller\\home\\CloudController@orderPage");
    Route::get('product/:id/pve/image', "\\server\\pve\\controller\\home\\CloudController@imageList");
    Route::post('product/:id/pve/duration', "\\server\\pve\\controller\\home\\CloudController@getAllDurationPrice");
    Route::get('product/:id/pve/config_limit', "\\server\\pve\\controller\\home\\CloudController@getAllConfigLimit");
    Route::get('product/:id/pve/vpc_network/search', "\\server\\pve\\controller\\home\\CloudController@vpcNetworkSearch");
    Route::get('product/:id/pve/line/:line_id', "\\server\\pve\\controller\\home\\CloudController@lineConfig");
    Route::get('product/:id/pve/data_center', "\\server\\pve\\controller\\home\\CloudController@dataCenterSelect");

    // vnc
    Route::get('pve/:id/vnc', "\\server\\mf_cloud\\controller\\home\\CloudController@vncPage");


})->allowCrossDomain([
    'Access-Control-Allow-Origin'        => $origin,
    'Access-Control-Allow-Credentials'   => 'true',
    'Access-Control-Max-Age'             => 600,
])->middleware(\app\http\middleware\Check::class);

// Route::get(DIR_ADMIN . '/v1/mf_cloud/:id/vnc', "\\server\\mf_cloud\\controller\\admin\\CloudController@vncPage");