<?php
declare (strict_types = 1);
namespace eduline\upload\interfaces;

interface FileInterface
{
    /** 存放文件 */
    public function putFile();
    /** 文件访问地址 */
    public function url();
    /** 文件储存位置 */
    public function path();
}
