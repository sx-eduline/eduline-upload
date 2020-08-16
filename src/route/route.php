<?php
use think\facade\Route;

/** 接口路由 */
Route::group('admin/system/package/upload', function () {
    Route::get('/list', 		  '@index')->name('system.package.upload'); // 上传配置页面
    Route::get('/<stock>/config', '@config')->pattern(['stock' => '[a-zA-Z_]+'])->name('system.package.upload.config'); // 上传配置页面
})->prefix('\eduline\upload\admin\service\Config')->middleware(['adminRoute']);
