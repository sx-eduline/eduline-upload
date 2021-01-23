<?php
declare (strict_types=1);

namespace eduline\upload;

use app\common\model\Attach;

/**
 * 上传入口类
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
     * 上传文件
     * Author   Martinsun<syh@sunyonghong.com>
     * Date:  2020-03-29
     *
     * @param string $savepath 需要保存的路径
     * @param    [type]                         $file     [description]
     * @param    [type]                         $savename [description]
     * @return   [type]                                   [description]
     */
    public function putFile($savepath = '', $file, $savename)
    {
        return $this->stock->putFile($savepath, $file, $savename);
    }

    public function putYunFile(Attach $attach)
    {
        return $this->stock->putYunFile($attach);
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
