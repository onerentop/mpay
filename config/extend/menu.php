<?php
// 后台菜单配置 - 与示例站点一致
return [
    [
        "id" => "console",
        "title" => "平台首页",
        "icon" => "icon pear-icon pear-icon-home",
        "type" => 1,
        "openType" => "_iframe",
        "href" => "Console/console"
    ],
    [
        "id" => "order",
        "title" => "订单管理",
        "icon" => "icon pear-icon pear-icon-survey",
        "type" => 1,
        "openType" => "_iframe",
        "href" => "/Order/index"
    ],
    [
        "id" => "payManage",
        "title" => "账号管理",
        "icon" => "icon pear-icon pear-icon-security",
        "type" => 1,
        "openType" => "_iframe",
        "href" => "/PayManage/index"
    ],
    [
        "id" => "pluginManage",
        "title" => "插件管理",
        "icon" => "icon pear-icon pear-icon-modular",
        "type" => 1,
        "openType" => "_iframe",
        "href" => "/Plugin/index"
    ],
    [
        "id" => "userCenter",
        "title" => "用户中心",
        "icon" => "icon pear-icon pear-icon-user",
        "type" => 1,
        "openType" => "_iframe",
        "href" => "/User/index"
    ]
];
