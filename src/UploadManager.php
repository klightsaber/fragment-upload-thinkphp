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

namespace klightsaber;

use klightsaber\contract\FileInfo;
use klightsaber\exception\FragmentUploadException;
use think\Config;

class UploadManager
{
    protected $diskInstance;
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config->get('fragmentUpload',[]);
        $this->init();
    }

    protected function init()
    {
        $defaultDisk = !empty($this->config['default']['disk']) ?$this->config['default']['disk']: '';
        if(!empty($this->config['disks'][$defaultDisk]['driver']) && class_exists($this->config['disks'][$defaultDisk]['driver'])){
            $diskDriver = $this->config['disks'][$defaultDisk]['driver'];
            $this->diskInstance = new $diskDriver($this->config['disks'][$defaultDisk]);
            if(!$this->diskInstance instanceof FragmentUploadInterface){
                throw new FragmentUploadException(1003,'your disk instance must implement '.FragmentUploadInterface::class);
            }
            if(!file_exists($this->config['default']['tmp_dir'])){
                mkdir($this->config['default']['tmp_dir'],0777,true);
            }
        }else{
            throw new FragmentUploadException(1000,'init default disk failed, check your fragmentUpload config file');
        }

    }

    public function disk($diskName)
    {
        if(!empty($this->config['disks'][$diskName]['driver']) && class_exists($this->config['disks'][$diskName]['driver'])){
            $diskDriver = $this->config['disks'][$diskName]['driver'];
            $this->diskInstance = new $diskDriver($this->config['disks'][$diskName]);

            if(!$this->diskInstance instanceof FragmentUploadInterface){
                throw new FragmentUploadException(1003,'your disk instance must implement '.FragmentUploadInterface::class);
            }
        }else{
            throw new FragmentUploadException(1002,'create disk intance failed, check your fragmentUpload config file');
        }
    }

    public function upload(FileInfo $fileInfo)
    {
        return $this->diskInstance->upload($fileInfo);
    }

    public function merge(FileInfo $fileInfo){
        return $this->diskInstance->merge($fileInfo);
    }

    public function checkFile(FileInfo $fileInfo){
        return $this->diskInstance->checkFile($fileInfo);
    }
}
