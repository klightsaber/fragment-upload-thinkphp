<?php

namespace klightsaber;

use klightsaber\contract\FileInfo;

/**
 * 缓存驱动接口
 */
interface FragmentUploadInterface
{
    //检测断点和md5
    public function checkFile(FileInfo $fileInfo);

    //上传分片
    public function upload(FileInfo $fileInfo);


    //合并文件
    public function merge(FileInfo $fileInfo);
}
