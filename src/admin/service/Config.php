<?php
declare (strict_types=1);

namespace eduline\upload\admin\service;

use app\admin\logic\system\Config as SystemConfig;
use app\common\service\BaseService;
use eduline\admin\libs\pagelist\ListItem;
use eduline\admin\page\PageList;
use eduline\upload\Stock;

class Config extends BaseService
{
    /**
     * 上传列表
     * Author   Martinsun<syh@sunyonghong.com>
     * Date:  2020-03-27
     *
     * @return   [type]                         [description]
     */
    public function index()
    {
        $data   = Stock::getStocks();
        $upload = SystemConfig::get('system.package.upload', [], request()->mhm_id, true);
        // 查询配置
        foreach ($data as $key => $stock) {
            // 储存配置key
            $__key                = 'system.package.upload.' . $stock['key'];
            $data[$key]['__key']  = $__key;
            $data[$key]['config'] = SystemConfig::get($__key, [], request()->mhm_id, true);
            $data[$key]['status'] = (isset($upload['stock']) && $upload['stock'] == $stock['key']) ? 1 : 0;
        }
        // 定义字段
        $keyList = [
            'name'   => ListItem::make()->title('名称'),
            'key'    => ListItem::make()->title('标识'),
            'desc'   => ListItem::make()->title('描述'),
            'status' => ListItem::make('custom')->title('启用状态'),
        ];

        // 设置表单
        $list = app(PageList::class);
        // 表单字段
        $list->pageKey = $keyList;
        $list->datas   = $data;

        return $list->send();
    }

    /**
     * 上传配置
     * Author   Martinsun<syh@sunyonghong.com>
     * Date:  2020-03-27
     *
     * @return   [type]                         [description]
     */
    public function config($stock)
    {
        // 配置界面
        $form = Stock::getStockConfigPage($stock);

        return $form->send();
    }
}
