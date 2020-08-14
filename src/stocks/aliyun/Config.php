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
            'accessKey_id'                => FormItem::make()->title('accessKey_id'),
            'accessKey_secret'            => FormItem::make()->title('accessKey_secret'),
            'vod_region_id' => FormItem::make()->title('Vod地域标识')->help('前往 <a href="https://help.aliyun.com/document_detail/98194.html?spm=a2c4g.11186623.2.41.23a93893gs27tV" target="_blank" class="el-link el-link--primary">点播中心和访问域名</a> 查看地域标识'),
            'vod_video_template_group_id' => FormItem::make()->title('Vod视频转码模板组ID')->help('填写后，上传的视频将使用模板组配置的转码方案进行转码'),
            'vod_audio_template_group_id' => FormItem::make()->title('Vod音频转码模板组ID')->help('填写后，上传的音频将使用模板组配置的转码方案进行转码'),
            'bucket'                      => FormItem::make()->title('OSS储存bucket空间名称')->help('若不需要上传文件类型的资源时，可不填写'),
            'domain'                    => FormItem::make()->title('OSS域名')->help('使用OSS加速域名访问资源，可不填写'),
            'endpoint'                    => FormItem::make()->title('OSS节点')->help('若不需要上传文件类型的资源时，可不填写'),
            'internal_endpoint'           => FormItem::make()->title('OSS内网节点')->help('1. 当服务器与储存空间属于同一地域时,可配置内网节点上传<br />2. 否则请勿填写该配置<br />3. 若不需要上传文件类型的资源时，可不填写'),
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
