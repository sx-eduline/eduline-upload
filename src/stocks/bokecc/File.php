<?php
declare (strict_types=1);

namespace eduline\upload\stocks\bokecc;

use app\admin\model\material\Category;
use app\common\library\Queue;
use app\common\model\Attach;
use eduline\upload\interfaces\FileInterface;
use eduline\upload\utils\Util;
use Exception;
use GuzzleHttp\Client;
use think\exception\FileException;
use think\facade\Db;

class File implements FileInterface
{
    protected $sparkapi = 'https://spark.bokecc.com/api';
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
    public function putFile()
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
    public function putYunFile(Attach $attach)
    {
        try {
            // 附件本地地址
            $filepath = $attach->getAttr('filepath');
            // 创建视频上传信息
            $response = $this->createUploadInfo($attach);
            if (isset($response['uploadinfo'])) {
                $videoId = $response['uploadinfo']['videoid'];
            } else {
                throw new FileException($response['error']);
            }
            // 更新为上传中
            Attach::update(['savename' => $videoId, 'status' => 3], ['id' => $attach->id]);

            Queue::push('bokeccUpload', [
                'filepath'   => $filepath,
                'attach_id'  => $attach->id,
                'uploadinfo' => $response['uploadinfo'],
                'config'     => $this->config
            ]);
        } catch (Exception $e) {
            Attach::update(['status' => 2], ['id' => $attach->id]);
            throw new FileException($e->getMessage());
        } catch (FileException $e) {
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
        $url = '';

        try {
            if (Util::isVideo($data['mimetype'], $data['extension'])) {
                $uri   = $this->sparkapi . "/video/original";
                $param = [
                    'userid'  => $this->config['userid'],
                    'videoid' => $data['savename'],
                    'format'  => 'json'
                ];

                $response = $this->client($uri, $param);

                if (isset($response['error'])) throw new FileException($response['error']);

                $video = $response['video'] ?? ['url' => ''];
                $url   = [['play_url' => $video['url']]];

            } else if (Util::isAudio($data['mimetype'], $data['extension'])) {

            }
        } catch (Exception $e) {
            $url = '';
        }

        return $url;
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
    public function client($uri, $param, $debug = false)
    {
        ksort($param);
        $str = '';
        foreach ($param as $k => $v) {
            $str .= $k . '=' . urlencode(strval($v)) . '&';
        }
        $str .= 'time=' . time();
        $md5 = md5($str . '&salt=' . $this->config['apikey']);
        $str .= '&hash=' . $md5;
        $uri .= '?' . $str;

        $client   = new Client();
        $res      = $client->get($uri);
        $response = $res->getBody()->getContents();
        $debug && Db::name('test')->save(['msg' => $response, 'create_time' => date('Y-m-d H:i:s', time())]);
        return json_decode($response, true);
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

    /**
     * 创建上传信息
     * Author: 亓官雨树 <lucky.max@foxmail.com>
     * Date: 22/11/08
     *
     * @param Attach $attach
     * @return mixed
     */
    public function createUploadInfo(Attach $attach)
    {
        $uri   = $this->sparkapi . "/video/create/v2";
        $param = [
            'userid'     => $this->config['userid'],
            'title'      => $attach->getAttr('filename'),
            // 'categoryid' => $a,
            'filename'   => $attach->getAttr('filename'),
            'filesize'   => $attach->getData('filesize'),
            'notify_url' => $this->config['notify_url'],
        ];
        return $this->client($uri, $param);
    }

    /**
     * 创建视频分类
     * Author: 亓官雨树 <lucky.max@foxmail.com>
     * Date: 22/11/10
     *
     * @param Category $cate
     */
    public function createCategory(Category $cate)
    {
        $uri   = $this->sparkapi . "/category/create";
        $param = [
            'userid' => $this->config['userid'],
            'name'   => $cate->title,
            'format' => 'json'
        ];
        //
        $res = $this->client($uri, $param);
        //
        if (isset($res['category'])) {
            $cate->cloud_id = $res['category']['id'];
            $cate->save();
        }
    }

    public function getVideoList($param)
    {
        //
        $uri = $this->sparkapi . "/videos/v7";
        //
        $param['userid'] = $this->config['userid'];
        // $param['format'] = 'json';
        //
        return $this->client($uri, $param);
    }

    public function updateVideo($attachId, $cloudId): array
    {
        $attach = app(Attach::class)->findOrEmpty($attachId);
        if ($attach->isEmpty() || $attach->status == 2) return [];

        $uri                 = $this->sparkapi . "/video/update";
        $param['videoid']    = $videoId;
        $param['userid']     = $this->config['userid'];
        $param['categoryid'] = $cloudId;

        return $this->client($uri, $param);
    }

}