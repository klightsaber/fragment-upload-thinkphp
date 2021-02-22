<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

namespace klightsaber\drivers;

use klightsaber\contract\FileInfo;
use klightsaber\exception\FragmentUploadException;
use klightsaber\FragmentUploadInterface;

class LocalDriver implements FragmentUploadInterface
{

    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function upload(FileInfo $fileInfo)
    {
        if (!empty($_FILES)) {
            if (!$in = fopen($_FILES["file"]["tmp_name"], "rb")) {
                 throw new FragmentUploadException(1004,'open tmp file failed');
            }
        } else {
            if (!$in = fopen("php://input", "rb")) {
                throw new FragmentUploadException(1005,'open php input stream  failed');
            }
        }
        if ($fileInfo->totalChunks === 1) {
            //如果只有1片，则不需要合并，直接将临时文件转存到保存目录下
            $saveDir = rtrim($this->config['save_dir'],'/')   .DIRECTORY_SEPARATOR .'resources'.DIRECTORY_SEPARATOR. date('Ymd');
            if (!is_dir($saveDir)) {
                mkdir($saveDir,0777,true);
            }
            $random = lcg_value();
            $uploadPath = $saveDir . DIRECTORY_SEPARATOR .$fileInfo->identifier.$random.'.'.$fileInfo->ext;
            $res['filepath'] = rtrim($this->config['preview_dir'],'/').'/resources/'.date('Ymd') . '/' . $fileInfo->identifier.$random.'.'.$fileInfo->ext;
            $res['savepath'] = $uploadPath;
            $res['merge'] = false;
        } else { //需要合并
            $filePath = config('fragmentUpload.default.tmp_dir') . DIRECTORY_SEPARATOR . $fileInfo->identifier; //临时分片文件路径
            $uploadPath = $filePath . '_' . $fileInfo->chunkNumber; //临时分片文件名
            $res['merge'] = true;
        }
        if (!$out = fopen($uploadPath, "wb")) {
            throw new FragmentUploadException(1006,'upload path is not writable'.$uploadPath);
        }
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        fclose($in);
        fclose($out);

        $res['code'] = 0;
        return $res;
    }

    public function merge(FileInfo $fileInfo)
    {

        $filePath = config('fragmentUpload.default.tmp_dir') . DIRECTORY_SEPARATOR . $fileInfo->identifier;

        $totalChunks = $fileInfo->totalChunks; //总分片数

        $done = true;
        //检查所有分片是否都存在
        for ($index = 1; $index <= $totalChunks; $index++ ) {
            if (!file_exists("{$filePath}_{$index}")) {
                $done = false;
                break;
            }
        }
        if ($done === false) {
            throw new FragmentUploadException(1007,'Incomplete file chunks,total chunk is:'.$totalChunks.' and '.$index.' is found');
        }
        //如果所有文件分片都上传完毕，开始合并
        $saveDir = rtrim($this->config['save_dir'],'/')  .DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR. date('Ymd');
        if (!is_dir($saveDir)) {
            mkdir($saveDir,0777,true);
        }
        $random = lcg_value();
        $uploadPath = $saveDir . DIRECTORY_SEPARATOR .$fileInfo->identifier.$random.'.'.$fileInfo->ext;

        if (!$out = fopen($uploadPath, "wb")) {
            throw new FragmentUploadException(1006,'upload path is not writable');
        }
        if (flock($out, LOCK_EX) ) { // 进行排他型锁定
            for($index = 1; $index <= $totalChunks; $index++ ) {
                if (!$in = fopen("{$filePath}_{$index}", "rb")) {
                    break;
                }
                while ($buff = fread($in, 4096)) {
                    fwrite($out, $buff);
                }
                fclose($in);
                unlink("{$filePath}_{$index}"); //删除分片
            }

            flock($out, LOCK_UN); // 释放锁定
        }
        fclose($out);

        $res['code'] = 0;
        $res['filepath'] = rtrim($this->config['preview_dir'],'/').'/resources/'.date('Ymd') . '/' . $fileInfo->identifier.$random.'.'.$fileInfo->ext;
        $res['savepath'] = $uploadPath;


        return $res;
    }

    //检测断点和md5
    public function checkFile(FileInfo $fileInfo)
    {

        $identifier = $fileInfo->identifier;
        $filePath = config('fragmentUpload.default.tmp_dir') . DIRECTORY_SEPARATOR . $identifier; //临时分片文件路径
        $totalChunks = $fileInfo->totalChunks;

        //检查分片是否存在
        $chunkExists = [];
        for ($index = 1; $index <= $totalChunks; $index++ ) {
            if (file_exists("{$filePath}_{$index}")) {
                array_push($chunkExists, $index);
            }
        }
        if (count($chunkExists) == $totalChunks) { //全部分片存在，则直接合成
            return $this->merge($fileInfo);
        } else {
            $res['uploaded'] = $chunkExists;
            return $res;
        }
    }
}
