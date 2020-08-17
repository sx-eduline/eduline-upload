<?php
declare (strict_types = 1);
namespace eduline\upload\stocks\aliyun\event;

use app\common\model\Attach;
use think\Request;

/**
 * 点播事件回调
 */
class VodEvent
{
    public function handle(Request $request)
    {
        $eventType = $request->post('EventType');
        $videoId   = $request->post('VideoId');
        $status    = $request->post('Status') == 'success' ? 1 : 0;
        switch ($eventType) {
            // 视频转码完成
            case 'TranscodeComplete':
                $streamInfos = $request->post('StreamInfos');
                // 音频视频都是一样的,所以取第一个即可获取视频时长
                $duration = $streamInfos[0]['Duration'];
                // 时长向下取整
                $duration = floor($duration);
                // 更新视频信息
                Attach::update(['status' => $status, 'duration' => $duration], ['stock' => 'aliyun', 'savename' => $videoId, 'status' => 4]);
                break;

            default:
                # code...
                break;
        }
    }
}
