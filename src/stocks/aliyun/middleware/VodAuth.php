<?php
declare (strict_types = 1);
namespace eduline\upload\stocks\aliyun\middleware;

use app\common\service\BaseService;
use eduline\upload\stocks\aliyun\Config;

/**
 * 视频点播回调鉴权
 */
class VodAuth
{

    public function handle($request, \Closure $next)
    {
        // 鉴权
        $vodTimestamp = $request->header('x-vod-timestamp');
        $vodSignature = $request->header('x-vod-signature');
        // 密钥
        $privateKey = Config::get('private_key');
        if (!$vodTimestamp || !$vodSignature || !$privateKey) {
            return BaseService::parseToData(['error_code' => 401], 0, '鉴权出错', 401);

        }
        // 签名
        $domain = $request->domain() . '/aliyun/vod/event';
        $sign   = md5($domain . '|' . $vodTimestamp . '|' . $privateKey);

        if (strtolower($sign) != strtolower($vodSignature)) {
            return BaseService::parseToData(['error_code' => 401], 0, '鉴权出错', 401);
        }

        return $next($request);
    }
}
