<?php
declare (strict_types=1);

namespace eduline\upload\stocks\tencent;

use app\common\model\Attach;
use eduline\upload\interfaces\FileInterface;
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
     * @param string $path [description]
     * @param    [type]                         $file [description]
     * @param string $name [description]
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
            $status = 1;
            // 判断是否是图片 音视频
            if (Util::isVideo($attach->mimetype, $attach->extension)) {
                // 视频
                $procedure        = $this->config['vod_video_procedure'] ?? false;
                $rsp              = $this->doUpload($filepath, $attach->filename, $procedure);
                $attach->savename = $rsp->FileId;
                $attach->savepath = '';
                $attach->bucket   = '';
                $procedure && $status = 4;

            } else if (Util::isAudio($attach->mimetype, $attach->extension)) {
                // 音频
                $procedure        = $this->config['vod_audio_procedure'] ?? false;
                $rsp              = $this->doUpload($filepath, $attach->filename, $procedure);
                $attach->savename = $rsp->FileId;
                $attach->savepath = '';
                $attach->bucket   = '';
                $procedure && $status = 4;

            } else if (Util::isImageFile($attach->mimetype, $filepath)) {
                // 图片
                $rsp              = $this->doUpload($filepath, $attach->filename);
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
            $attach->status = $status;

            return $attach->save();
        } catch (\Exception $e) {
            Attach::update(['status' => 0], ['id' => $attach->id]);
            throw new FileException($e->getMessage());

        }

    }

    /**
     * 上传方法
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-08-18
     * @param    [type]                         $filepath  [description]
     * @param    [type]                         $filename  [description]
     * @param    [type]                         $procedure [description]
     * @return   [type]                                    [description]
     */
    protected function doUpload($filepath, $filename, $procedure = null)
    {
        $secretId      = $this->config['secret_id'];
        $secretKey     = $this->config['secret_key'];
        $storageRegion = $this->config['vod_storage_region'] ?? '';
        $region        = $this->config['vod_region'] ?? 'ap-chengdu';
        // 初始化
        $client             = new VodUploadClient($secretId, $secretKey);
        $req                = new VodUploadRequest();
        $req->MediaFilePath = $filepath;
        $req->MediaName     = $filename;
        if ($storageRegion) {
            $req->StorageRegion = $storageRegion;
        }

        if ($procedure) {
            $req->Procedure = $procedure;
        }

        return $client->upload($region, $req);
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
        $isImage = Util::isImage($data['mimetype']);
        $isVod   = $isImage || Util::isAudio($data['mimetype'], $data['extension']) || Util::isVideo($data['mimetype'], $data['extension']);
        try {
            if ($isVod) {
                $cred        = new Credential($secretId, $secretKey);
                $httpProfile = new HttpProfile();
                $httpProfile->setEndpoint("vod.tencentcloudapi.com");

                $clientProfile = new ClientProfile();
                $clientProfile->setHttpProfile($httpProfile);
                $vodRegion = $this->config['vod_region'] ?? 'ap-chengdu';
                $client    = new VodClient($cred, $vodRegion, $clientProfile);

                $req = new DescribeMediaInfosRequest();

                $params = [
                    "FileIds" => [$data['savename']],
                ];
                $req->fromJsonString(json_encode($params));

                $resp = $client->DescribeMediaInfos($req);
                if ($isImage) {
                    $url = $resp->MediaInfoSet[0]->BasicInfo->MediaUrl;
                } else {
                    $url         = [];
                    $items       = $resp->MediaInfoSet[0]->TranscodeInfo->TranscodeSet;
                    $definitions = [
                        // FD:流畅
                        '100010' => 'FD',
                        '100210' => 'FD',
                        // LD:标清
                        '100020' => 'LD',
                        '100220' => 'LD',
                        // SD:高清
                        '100030' => 'SD',
                        '100230' => 'SD',
                        // HD:超清
                        '100040' => 'HD',
                        '100240' => 'HD',
                        // OD:原画
                        '0'      => 'OD',
                        // 2K
                        '100070' => '2K',
                        '100270' => '2K',
                        // 4k
                        '100080' => '4K',
                        '100080' => '4K',
                        '1010'   => 'OD',
                        '1020'   => 'HD',
                    ];
                    foreach ($items as $item) {
                        $url[] = [
                            'definition' => $definitions[$item->Definition] ?? 'OD',
                            'play_url'   => $item->Url,
                        ];
                    }
                }

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
                ], '+10 minutes')->__toString();
            }
        } catch (\Exception $e) {
            $url = '';
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
