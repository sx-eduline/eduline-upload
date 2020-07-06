<?php
declare (strict_types = 1);
namespace eduline\upload\stocks\aliyun;

use eduline\upload\interfaces\FileInterface;
use eduline\upload\stocks\aliyun\Config;
use OSS\OssClient;
use think\exception\FileException;

class File implements FileInterface
{
    protected $config;
    public function __construct()
    {
        $this->config = Config::get();
    }
    /**
     * 上传文件
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-03-30
     * @param    string                         $path [description]
     * @param    [type]                         $file [description]
     * @param    string                         $name [description]
     * @return   [type]                               [description]
     */
    public function putFile($path = '', $file = null, string $name = '')
    {
        try {
            $accessKeyId      = $this->config['accessKey_id'];
            $accessKeySecret  = $this->config['accessKey_secret'];
            $internalEndpoint = $this->config['internal_endpoint'];
            $endpoint         = $this->config['endpoint'];
            $bucket           = $this->config['bucket'];

            $point = $internalEndpoint ?: $endpoint;

            $client = new OssClient($accessKeyId, $accessKeySecret, $point);
            $saveas = $path . '/' . $name;
            $client->uploadFile($bucket, $saveas, $file);

            return [
                'stock'    => 'aliyun',
                'bucket'   => $bucket,
                'savepath' => dirname($saveas),
                'savename' => basename($saveas),
            ];
        } catch (\Exception $e) {
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
        $accessKeyId     = $this->config['accessKey_id'];
        $accessKeySecret = $this->config['accessKey_secret'];
        $endpoint        = $this->config['endpoint'];
        $ossClient       = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

        return $ossClient->signUrl($data['bucket'], $data['savepath'] . '/' . $data['savename'], 3600 * 10, 'GET');
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
