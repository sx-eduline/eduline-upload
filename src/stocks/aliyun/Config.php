<?php
declare (strict_types=1);

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
            'accessKey_id'          => FormItem::make()->title('accessKey_id')->required(),
            'accessKey_secret'      => FormItem::make()->title('accessKey_secret')->required(),
            'vod_region_id'         => FormItem::make()->title('Vod地域标识')->help('前往 <a href="https://help.aliyun.com/document_detail/98194.html?spm=a2c4g.11186623.2.41.23a93893gs27tV" target="_blank" class="el-link el-link--primary">点播中心和访问域名</a> 查看地域标识')->required(),
            'private_key'           => FormItem::make()->title('回调鉴权密钥')->help('1. 需与阿里云点播回调配置中的回调鉴权密钥一致<br />2. 若需要修改该密钥，务必在所有视频状态为正常之后修改，否则将可能导致视频状态异常')->required(),
            // 'hls_template_id'       => FormItem::make()->title('HLS加密转码模板ID')->help('前往 <a href="https://vod.console.aliyun.com/?spm=a2c4g.11186623.0.0.75c312e0y3qdKU#/settings/transcode/list" target="_blank" class="el-link el-link--primary">转码模板组</a> 添加并查看')->required(),
            'hls_play_acc_id'       => FormItem::make()->title('HLS accessKeyId')->required()->help('建议使用子账号accessKeyId，未配置时将使用主账号配置的accessKeyId'),
            'hls_play_acc_secret'   => FormItem::make()->title('HLS accessKeySecret')->required()->help('建议使用子账号accessKeySecret，未配置时将使用主账号配置的accessKeySecret'),
            // 'hls_play_role_arn'     => FormItem::make()->title('HLS roleArn')->required()->help('前往 <a href="https://ram.console.aliyun.com/roles" target="_blank" class="el-link el-link--primary">角色</a> 添加并查看，<a href="https://help.aliyun.com/document_detail/93689.html" target="_blank" class="el-link el-link--primary">RAM角色概览</a>'),
            'vod_video_workflow_id' => FormItem::make()->title('Vod视频处理工作流ID')->help('填写后，上传的视频将使用该工作流处理'),
            'vod_audio_workflow_id' => FormItem::make()->title('Vod音频处理工作流ID')->help('填写后，上传的音频将使用该工作流处理'),
            'bucket'                => FormItem::make()->title('OSS储存bucket空间名称')->help('若不需要上传非音视频、图片资源时，可不填写'),
            'domain'                => FormItem::make()->title('OSS域名')->help('使用OSS加速域名访问资源，可不填写'),
            'endpoint'              => FormItem::make()->title('OSS节点')->help('若不需要上传非音视频、图片资源时，可不填写'),
            'internal_endpoint'     => FormItem::make()->title('OSS内网节点')->help('1. 当服务器与储存空间属于同一地域时,可配置内网节点上传<br />2. 否则请勿填写该配置<br />3. 若不需要上传非音视频、图片资源时，可不填写'),
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
