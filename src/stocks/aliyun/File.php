<?php
declare (strict_types=1);

namespace eduline\upload\stocks\aliyun;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Vod\Vod;
use AliyunVodUploader;
use app\common\model\Attach;
use eduline\upload\interfaces\FileInterface;
use eduline\upload\utils\Util;
use Exception;
use OSS\OssClient;
use think\exception\FileException;
use UploadImageRequest;
use UploadVideoRequest;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'voduploadsdk' . DIRECTORY_SEPARATOR . 'Autoloader.php';

class File implements FileInterface
{
    protected $config;

    public function __construct()
    {
        $this->config = Config::get();
    }

    /**
     * 本地上传 -- 不需要
     * Author   Martinsun<syh@sunyonghong.com>
     * Date:  2020-08-15
     *
     * @return   [type]                         [description]
     */
    public function putFile($savepath, $file, $savename)
    {
        throw new FileException('暂不支持该方式上传');

    }

    /**
     * 将本地文件上传到云端
     * Author   Martinsun<syh@sunyonghong.com>
     * Date:  2020-03-30
     *
     * @param string $path [description]
     * @param    [type]                         $file [description]
     * @param string $name [description]
     * @return   [type]                               [description]
     */
    public function putYunFile($attach)
    {
        try {
            $accessKeyId     = $this->config['accessKey_id'];
            $accessKeySecret = $this->config['accessKey_secret'];
            $filepath        = $attach->getAttr('filepath');
            // 更新为上传中
            Attach::update(['status' => 3], ['id' => $attach->id]);
            $status = 1;
            // 判断是否是图片 音视频
            if (Util::isVideo($attach->mimetype, $attach->extension)) {
                // 视频上传
                $uploader           = new AliyunVodUploader($accessKeyId, $accessKeySecret, $this->config['vod_region_id']);
                $uploadVideoRequest = new UploadVideoRequest($filepath, $attach->filename);
                // 是否配置了视频处理流程
                if ($this->config['vod_video_workflow_id'] ?? false) {
                    $uploadVideoRequest->setWorkflowId($this->config['vod_video_workflow_id']);
                    $status = 4;
                }

                $videoId = $uploader->uploadLocalVideo($uploadVideoRequest);

                $attach->savename = $videoId;
                $attach->savepath = '';
                $attach->bucket   = '';

            } else if (Util::isAudio($attach->mimetype, $attach->extension)) {
                // 音频上传
                $uploader           = new AliyunVodUploader($accessKeyId, $accessKeySecret, $this->config['vod_region_id']);
                $uploadVideoRequest = new UploadVideoRequest($filepath, $attach->filename);
                // 是否配置了音频处理流程
                if ($this->config['vod_audio_workflow_id'] ?? false) {
                    $uploadVideoRequest->setWorkflowId($this->config['vod_audio_workflow_id']);
                    $status = 4;
                }

                $videoId = $uploader->uploadLocalVideo($uploadVideoRequest);

                $attach->savename = $videoId;
                $attach->savepath = '';
                $attach->bucket   = '';
            } else if (Util::isImageFile($attach->mimetype, $filepath)) {
                // 图片上传
                $uploader           = new AliyunVodUploader($accessKeyId, $accessKeySecret, $this->config['vod_region_id']);
                $uploadVideoRequest = new UploadImageRequest($filepath, $attach->filename);

                $image = $uploader->uploadLocalImage($uploadVideoRequest);

                $attach->savename = $image['ImageId'];
                $attach->savepath = '';
                $attach->bucket   = '';
            } else {
                // 其他文件,上传到OSS
                $internalEndpoint = $this->config['internal_endpoint'];
                $endpoint         = $this->config['endpoint'];
                $bucket           = $this->config['bucket'];

                $point = $internalEndpoint ?: $endpoint;

                $client = new OssClient($accessKeyId, $accessKeySecret, $point);
                $saveas = $attach->savepath . '/' . $attach->savename;
                $client->uploadFile($bucket, $saveas, $filepath);
                $attach->bucket = $bucket;
            }

            $attach->stock  = 'aliyun';
            $attach->status = $status;

            $re = $attach->save();
            if ($re) {
                @unlink($filepath);
            }

            return $re;
        } catch (Exception $e) {
            Attach::update(['status' => 2], ['id' => $attach->id]);
            throw new FileException($e->getMessage());

        }

    }

