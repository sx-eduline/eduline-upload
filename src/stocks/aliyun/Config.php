<?php
declare (strict_types = 1);
namespace eduline\upload\stocks\aliyun;

use app\admin\logic\system\Config as SystemConfig;
use eduline\admin\libs\pageform\FormItem;
use eduline\admin\page\PageForm;
use eduline\upload\interfaces\ConfigInterface;

class Config implements ConfigInterface
{
    protected static $key = 'system.package.upload.aliyun';
    public static function page(): PageForm
    {
        $fields = [
            'accessKey_id'      => FormItem::make()->title('accessKey_id'),
            'accessKey_secret'  => FormItem::make()->title('accessKey_secret'),
            'bucket'            => FormItem::make()->title('储存bucket空间名称'),
            'endpoint'          => FormItem::make()->title('节点'),
            'internal_endpoint' => FormItem::make()->title('内网节点')->help('当服务器与储存空间属于同一地域时,可配置内网节点上传<br />否则请勿填写该配置'),
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
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-03-28
     * @return   [type]                         [description]
     */
    public static function get($name = null)
    {
        $config = SystemConfig::get(self::$key, []);

        if ($name) {
            return isset($config[$name]) ? $config[$name] : null;
        }

        return $config;
    }
}
