<?php

return [
    'disks' => [
        'local' => [
            'driver' => \klightsaber\drivers\LocalDriver::class,//上传类
            'save_dir' => public_path('uploads') ,//上传保存路径
            'preview_dir' => '/uploads'
        ] ,
        //继续添加其它仓库
    ]
    ,'default' => [
        'disk' => 'local' ,//默认磁盘
        'extensions' => 'jpg,png,mp4' ,//后缀
        'mimeTypes' => 'image/*,video/*' ,//类型
        'middlewares' => [],//中间件数组
        'tmp_dir' => root_path('runtime' . DIRECTORY_SEPARATOR . 'tem')
    ]
];
