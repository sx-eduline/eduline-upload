<?php
declare (strict_types = 1);
namespace eduline\upload;

class Stock
{
    /**
     * 获取储存空间列表
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-03-27
     * @return   [type]                         [description]
     */
    public static function getStocks()
    {
        $dir    = __DIR__ . '/' . 'stocks';
        $stocks = [];
        // 遍历文件夹
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ((is_dir($dir . '/' . $file)) && $file != '.' && $file != '..') {
                    // 读取.ini配置文件
                    $config = $dir . '/' . $file . '/' . '.ini';
                    if (is_file($config)) {
                        $stocks[] = parse_ini_file($config, true, INI_SCANNER_TYPED);
                    }
                }
            }
            closedir($dh);
        }

        return $stocks;
    }

    /**
     * 获取配置界面表单
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-03-28
     * @param    string                         $stock [description]
     * @return   [type]                                [description]
     */
    public static function getStockConfigPage(string $stock)
    {
        $stdclass = __NAMESPACE__ . '\\stocks\\' . $stock . '\\Config';

        return $stdclass::page();
    }

    /**
     * 获取储存配置字段信息
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-03-27
     * @param    string                         $stock 储存端标识
     * @return   [type]                                [description]
     */
    public static function getStockConfig(string $stock, $getClass = false)
    {
        $stdclass = __NAMESPACE__ . '\\stocks\\' . $stock . '\\Config';

        return $getClass ? new $stdclass() : $stdclass::get();

    }
}
