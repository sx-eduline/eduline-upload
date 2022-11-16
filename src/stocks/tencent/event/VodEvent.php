<?php
declare (strict_types=1);

namespace eduline\upload\stocks\tencent\event;

use app\common\model\Attach;
use app\common\service\BaseService;
use eduline\upload\stocks\tencent\Config;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Vod\V20180717\Models\DescribeMediaInfosRequest;
use TencentCloud\Vod\V20180717\Models\ProcessMediaByProcedureRequest;
use TencentCloud\Vod\V20180717\VodClient;
use think\Request;

/**
 * 点播事件回调
 */
class VodEvent
{
    public function handle(Request $request)
    {

        $eventType = $request->post('EventType');

        $response = BaseService::parseToData([], 1);

        switch ($eventType) {
            // // 视频上传完成
            // case 'NewFileUpload':
            //     $body = $request->post('FileUploadEvent');
            //     // 使用任务流模板进行视频处理ProcessMediaByProcedure
            //     $this->processMediaByProcedure($body['FileId']);
            //     break;

            // 视频任务流变更
            case 'ProcedureStateChanged':
                $body   = $request->post('ProcedureStateChangeEvent');
                $status = $body['Status'] == 'FINISH';
                if ($status) {
                    // 任务流完成了
                    $fileId = $body['FileId'];
                    // 获取信息
                    $info   = $this->getInfo($fileId);
                    $update = [];
                    // 时长
                    if ($info->MetaData) {
                        // 时长向下取整
                        $update['duration'] = floor($info->MetaData->Duration);

                    }
                    // 封面
                    $basic = $info->BasicInfo;
                    if ($basic->CoverUrl) {
                        $update['cover_url'] = $basic->CoverUrl;
                    }
                    // 状态
                    $update['status'] = $basic->Status == 'Normal' ? 1 : 0;

                    // 更新视频信息
                    Attach::update($update, ['stock' => 'tencent', 'savename' => $fileId, 'status' => 4]);

                }

                break;
            default:
                # code...
                break;
        }

        return $response;
    }

    public function processMediaByProcedure($fileId)
    {
        try {
            $cred        = new Credential(Config::get('secret_id'), Config::get('secret_key'));
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("vod.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $vodRegion = Config::get('vod_region', 'ap-chengdu');
            $client    = new VodClient($cred, $vodRegion, $clientProfile);
            $req       = new ProcessMediaByProcedureRequest();

            $params = [
                "FileId"        => $fileId,
                "ProcedureName" => Config::get('vod_hls_name')
            ];
            $req->fromJsonString(json_encode($params));
            $resp = $client->ProcessMediaByProcedure($req);
            // print_r($resp->toJsonString());
        } catch (TencentCloudSDKException $e) {
            echo $e;
        }
    }

    public function getInfo($fileId)
    {
        $cred        = new Credential(Config::get('secret_id'), Config::get('secret_key'));
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint("vod.tencentcloudapi.com");

        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        $vodRegion = Config::get('vod_region', 'ap-chengdu');
        $client    = new VodClient($cred, $vodRegion, $clientProfile);
        $req       = new DescribeMediaInfosRequest();

        $params = [
            "FileIds" => [$fileId],
        ];
        $req->fromJsonString(json_encode($params));

        $resp = $client->DescribeMediaInfos($req);

        return $resp->MediaInfoSet[0];
    }
}
