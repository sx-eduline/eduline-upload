<?php
declare (strict_types=1);

namespace eduline\upload\stocks\aliyun\event;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Vod\Vod;
use app\common\model\Attach;
use app\common\service\BaseService;
use eduline\upload\stocks\aliyun\Config;
use think\Request;
use think\facade\Db;

/**
 * 点播事件回调
 */
class VodEvent
{
    public function handle(Request $request)
    {

        $eventType = $request->post('EventType');
        $videoId   = $request->post('VideoId');
        $response  = BaseService::parseToData([], 1);
        switch ($eventType) {
            // 视频转码完成
            case 'TranscodeComplete':
                $status = $request->post('Status') == 'success' ? 1 : 0;
                // 更新视频信息
                $video = $this->getVideoInfo($videoId);
                if (false == $video) {
                    $response = BaseService::parseToData(['error_code' => 404], 0, '文件出错', 404);
                    break;
                }
                // 时长向下取整
                $duration = floor($video->Duration);
                $update   = ['duration' => $duration, 'status' => $status];
                if (property_exists($video, 'CoverURL')) {
                    $update['cover_url'] = $video->CoverURL;
                }
                // 更新视频信息
                Attach::update($update, ['stock' => 'aliyun', 'savename' => $videoId, 'status' => 4]);

                break;
            case 'SnapshotComplete':
                // 视频截图完成
                if ($request->post('Status') == 'success') {
                    $coverURL = $request->post('CoverUrl', '');
                    $video    = $this->getVideoInfo($videoId);
                    // 时长向下取整
                    $duration = floor($video->Duration);
                    Attach::update(['duration' => $duration, 'cover_url' => $coverURL], ['stock' => 'aliyun', 'savename' => $videoId]);
                }
                break;
            case 'FileUploadComplete':
                // 视频上传完成
                // if ($request->post('Status') == 'success') {
                //     $this->submitTranscodeJobs($videoId);
                // }
                break;
            default:
                # code...
                break;
        }

        return $response;
    }

    public function getVideoInfo($videoId)
    {
        AlibabaCloud::accessKeyClient(Config::get('accessKey_id'), Config::get('accessKey_secret'))
            ->regionId(Config::get('vod_region_id', 'cn-shanghai'))
            ->connectTimeout(1)
            ->timeout(3)
            ->name('AliyunVod');
        $result = Vod::V20170321()->getVideoInfo()->client('AliyunVod')->withVideoId($videoId)->format('JSON')->request();
        if ($result->isSuccess()) {
            return $result->Video;
        }

        return false;
    }

    /**
     * 提交媒体转码作业
     * Author: 亓官雨树 <lucky.max@foxmail.com>
     * Date: 22/11/01
     *
     * @throws ClientException
     */
    public function submitTranscodeJobs($videoId)
    {
        AlibabaCloud::accessKeyClient(Config::get('accessKey_id'), Config::get('accessKey_secret'))
            ->regionId(Config::get('vod_region_id', 'cn-shanghai'))
            ->asDefaultClient()->options([]);

        // try {
            $request = Vod::v20170321()->submitTranscodeJobs();
            $result  = $request->withTemplateGroupId(Config::get('hls_template_id'))
                ->withVideoId($videoId)
                // ->debug(true)
                ->request();
        //     Db::name('test')->save(['msg'=>'submitTranscodeJobs/VideoId:'.$videoId.':Success:' . $result]);
        // } catch (ClientException $exception) {
        //     Db::name('test')->save(['msg'=>'submitTranscodeJobs/VideoId:'.$videoId.':ClientException:' . $exception->getMessage()]);
        // } catch (ServerException $exception) {
        //     Db::name('test')->save(['msg'=>'submitTranscodeJobs/VideoId:'.$videoId.':ServerException:Message:' . $exception->getMessage() . '/ErrorCode' . $exception->getErrorCode() . '/RequestId' . $exception->getRequestId() . '/ErrorMessage' . $exception->getErrorMessage()]);
        // }
    }
}
