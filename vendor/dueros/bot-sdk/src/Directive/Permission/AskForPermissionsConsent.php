<?php
/**
 * Copyright (c) 2017 Baidu, Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @desc 用于生成Permission.AskForPermissionsConsent指令的类
 **/
namespace Baidu\Duer\Botsdk\Directive\Permission;

/**
 * 技能需要获取某个用户个性化权限时，通过返回权限指令来让用户进行授权。
 * 在用户同意授权、拒绝授权或授权失败时会发送事件给技能。
 * 授权成功事件：Permission.Granted
 * 授权拒绝事件：Permission.Rejected
 * 授权失败事件：Permission.GrantFailed
 */
class AskForPermissionsConsent extends \Baidu\Duer\Botsdk\Directive\BaseDirective{

    const READ_USER_PROFILE = 'READ::USER:PROFILE';
    const READ_DEVICE_LOCATION = 'READ::DEVICE:LOCATION';
    const WRITE_SMARTHOME_PRINTER = 'WRITE::SMARTHOME:PRINTER';
    const RECORD_SPEECH = 'RECORD::SPEECH';

    protected static $permissions = array(
        self::READ_USER_PROFILE,
        self::READ_DEVICE_LOCATION,
        self::WRITE_SMARTHOME_PRINTER,
        self::RECORD_SPEECH
    );

    public function __construct() {
        parent::__construct('Permission.AskForPermissionsConsent');
        $this->data['token'] = $this->genToken();
    }

    /**
     * @desc 设置directive的token. 默认在构造时自动生成了token，可以覆盖
     * @param string $token token
     * @return null
     **/
    public function setToken($token){
        if($token && is_string($token)) {
            $this->data['token'] = $token;
        }
    }

    /**
     * 增加权限
     * @param enum $name 权限名称
     */
    public function addPermission($name){
        if($name && in_array($name, self::$permissions)){
            $this->data['permissions'][] = array(
                'name' => $name
            ); 
        }
    }
}
 

