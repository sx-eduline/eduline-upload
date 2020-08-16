<?php
declare (strict_types = 1);
namespace eduline\upload\utils;

use think\facade\Validate;
use think\File as ThinkFile;

class Util
{
    /**
     * 是否视频
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-08-15
     * @param    [type]                         $mimetype  [description]
     * @param    [type]                         $extension [description]
     * @return   boolean                                   [description]
     */
    public static function isVideo($mimetype, $extension)
    {
        return stripos($mimetype, 'video') !== false || in_array(strtolower($extension), ['avi', 'mp4', 'wmv', 'mov', 'mkv', 'flv', 'f4v', 'm4v', 'rmvb', '3gp', 'rm', 'ts', 'dat', 'mts', 'vob', 'mpeg']);
    }

    /**
     * 是否音频
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-08-15
     * @param    [type]                         $mimetype  [description]
     * @param    [type]                         $extension [description]
     * @return   boolean                                   [description]
     */
    public static function isAudio($mimetype, $extension)
    {
        return stripos($mimetype, 'audio') !== false || in_array(strtolower($extension), ['mp3', 'wav', 'acc', 'asf']);
    }

    /**
     * 是否图片格式
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-08-16
     * @param    [type]                         $mimetype [description]
     * @return   boolean                                  [description]
     */
    public static function isImage($mimetype)
    {
        return stripos($mimetype, 'image') !== false;
    }

    /**
     * 是否图片文件
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2020-08-16
     * @param    string                         $filepath [description]
     * @return   boolean                                  [description]
     */
    public static function isImageFile(string $filepath)
    {
        return Validate::is(new ThinkFile($filepath), 'image');
    }
}
