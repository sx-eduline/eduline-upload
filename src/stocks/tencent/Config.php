<?php
declare (strict_types = 1);
namespace eduline\upload\stocks\tencent;

use app\admin\logic\system\Config as SystemConfig;
use eduline\admin\libs\pageform\FormItem;
use eduline\admin\page\PageForm;
use eduline\upload\interfaces\ConfigInterface;

class Config implements ConfigInterface
{
    protected static $key = 'system.package.upload.tencent';
    public static function page(): PageForm
    {
        $fields = [
            'cos_secret_id'  => FormItem::make()->title('云API密钥-SecretId')->help('推荐创建子账号,赋予子账号Cos存储权限'),
            'cos_secret_key' => FormItem::make()->title('云API密钥-SecretKey')->help('推荐创建子账号,赋予子账号Cos存储权限'),
            'bucket'         => FormItem::make()->title('储存桶名称'),
            'cos_region'     => FormItem::make()->title('存储桶地域'),
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
