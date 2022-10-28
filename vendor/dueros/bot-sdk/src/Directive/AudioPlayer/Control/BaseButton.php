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
 **/
namespace Baidu\Duer\Botsdk\Directive\AudioPlayer\Control;
/**
 * @desc BaseButton基类
 */
abstract class BaseButton{

    protected $data = [];

    /**
     * @param string $type 类型
     * @param string $name 名称
     */
    public function __construct($type, $name) {
        $this->data['type'] = $type;
        $this->data['name'] = $name;
    }

    /**
     * @desc getData
     * @return array Control数据
     */ 
    public function getData(){
        return $this->data;
    }
}
 

