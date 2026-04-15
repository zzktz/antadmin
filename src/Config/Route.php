<?php

use Illuminate\Support\Facades\Route;


use Antmin\Http\Controllers\AccountController;
use Antmin\Http\Controllers\EnterController;
use Antmin\Http\Controllers\UploadController;
use Antmin\Http\Controllers\VersionController;
use Antmin\Http\Controllers\LogsController;
use Antmin\Http\Controllers\ItemController;
use Antmin\Http\Controllers\RequestLogController;
use Antmin\Http\Controllers\OperateLogController;


Route::group([
    'prefix'     => 'api/adminconsole/',
    'middleware' => 'antAuth'
], function () {

    Route::any('systemLogin', [AccountController::class, 'login']);
    Route::any('systemRegister', [AccountController::class, 'register']);
    Route::any('sendCodeByEmail', [AccountController::class, 'sendCodeByEmail']);

    Route::any('systemUploadEditor', [UploadController::class, 'editorUpload']);
    Route::any('systemIndexOperate', [EnterController::class, 'operate']);
    Route::any('systemUploadOperate', [UploadController::class, 'operate']);
    Route::any('systemVersionOperate', [VersionController::class, 'operate']);
    Route::any('systemLogsOperate', [LogsController::class, 'operate']);
    Route::any('systemItemOperate', [ItemController::class, 'operate']);
    Route::any('requestLogOperate', [RequestLogController::class, 'operate']);
    Route::any('operateLogOperate', [OperateLogController::class, 'operate']);

});