    /**
     * 文件的url
     * Author   Martinsun<syh@sunyonghong.com>
     * Date:  2020-03-30
     */
    public function url(array $data = [])
    {
        $url    = '';
        $client = $this->createClient();
        try {
            if (Util::isImage($data['mimetype'])) {
                // 图片
                $result = $client->getImageInfo()->withImageId($data['savename'])->format('JSON')->request();
                if ($result->isSuccess()) {
                    $url = $result->ImageInfo->URL;
                }

            } else if (Util::isAudio($data['mimetype'], $data['extension']) || Util::isVideo($data['mimetype'], $data['extension'])) {
                $url = [];
                // 音视频
                $result = $client->getPlayInfo()->withVideoId($data['savename'])->format('JSON')->request();
                if ($result->isSuccess()) {
                    $items = $result->PlayInfoList->PlayInfo;
                    foreach ($items as $item) {
                        $url[] = [
                            'definition' => $item->Definition,
                            'play_url'   => $item->PlayURL,
                        ];
                    }
                }
            } else {
                $accessKeyId     = $this->config['accessKey_id'];
                $accessKeySecret = $this->config['accessKey_secret'];
                $endpoint        = $this->config['domain'] ?? $this->config['endpoint'];
                $ossClient       = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

                $url = $ossClient->signUrl($data['bucket'], $data['savepath'] . '/' . $data['savename'], 3600 * 10, 'GET');
            }
        } catch (Exception $e) {
            $url = '';
        }

        return $url;
    }

    /**
     * 文件的储存路径
     * Author   Martinsun<syh@sunyonghong.com>
     * Date:  2020-03-30
     */
    public function path(array $data = [])
    {
        $path = $data['bucket'] . ':' . $data['savepath'] . '/' . $data['savename'];

        return str_replace('\\', '/', $path);
    }

    public function playAuth($videoId)
    {
        $client = $this->createClient();

        $result = $client->getVideoPlayAuth()->withVideoId($videoId)->withAuthInfoTimeout(120)->request()->toArray();

        return $result['PlayAuth'] ?? '';
    }

    /**
     * getVideoList
     *
     * @param array $params
     * @return mixed|void
     */
    public function getVideoList(array $params = [])
    {
        // TODO: Implement getVideoList() method.
        $client = $this->createClient();
        $result = $client->getVideoList()
            ->withPageNo($params['pageNo'])
            ->withPageSize($params['pageSize'])
            ->request();

        $ret = [
            'total'      => 0,
            'video_list' => []
        ];

        if ($result->isSuccess()) {
            $datas = $result->toArray();
            $ret   = [
                'total'      => $datas['Total'],
                'video_list' => $datas['VideoList']['Video'] ?? []
            ];
        }

        return $ret;

    }

    private function createClient()
    {
        $accessKeyId     = $this->config['accessKey_id'];
        $accessKeySecret = $this->config['accessKey_secret'];
        $regionId        = $this->config['vod_region_id'] ?? 'cn-shanghai';

        AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
            ->regionId($regionId)
            ->connectTimeout(1)
            ->timeout(3)
            ->asDefaultClient();

        // ->name('AliyunVod');

        return Vod::v20170321();//->client('AliyunVod');
    }

    /**
     * 删除文件
     *
     * @param $attach
     */
    public function delete($attach)
    {
        $client = $this->createClient();
        try {
            if (Util::isImage($attach->mimetype)) {
                // 图片
                $client->deleteImage()
                    ->withDeleteImageType('ImageId')
                    ->withImageIds($attach->savename)
                    ->format('JSON')
                    ->request();
                // if ($result->isSuccess()) {
                //     // 删除成功
                // }

            } else if (Util::isAudio($attach->mimetype, $attach->extension) || Util::isVideo($attach->mimetype, $attach->extension)) {
                // 音视频
                $client->deleteVideo()
                    ->withVideoIds($attach->savename)
                    ->format('JSON')
                    ->request();
            } else {
                // 其他文件
                $accessKeyId     = $this->config['accessKey_id'];
                $accessKeySecret = $this->config['accessKey_secret'];
                $endpoint        = $this->config['domain'] ?? $this->config['endpoint'];
                $ossClient       = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

                $ossClient->deleteObject($attach->bucket, $attach->savepath . '/' . $attach->savename);
            }
        } catch (Exception $e) {
        }
    }
}
