<?php
return [
  'doc'=>[
      //排除目录或文件命令空间(起始目录为当前项目目录)
      'except'=>[
          'plugin/curd',
          \plugin\curd\controller\api\Controller::class
      ],
      //全局请求头
      'header'=>[
//          [
//              'key'=>'token',
//              'value'=>'',
//              'desc'=>'认证token',
//          ]
      ]
  ]
];
