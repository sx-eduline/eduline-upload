<?php
declare (strict_types=1);

namespace eduline\upload\stocks\bokecc\sdk;

use app\common\exception\LogicException;
use app\common\library\Queue;
use app\common\model\Attach;
use Exception;
use GuzzleHttp\Client;
use think\exception\FileException;

// use think\facade\Log;

/**
 * bokecc上传
 */
class Upload
{
    protected $attachId;
    protected $config;
    protected $filepath;
    protected $filesize;
    protected $filemd5;
    protected $savename;
    protected $filename;
    protected $metaurl;
    protected $chunkurl;
    protected $limit;
    protected $rangeSize;
    protected $uploadInfo;
    protected $videoId;
    protected $received;

    /**
     * 重试最大次数
     *
     * @var int
     */
    protected $maxAttempts = 5;

    /**
     *
     */
    public function __construct(array $params)
    {
        $this->attachId = $params['attach_id'];
        $attach         = app(Attach::class)->findOrEmpty($this->attachId);
        // 配置
        $this->config   = $params['config'];// Request::post('config');
        $this->filepath = $attach->getAttr('filepath');// Request::post('filepath');
        // 参数
        $this->filesize  = $attach->getData('filesize');
        $this->filemd5   = $attach->getData('filemd5');
        $this->filename  = $attach->getData('filename');
        $this->savename  = $attach->getData('savename');
        $this->limit     = 1024 * 1024 * 2; // 2M分片大小
        $this->rangeSize = $this->limit - 1;
        // uploadinfo
        $this->uploadInfo = $uploadInfo = $params['uploadinfo'];//Request::post('uploadinfo');
        $this->videoId    = $uploadInfo['videoid'];
        $this->metaurl    = $uploadInfo['metaurl'];
        $this->chunkurl   = $uploadInfo['chunkurl'] . '?ccvid=' . $this->videoId;
    }

    /**
     * run
     * Author: 亓官雨树 <lucky.max@foxmail.com>
     * Date: 22/11/08
     */
    public function run()
    {
        try {
            // 查询文件上传状态及断点位置
            $uploadmeta = $this->uploadmeta();
            $queue      = false;
            if (isset($uploadmeta['result'])) {
                switch ($uploadmeta['result']) {
                    case 1: // 文件已全部接收，上传成功
                        @unlink($this->filepath);
                        break;
                    case 0: // 文件仍在上传状态中，成功返回“断点位置”
                        $this->received = $uploadmeta['received'];
                        $this->uploadChunk();
                        break;
                    case -1: // 上传失败，可以放弃“本次”上传，不要重试了；
                        break;
                    default:
                        throw new FileException($uploadmeta['msg']);
                }

            }
        } catch (FileException|Exception $e) {
            throw new LogicException('上传失败', $e->getFile() . ':' . $e->getLine() . '【' . $e->getMessage() . '】');
        }
    }

    /**
     * 查询文件上传状态及断点位置
     * Author: 亓官雨树 <lucky.max@foxmail.com>
     * Date: 22/11/15
     *
     * @return mixed
     */
    public function uploadmeta()
    {
        $param = [
            'uid'         => $this->config['userid'],
            'ccvid'       => $this->savename,
            'filename'    => $this->filename,
            'filesize'    => $this->filesize,
            'servicetype' => $this->uploadInfo['servicetype']
        ];

        return $this->client($this->metaurl, $param);
    }

    /**
     * 上传视频文件块CHUNK 直至全部完成
     */
    public function uploadChunk(int $retryCount = 0)
    {
        // 文件总大小
        $size = $this->filesize;
        // 分片大小
        $rangeSize = $this->rangeSize;
        // 开始分片上传的位置
        $start = $this->received;
        // 剩余未传大小
        $rest = $size - $start;
        // 结束分片上传的位置
        $end = $rest > $rangeSize ? $start + $rangeSize : $size - 1;

        $block = file_get_contents($this->filepath, true, null, $start, $this->limit);
        $data  = [
            'block'    => $block,
            'name'     => 'file',
            'filename' => $this->filename,
            'start'    => $start,
            'end'      => $end,
            'size'     => $size,
        ];

        $result = $this->postData($this->chunkurl, $data);

        $result = json_decode($result, true);
        if ($result['result'] === 0) {
            // 接受分片完成
            $this->received = $result['received'];
            $this->uploadChunk();
        } else if ($result['result'] === 1 || $result['received'] == $size) {
            // 上传完成
        } else if ($result['result'] === -2) {
            // 服务器内部错误，重试
            $retryCount += 1;
            if ($retryCount > $this->maxAttempts) {
                throw new  FileException($result['msg']);
            }
            $this->uploadChunk($retryCount);
        } else if ($result['result'] === -3) {
            throw new FileException($result['msg']);
        }
    }

    private function postData($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $this->curl_custom_postfields($ch, $data);
        $dxycontent = curl_exec($ch);
        curl_close($ch);

        return $dxycontent;
    }

    private function curl_custom_postfields($ch, $data)
    {
        $body[]   = implode("\r\n", [
            "Content-Disposition: form-data;name=\"{$data['name']}\";filename=\"{$data['filename']}\"",
            "Content-Type: application/octet-stream",
            "",
            $data['block'],
        ]);
        $boundary = "---------------------" . md5(mt_rand() . microtime());

        array_walk($body, function (&$part) use ($boundary) {
            $part = "--{$boundary}\r\n{$part}";
        });

        $body[] = "--{$boundary}--";
        $body[] = "";

        return @curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => implode("\r\n", $body),
            CURLOPT_HTTPHEADER => [
                'Charsert: UTF-8',
                'user-agent:  Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4)',
                "Content-Type: multipart/form-data; boundary={$boundary}",
                'Content-Range: bytes ' . $data['start'] . "-" . $data['end'] . "/" . $data['size'],
                'Cache-Control: no-cache',
                'Connection: keep-alive',
            ],
        ]);
    }

    /**
     * 请求bokecc
     * Author: 亓官雨树 <lucky.max@foxmail.com>
     * Date: 22/10/28
     *
     * @param $uri
     * @param $param
     * @return mixed
     */
    public function client($uri, $param)
    {
        $param = $this->THQS($param);
        $uri   .= '?' . http_build_query($param);
        // Log::write('请求地址：' . $uri);
        $client   = new Client();
        $res      = $client->get($uri);
        $response = $res->getBody()->getContents();

        // Log::write('请求结果：' . $response);

        return json_decode($response, true);
    }

    /**
     * THQS
     * Author: 亓官雨树 <lucky.max@foxmail.com>
     * Date: 22/11/09
     *
     * @param $param
     * @return mixed
     */
    private function THQS($param)
    {
        ksort($param);
        $param['time'] = time();
        $param['salt'] = $this->config['apikey'];
        $value         = http_build_query($param);
        $hash          = md5($value);
        $param['hash'] = strtoupper($hash);
        unset($param['salt']);

        return $param;
    }

}
