<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix'     => 'api/adminconsole/',
    'middleware' => 'antAuth'
], function () {

    Route::get('test', 'Antmin\Http\Controllers\DemoController@operate');

    Route::any('systemLogin',           'Antmin\Http\Controllers\AccountController@login');
    Route::any('systemIndexOperate',    'Antmin\Http\Controllers\EnterController@operate');
    Route::any('systemUploadOperate',   'Antmin\Http\Controllers\UploadController@operate');
    Route::any('systemUploadEditor',    'Antmin\Http\Controllers\UploadController@editorUpload');
    Route::any('systemVersionOperate',  'Antmin\Http\Controllers\VersionController@operate');
    Route::any('systemLogsOperate',     'Antmin\Http\Controllers\LogsController@operate');
    Route::any('systemItemOperate',     'Antmin\Http\Controllers\ItemController@operate');


});
