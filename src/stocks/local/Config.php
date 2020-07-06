<?php
declare (strict_types = 1);
namespace eduline\upload\stocks\local;

use app\admin\logic\system\Config as SystemConfig;
use eduline\admin\libs\pageform\FormItem;
use eduline\admin\page\PageForm;
use eduline\upload\interfaces\ConfigInterface;

class Config implements ConfigInterface
{
    protected static $key = 'system.package.upload.local';
    /**
     * 配置界面
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-03-28
     * @return   [type]                         [description]
     */
    public static function page(): PageForm
    {
        $fields = [
            'bucket'     => FormItem::make()->title('磁盘名称')->help('请确保该磁盘已经在config/filesystem.php中配置')->required(),
            'upload_dir' => FormItem::make()->title('存放目录')->help('默认存放磁盘配置的根目录中'),
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
        $config = SystemConfig::get(self::$key, ['bucket' => 'local', 'upload_dir' => 'upload']);

        if ($name) {
            return isset($config[$name]) ? $config[$name] : null;
        }

        return $config;
    }
}
