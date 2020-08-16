<?php
declare (strict_types = 1);
namespace eduline\upload\stocks\tencent;

use app\common\model\Attach;
use eduline\upload\interfaces\FileInterface;
use eduline\upload\stocks\tencent\Config;
use eduline\upload\utils\Util;
use Qcloud\Cos\Client;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Vod\V20180717\Models\DescribeMediaInfosRequest;
use TencentCloud\Vod\V20180717\VodClient;
use think\exception\FileException;
use Vod\Model\VodUploadRequest;
use Vod\VodUploadClient;

class File implements FileInterface
{
    protected $config;
    public function __construct()
    {
        $this->config = Config::get();
    }
    /**
     * 本地上传 -- 不需要
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-08-15
     * @return   [type]                         [description]
     */
    public function putFile()
    {
        throw new FileException('暂不支持该方式上传');

    }
    /**
     * 将本地文件上传到云端
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-03-30
     * @param    string                         $path [description]
     * @param    [type]                         $file [description]
     * @param    string                         $name [description]
     * @return   [type]                               [description]
     */
    public function putYunFile(Attach $attach)
    {
        try {
            $secretId  = $this->config['secret_id'];
            $secretKey = $this->config['secret_key'];
            $filepath  = $attach->getAttr('filepath');
            // 更新为上传中
            Attach::update(['status' => 3], ['id' => $attach->id]);
            // 判断是否是图片 音视频
            if (Util::isVideo($attach->mimetype, $attach->extension)) {
                $storageRegion = $this->config['vod_storage_region'] ?? '';
                $region        = $this->config['vod_region'] ?? 'ap-chengdu';
                // 视频上传
                $client             = new VodUploadClient($secretId, $secretKey);
                $req                = new VodUploadRequest();
                $req->MediaFilePath = $filepath;
                $req->MediaName     = $attach->filename;
                if ($storageRegion) {
                    $req->StorageRegion = $storageRegion;
                }
                // 视频任务流
                $procedure = $this->config['vod_video_procedure'];
                if ($procedure) {
                    $req->Procedure = $procedure;
                }

                $rsp = $client->upload($region, $req);

                $attach->savename = $rsp->FileId;
                $attach->savepath = '';
                $attach->bucket   = '';

            } else if (Util::isAudio($attach->mimetype, $attach->extension)) {
                $storageRegion = $this->config['vod_storage_region'] ?? '';
                $region        = $this->config['vod_region'] ?? 'ap-chengdu';
                // 音频上传
                $client             = new VodUploadClient($secretId, $secretKey);
                $req                = new VodUploadRequest();
                $req->MediaFilePath = $filepath;
                $req->MediaName     = $attach->filename;
                if ($storageRegion) {
                    $req->StorageRegion = $storageRegion;
                }
                // 音频任务流
                $procedure = $this->config['vod_audio_procedure'];
                if ($procedure) {
                    $req->Procedure = $procedure;
                }

                $rsp = $client->upload($region, $req);

                $attach->savename = $rsp->FileId;
                $attach->savepath = '';
                $attach->bucket   = '';
            } else if (Util::isImageFile($filepath)) {
                $storageRegion = $this->config['vod_storage_region'] ?? '';
                $region        = $this->config['vod_region'] ?? 'ap-chengdu';
                // 图片上传
                $client             = new VodUploadClient($secretId, $secretKey);
                $req                = new VodUploadRequest();
                $req->MediaFilePath = $filepath;
                $req->MediaName     = $attach->filename;
                if ($storageRegion) {
                    $req->StorageRegion = $storageRegion;
                }

                $rsp = $client->upload($region, $req);

                $attach->savename = $rsp->FileId;
                $attach->savepath = '';
                $attach->bucket   = '';
            } else {
                $region = $this->config['cos_region'];
                $bucket = $this->config['bucket'];

                $client = new Client([
                    'region'      => $region,
                    'schema'      => 'https', //协议头部，默认为http
                    'credentials' => [
                        'secretId'  => $secretId,
                        'secretKey' => $secretKey,
                    ],
                ]);
                $saveas = $attach->savepath . '/' . $attach->savename;
                $client->putObject([
                    'Bucket' => $bucket,
                    'Key'    => $saveas,
                    'Body'   => fopen($filepath, 'rb'),
                ]);
                $attach->bucket = $bucket;
            }

            $attach->stock  = 'tencent';
            $attach->status = 1;

            return $attach->save();
        } catch (\Exception $e) {
            Attach::update(['status' => 0], ['id' => $attach->id]);
            throw new FileException($e->getMessage());

        }

    }

    /**
     * 文件的url
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-03-30
     */
    public function url(array $data = [])
    {
        $secretId  = $this->config['secret_id'];
        $secretKey = $this->config['secret_key'];

        $url = '';
        // 是否VOD储存
        $isVod = Util::isImage($data['mimetype']) || Util::isAudio($data['mimetype'], $data['extension']) || Util::isVideo($data['mimetype'], $data['extension']);
        if ($isVod) {
            $cred        = new Credential($secretId, $secretKey);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("vod.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $vodRegion = $this->config['vod_region'] ?? 'ap-chengdu';
            $client    = new VodClient($cred, $vodRegion, $clientProfile);

            $req = new DescribeMediaInfosRequest();

            $params = array(
                "FileIds" => [$data['savename']],
            );
            $req->fromJsonString(json_encode($params));

            $resp = $client->DescribeMediaInfos($req);

            $url = $resp->MediaInfoSet[0]->TranscodeInfo->TranscodeSet[0]->Url;

        } else {
            $region = $this->config['cos_region'];
            $client = new Client([
                'region'      => $region,
                'schema'      => 'https', //协议头部，默认为http
                'credentials' => [
                    'secretId'  => $secretId,
                    'secretKey' => $secretKey,
                ],
            ]);

            $url = $client->getPresignetUrl('getObject', [
                'Bucket' => $data['bucket'],
                'Key'    => $data['savepath'] . '/' . $data['savename'],
            ], '+10 minutes');
        }

        return $url;
    }

    /**
     * 文件的储存路径
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-03-30
     */
    public function path(array $data = [])
    {
        $path = $data['bucket'] . ':' . $data['savepath'] . '/' . $data['savename'];
        return str_replace('\\', '/', $path);
    }
}
