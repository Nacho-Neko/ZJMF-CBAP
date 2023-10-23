<?php
/**
 * @desc 插件后台配置
 * @author wyh
 * @version 1.0
 * @time 2022-06-01
 */
return[
    'module_name'            => [    # 在后台插件配置表单中的键名(统一规范:小写+下划线),会是config[module_name]
        'title' => '名称',            # 表单的label标题
        'type'  => 'text',           # 表单的类型：text文本,password密码,checkbox复选框,select下拉,radio单选,textarea文本区域,tip提示
        'value' => '微信支付',     # 表单的默认值
        'tip'   => 'friendly name',  # 表单的帮助提示
        'size'  => 200,               # 输入框长度(当type类型为text,password,textarea,tip时,可传入此键)
    ],
    'AppId'=>[
        'title'=>'AppId',
        'type'=>'text',
        'value'=>'',
        'tip'=>'配置信息',
        'size'  => 200,
    ],
    'MerchantId'=>[
        'title'=>'MerchantId',
        'type'=>'text',
        'value'=>'',
        'tip'=>'配置信息',
        'size'  => 200,
    ],
    'Key'=>[
        'title'=>'Key',
        'type'=>'text',
        'value'=>'',
        'tip'=>'商户配置信息',
        'size'  => 200,
    ],
    'AppSecret'=>[
        'title'=>'AppSecret',
        'type'=>'text',
        'value'=>'',
        'tip'=>'配置信息',
        'size'  => 200,
    ],

];