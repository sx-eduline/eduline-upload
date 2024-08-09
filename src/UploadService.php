<?php

namespace eduline\upload;

use think\facade\Route;
use think\Service;

class UploadService extends Service
{
    public function boot()
    {
        $this->registerRoutes(function () {
            /** 接口路由 */
            Route::group('admin/system/package/upload', function () {
                Route::get('/list', '@index')->name('system.package.upload'); // 上传配置页面
                Route::get('/<stock>/config', '@config')->pattern(['stock' => '[a-zA-Z_]+'])->name('system.package.upload.config'); // 上传配置页面
            })->prefix('\eduline\upload\admin\service\Config')->middleware(['adminRoute', 'init', 'bindLoginUser']);
            // 阿里云视频点播事件通知路由
            Route::post('/aliyun/vod/event', '\eduline\upload\stocks\aliyun\event\VodEvent@handle')->middleware(['\eduline\upload\stocks\aliyun\middleware\VodAuth']);
            // 腾讯云视频点播事件通知路由
            Route::post('/tencent/vod/event', '\eduline\upload\stocks\tencent\event\VodEvent@handle');
            // bokecc云视频点播事件通知路由
            Route::get('/bokecc/vod/event', '\eduline\upload\stocks\bokecc\event\VodEvent@handle');
        });
    }
}
