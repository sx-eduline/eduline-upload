<?php
declare (strict_types=1);

namespace eduline\upload\interfaces;

interface FileInterface
{
    /**
     * 本地上传
     *
     * @param $savepath
     * @param $file
     * @param $savename
     * @return mixed
     */
    public function putFile($savepath, $file, $savename);

    /**
     * 本地上传到云端
     *
     * @param $attach
     * @return mixed
     */
    public function putYunFile($attach);

    /**
     * 文件访问地址
     *
     * @param array $data
     * @return mixed
     */
    public function url(array $data = []);

    /**
     * 文件储存位置
     *
     * @param array $data
     * @return mixed
     */
    public function path(array $data = []);

    /**
     * 列表
     *
     * @param array $params
     * @return mixed
     */
    public function getVideoList(array $params = []);

    /**
     * 删除文件
     *
     * @param $attach
     * @return mixed
     */
    public function delete($attach);
}
