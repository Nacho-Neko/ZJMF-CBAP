<?php

return [
    'id_error' => 'ID错误',
    'param_error' => '参数错误',
    'success_message' => '请求成功',
    'fail_message' => '请求失败',
    'create_success' => '创建成功',
    'create_fail' => '创建失败',
    'delete_success' => '删除成功',
    'delete_fail' => '删除失败',
    'update_success' => '修改成功',
    'update_fail' => '修改失败',
    'cannot_repeat_opreate' => '不可重复操作',
    'promo_code_require' => '请填写优惠码',
    'promo_code_error' => '优惠码只能为9位且包含大小写字母数字',
    'promo_code_unique' => '优惠码已存在',
    'promo_code_start_time_require' => '请填写生效时间',
    'promo_code_start_time_date' => '生效时间错误',
    'promo_code_end_time_date' => '截止时间错误',
    'promo_code_end_time_gt' => '截止时间需要大于生效时间',
    'promo_code_max_times_require' => '请填写最大使用次数',
    'promo_code_max_times_error' => '最大使用次数需为大于等于0的整数',
    'promo_code_notes_max' => '备注长度最大为1000字符',
    'promo_code_is_not_exist' => '优惠码不存在',
    'promo_code_type_percent_value_error' => '折扣比例只能为大于0且小于等于100的数',
    'promo_code_type_fixed_amount_value_error' => '减免金额只能为大于0的数',
    'promo_code_type_replace_price_value_error' => '覆盖金额只能为0以上的数',
    'promo_code_product_is_not_exist' => '商品不存在',
    'promo_code_type_percent_description' => '优惠码{promo_code}应用至产品Host Id:{host_id}, {value}% 百分比 折扣',
    'promo_code_type_fixed_amount_description' => '优惠码{promo_code}应用至产品Host Id:{host_id}, {value} 固定金额减免 折扣',
    'promo_code_type_replace_price_description' => '优惠码{promo_code}应用至产品Host Id:{host_id}, {value} 覆盖价格 折扣',
    'promo_code_type_free_description' => '优惠码{promo_code}应用至产品Host Id:{host_id}, 免费 折扣',



    'log_admin_create_promo_code' => '{admin}新增优惠码:{promo_code}',
    'log_admin_update_promo_code' => '{admin}修改优惠码{promo_code}:{description}',
    'log_admin_delete_promo_code' => '{admin}删除优惠码:{promo_code}',
    'log_admin_enable_promo_code' => '{admin}启用优惠码:{promo_code}',
    'log_admin_disable_promo_code' => '{admin}禁用优惠码:{promo_code}',
    'promo_code_client_use_promo_code' => '{client}使用优惠码:{promo_code},应用于订单:{order_id}',


    # 优惠码可用判断
    'addon_promo_code_not_found' => '未找到优惠码',
    'addon_promo_code_has_expired' => '该优惠码已失效',
    'addon_promo_code_product_cannot_use' => '该优惠码无法应用到该产品',
    'addon_promo_code_the_condition_cannot_use' => '尚未达到优惠码使用条件',
    'addon_promo_code_upgrade_cannot_use' => '该优惠码无法在升降级时使用',
    'addon_promo_code_renew_cannot_use' => '该优惠码无法在续费产品时使用',
    'addon_promo_code_only_new_client' => '该优惠码仅可用于无产品新用户',
    'addon_promo_code_only_old_client' => '该优惠码仅可用于账户内存在激活产品的用户',
    'addon_promo_code_higher_cannot_use' => '应用优惠码后价格高于原价格，无法应用',

    # 导航
    'nav_plugin_addon_promo_code' => '优惠码',

];
