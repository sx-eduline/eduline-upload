<?php
namespace eduline\upload;

use think\Route;
use think\Service;

class UploadService extends Service
{
    public function boot()
    {
        $this->registerRoutes(function (Route $route) {
            /** 接口路由 */
            $route->group('admin/system/package/upload', function () use ($route) {
                $route->get('/list', '@index')->name('system.package.upload'); // 上传配置页面
                $route->get('/<stock>/config', '@config')->pattern(['stock' => '[a-zA-Z_]+'])->name('system.package.upload.config'); // 上传配置页面
            })->prefix('\eduline\upload\admin\service\Config')->middleware(['adminRoute']);

        })
    }
}
