<?php
declare (strict_types = 1);
namespace eduline\upload\stocks\tencent;

use app\common\model\Attach;
use eduline\upload\interfaces\FileInterface;
use eduline\upload\stocks\tencent\Config;
use Qcloud\Cos\Client;
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
            $secretId  = $this->config['cos_secret_id'];
            $secretKey = $this->config['cos_secret_key'];
            $region    = $this->config['cos_region'];
            $bucket    = $this->config['bucket'];

            $client = new Client([
                'region'      => $region,
                'schema'      => 'https', //协议头部，默认为http
                'credentials' => [
                    'secretId'  => $secretId,
                    'secretKey' => $secretKey,
                ],
            ]);
            $saveas = $path . '/' . $name;
            $client->putObject([
                'Bucket' => $bucket,
                'Key'    => $saveas,
                'Body'   => fopen($file, 'rb'),
            ]);

            return [
                'stock'    => 'tencent',
                'datas'    => [
                    'region' => $region,
                ],
                'bucket'   => $bucket,
                'savepath' => dirname($saveas),
                'savename' => basename($saveas),
            ];
        } catch (\Exception $e) {
            throw new FileException($e->getMessage());

        }

    }

    /**
     * 上传到云端 待开发
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-08-15
     * @param    Attach                         $attach [description]
     * @return   [type]                                 [description]
     */
    public function putYunFile(Attach $attach)
    {
        throw new FileException('暂不支持该方式上传');
    }

    /**
     * 文件的url
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-03-30
     */
    public function url(array $data = [])
    {
        $secretId  = $this->config['cos_secret_id'];
        $secretKey = $this->config['cos_secret_key'];
        $region    = $this->config['cos_region'];
        $client    = new Client([
            'region'      => $region,
            'schema'      => 'https', //协议头部，默认为http
            'credentials' => [
                'secretId'  => $secretId,
                'secretKey' => $secretKey,
            ],
        ]);

        return $client->getPresignetUrl('getObject', [
            'Bucket' => $data['bucket'],
            'Key'    => $data['savepath'] . '/' . $data['savename'],
        ], '+10 minutes');
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
