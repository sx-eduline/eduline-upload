<?php
declare (strict_types=1);

namespace eduline\upload\stocks\local;

use app\common\model\Attach;
use eduline\upload\interfaces\FileInterface;
use eduline\upload\utils\Util;
use Exception;
use think\exception\FileException;
use think\facade\Filesystem;
use think\facade\Request;

class File implements FileInterface
{
    /**
     * 上传文件
     * Author   Martinsun<syh@sunyonghong.com>
     * Date:  2020-03-30
     *
     * @param string $path [description]
     * @param    [type]                         $file [description]
     * @param string $name [description]
     * @return   [type]                               [description]
     */
    public function putFile($savepath, $file, $savename)
    {
        $savepath = Config::get('upload_dir') . $savepath;
        try {
            Filesystem::disk(Config::get('bucket'))->putFileAs($savepath, $file, $savename);

            return [
                'stock'    => 'local',
                'bucket'   => Config::get('bucket'),
                'savepath' => $savepath,
                'savename' => $savename,
            ];
        } catch (Exception $e) {
            throw new FileException($e->getMessage());

        }

    }

    /**
     * 上传到本地,无需云端支持
     * Author   Martinsun<syh@sunyonghong.com>
     * Date:  2020-08-15
     *
     * @param Attach $attach [description]
     * @return   [type]                                 [description]
     */
    public function putYunFile($attach)
    {
        throw new FileException('暂不支持该方式上传');
    }

    /**
     * 文件的url
     * Author   Martinsun<syh@sunyonghong.com>
     * Date:  2020-03-30
     */
    public function url(array $data = [])
    {
        $url = Request::domain() . Filesystem::getDiskConfig($data['bucket'], 'url') . '/' . str_replace('\\', '/', $data['savepath'] . '/' . $data['savename']);
        if (Util::isAudio($data['mimetype'], $data['extension']) || Util::isVideo($data['mimetype'], $data['extension'])) {
            // 音视频
            return [
                [
                    'definition' => 'OD',
                    'play_url'   => $url,
                ],
            ];
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
        $path = Filesystem::getDiskConfig($data['bucket'], 'root');
        $path .= DIRECTORY_SEPARATOR . $data['savepath'] . DIRECTORY_SEPARATOR . $data['savename'];

        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * getVideoList
     *
     * @param array $params
     * @return array
     */
    public function getVideoList(array $params = [])
    {
        // TODO: Implement getVideoList() method.
        return [
            'total'      => 0,
            'video_list' => []
        ];
    }
}
