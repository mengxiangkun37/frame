<?php

/*
 * API 接口文件
 * version 0.1
 * 说明：接口只支持ajax调用
 * 架构：1、接口列表存在数据库；2、接口代码分散通过api的controller与action调用；3、接口文件必须返回格式：{ code:0, data: …, msg: ''}，调用者通过code判定是否成功
 * 流程说明：1、获取api列表，判定是否存在，2、通过action执行，3、
 * 目前问题：api与页面输出混合，集中业务的同时，使代码凌乱，无法通过base方法有效控制
 *
 *
 */


namespace app\web\api;
use app\common\controller\ApiCommon;
class WebBase extends ApiCommon {

    public function _initialize() {
        parent::_initialize();
    }

}
