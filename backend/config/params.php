<?php

return [
    'adminEmail' => 'petan@xuttanh.com',
    'adminAcronym' => 'xuttanh',
    'adminTitle' => 'Pet AN',
    'adminDefaultHomePage' => ['main/system'], // 默认主页

    /** ------ 总管理员配置 ------ **/
    'adminAccount' => 1,// 系统管理员账号id
    'isMobile' => false, // 手机访问

    /** ------ 日志记录 ------ **/
    'user.log' => true,
    'user.log.level' => ['warning', 'error'], // 级别 ['success', 'info', 'warning', 'error']
    'user.log.except.code' => [404], // 不记录的code

    /** ------ 开发者信息 ------ **/
    'exploitDeveloper' => 'xuhh',
    'exploitFullName' => '文章后台管理系统',
    'exploitOfficialWebsite' => '<a href="http://www.petan.com" target="_blank">www.petan.com</a>',
    'exploitGitHub' => '<a href="https://github.com/xuttanh" target="_blank">https://github.com/xuttanh</a>',

    /**
     * 不需要验证的路由全称
     *
     * 注意: 前面以绝对路径/为开头
     */
    'noAuthRoute' => [
        '/main/index',// 系统主页
        '/main/system',// 系统首页
        '/main/member-between-count',
        '/main/member-credits-log-between-count',
    ],
];