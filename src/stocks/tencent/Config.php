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
            'secret_id'           => FormItem::make()->title('云API密钥-SecretId')->help('推荐创建子账号,赋予子账号Cos/Vod等权限'),
            'secret_key'          => FormItem::make()->title('云API密钥-SecretKey')->help('推荐创建子账号,赋予子账号Cos/Vod等权限'),
            'vod_region'          => FormItem::make()->title('云点播接入地域英文简称')->help('1. 前往 <a href="https://cloud.tencent.com/document/api/266/31756#.E5.9C.B0.E5.9F.9F.E5.88.97.E8.A1.A8" target="_blank" class="el-link el-link--primary">已支持地域列表</a> 查看地域英文简称<br />2. 接入地域如果与服务器所在地域越靠近，上传资源将会更快'),
            'vod_storage_region'  => FormItem::make()->title('云点播储存地域英文简称')->help('前往 <a href="https://cloud.tencent.com/document/product/266/9760#.E5.B7.B2.E6.94.AF.E6.8C.81.E5.9C.B0.E5.9F.9F.E5.88.97.E8.A1.A8" target="_blank" class="el-link el-link--primary">已支持地域列表</a> 查看地域英文简称'),
            'vod_video_procedure' => FormItem::make()->title('视频转码任务流名称')->help('填写后，上传的视频将按照任务流执行'),
            'vod_audio_procedure' => FormItem::make()->title('音频转码任务流名称')->help('填写后，上传的音频将按照任务流执行'),
            'bucket'              => FormItem::make()->title('Cos储存桶名称'),
            'cos_region'          => FormItem::make()->title('Cos存储桶地域'),
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
