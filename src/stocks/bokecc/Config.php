<?php
declare (strict_types=1);

namespace eduline\upload\stocks\bokecc;

use app\admin\logic\system\Config as SystemConfig;
use eduline\admin\libs\pageform\FormItem;
use eduline\admin\page\PageForm;
use eduline\upload\interfaces\ConfigInterface;

class Config implements ConfigInterface
{
    protected static $key = 'system.package.upload.bokecc';

    public static function page(): PageForm
    {
        $help   = '前往 <a href="https://console.bokecc.com/index" target="_blank" class="el-link el-link--primary"> 获得场景视频 </a> 后台获取';
        $fields = [
            'userid' => FormItem::make()->title('账户ID')->required()->help($help),
            'apikey' => FormItem::make()->title('API Key')->help($help)->required(),
            'notify_url'=>FormItem::make()->title('上传视频回调')->help('接口地址+/bokecc/vod/event，如：https://api.domain.com/bokecc/vod/event')->required(),
        ];

        $form          = new PageForm();
        $form->pageKey = $fields;
        $form->withSystemConfig();
        $config          = self::get();
        $config['__key'] = self::$key;
        $form->datas     = $config;

        return $form;
    }

    /**
     * 获取配置
     * Author   Martinsun<syh@sunyonghong.com>
     * Date:  2020-03-28
     *
     * @return   [type]                         [description]
     */
    public static function get($name = null, $default = null, $mhm_id = null)
    {
        $mhm_id = $mhm_id ?? request()->mhm_id;
        $config = SystemConfig::get(self::$key, [], $mhm_id);

        if ($name) {
            return isset($config[$name]) ? $config[$name] : $default;
        }

        return $config;
    }
}
