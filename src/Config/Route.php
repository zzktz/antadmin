<?php

use Illuminate\Support\Facades\Route;


use Antmin\Http\Controllers\AccountController;

Route::group([
    'prefix'     => 'api/adminconsole/',
    'middleware' => 'antAuth'
], function () {

    Route::any('systemLogin',        [AccountController::class, 'login']);
    Route::any('systemUploadEditor', 'Antmin\Http\Controllers\UploadController@editorUpload');
    Route::any('systemIndexOperate', 'Antmin\Http\Controllers\EnterController@operate');
    Route::any('systemUploadOperate', 'Antmin\Http\Controllers\UploadController@operate');
    Route::any('systemVersionOperate', 'Antmin\Http\Controllers\VersionController@operate');
    Route::any('systemLogsOperate', 'Antmin\Http\Controllers\LogsController@operate');
    Route::any('systemItemOperate', 'Antmin\Http\Controllers\ItemController@operate');
    Route::any('requestLogOperate', 'Antmin\Http\Controllers\RequestLogController@operate');
    Route::any('operateLogOperate', 'Antmin\Http\Controllers\OperateLogController@operate');

});
