<?php
declare (strict_types=1);

namespace eduline\upload\stocks\bokecc\event;

use app\common\model\Attach;
use app\common\service\BaseService;
use eduline\upload\stocks\bokecc\Config;
use eduline\upload\stocks\bokecc\File;
use think\facade\Db;
use think\Request;

/**
 * 点播事件回调
 */
class VodEvent
{
    public function handle(Request $request)
    {
        $status = $request->get('status') == 'OK' ? 1 : 0;
        if ($status) {
            $videoId   = $request->get('videoid');
            $duration  = $request->get('duration');
            $cover_url = $request->get('image');
            // 时长向下取整
            $duration = floor($duration);
            // 更新视频信息
            $update = ['stock' => 'bokecc', 'duration' => $duration, 'cover_url' => $cover_url, 'status' => 1];
            // 更新视频信息
            Attach::update($update, ['savename' => $videoId]);
            $this->returnXml();
        }
    }

    public function returnXml()
    {
        header("Content-Type:text/xml");
        $xml = '<?xml version="1.0" encoding="UTF-8"?><result>OK</result>';
        echo $xml;
    }

}
