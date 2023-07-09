<?php
declare (strict_types=1);

namespace eduline\upload;

/**
 * Class Client
 *
 * @package eduline\upload
 *
 * @method  array putFile($savepath, $file, $savename) 上传文件到本地, 只有local储存可调用。参数: 保存路径, 临时文件名称, 保存的文件文件
 * @method void putYunFile($attach) 上传到云端，非local储存可调用。参数：附件数据
 * @method string url(array $data = []) 获取附件预览的地址。参数：附件数据
 * @method string path(array $data = []) 获取附件储存的路径。参数：附件数据
 * @method array getVideoList(array $params = []) 获取视频列表。参数：第三方储存请求参数
 */
class Client
{
    protected $stock;

    public function __construct($stock = null)
    {
        // 当前处理类
        $class = __NAMESPACE__ . '\\stocks\\' . $stock . '\\File';

        $this->stock = new $class();
    }

    /**
     * 动态调用
     * Author   Martinsun<syh@sunyonghong.com>
     * Date:  2020-03-29
     *
     * @param    [type]                         $method [description]
     * @param    [type]                         $args   [description]
     * @return   [type]                                 [description]
     */
    public function __call($method, $args)
    {
        return $this->stock->$method(...$args);
    }
}
