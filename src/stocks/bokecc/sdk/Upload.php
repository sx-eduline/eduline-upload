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

    protected $readStream;

    /**
     *
     */
    public function __construct(array $params)
    {
        ini_set("memory_limit", "-1");
        $this->attachId = $params['attach_id'];
        $attach         = app(Attach::class)->findOrEmpty($this->attachId);
        // 配置
        $this->config   = $params['config'];// Request::post('config');
        $this->filepath = $params['filepath'];//Request::post('filepath');
        // 参数
        $this->filesize  = $attach->getData('filesize');
        $this->filemd5   = $attach->getData('filemd5');
        $this->filename  = $attach->getData('filename');
        $this->limit     = 1024 * 1024 * 2; // 2M分片大小
        $this->rangeSize = $this->limit;
        // uploadinfo
        $this->uploadInfo = $uploadInfo = $params['uploadinfo'];//Request::post('uploadinfo');
        $this->savename   = $uploadInfo['videoid'];// $attach->getData('savename');
        $this->videoId    = $uploadInfo['videoid'];
        $this->metaurl    = $uploadInfo['metaurl'];
        $this->chunkurl   = $uploadInfo['chunkurl'] . '?ccvid=' . $this->videoId;
        if (file_exists($this->filepath)) {
            $this->readStream = fopen($this->filepath, 'r');
        }
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
            'ccvid'       => $this->videoId,
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
        // 余下未传的大小
        $rest = $this->filesize - $this->received;
        // 当前分片大小: 取指定分片大小 或则 余下未传的大小不足以分片 则取余下未传的大小
        $length = min($this->rangeSize, $rest);
        // 结束分片上传的位置
        $end = $length + $this->received - 1;//$rest > $this->rangeSize ? $this->received + $this->rangeSize : $this->filesize - 1;

        // $block = file_get_contents($this->filepath, true, null, $start, $this->limit);
        //Log::write(sprintf("开始 %d,结束 %d, 大小 %d", $this->received, $end, $length));

        /**
         * // 文件总大小超过开始读取的位置  且 开始读取的位置与文件指针不一致 => 调整文件指针
         * $seek = $this->received == 0 ? 0 : $this->received - 1;
         * if ($this->received == 0 && $seek !== ftell($this->readStream)) {
         * if (fseek($this->readStream, $seek) !== 0) {
         * throw new  FileException("读取文件失败");
         * }
         * }**/
        $block = fread($this->readStream, $length); // Remaining upload data or cURL's requested chunk size

        $data = [
            'block'    => $block,
            'name'     => 'file',
            'filename' => $this->filename,
            'start'    => $this->received,
            'end'      => $end,
            'size'     => $this->filesize,
        ];

        $result = $this->postData($this->chunkurl, $data);

        unset($block,$data);

        $result = json_decode($result, true);
        //Log::write("CC返回数据:" . json_encode($result));
        if ($result['result'] === 0) {
            // 接受分片完成
            $this->received = $result['received'];
            $this->uploadChunk();
        } else if ($result['result'] === 1 || $result['received'] == $this->filesize) {
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
        $ch         = $this->curl_custom_postfields($ch, $data);
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

        curl_setopt_array($ch, [
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

        return $ch;
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

    public function __destruct()
    {
        if ($this->readStream) {
            fclose($this->readStream);
        }

        if (file_exists($this->filepath)) {
            @unlink($this->filepath);
        }
    }

}
