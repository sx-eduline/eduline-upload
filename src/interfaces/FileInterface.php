<?php
declare (strict_types=1);

namespace eduline\upload\interfaces;

use app\common\model\Attach;

interface FileInterface
{
    /** 本地上传 */
    public function putFile();

    /** 本地上传到云端 */
    public function putYunFile(Attach $attach);

    /** 文件访问地址 */
    public function url();

    /** 文件储存位置 */
    public function path();
}
