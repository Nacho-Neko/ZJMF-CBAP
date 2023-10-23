<?php
namespace server\mf_dcim\validate;

use think\Validate;

/**
 * @title 保存参数验证
 * @use  server\mf_dcim\validate\HostUpdateValidate
 */
class HostUpdateValidate extends Validate{

	protected $rule = [
        'bw'                    => 'integer|between:0,30000',
        'in_bw'                 => 'integer|between:0,30000',
        'out_bw'                => 'integer|between:0,30000',
        'flow'                  => 'integer|between:0,999999',
        'defence'               => 'integer|between:0,999999',
        'ip_num'                => 'integer|between:0,10000',
    ];

    protected $message = [
        'bw.integer'            => '带宽只能是0-30000的整数',
        'bw.between'            => '带宽只能是0-30000的整数',
        'in_bw.integer'         => '进带宽只能是0-30000的整数',
        'in_bw.between'         => '进带宽只能是0-30000的整数',
        'out_bw.integer'        => '出带宽只能是0-30000的整数',
        'out_bw.between'        => '出带宽只能是0-30000的整数',
        'flow.integer'          => '流量只能是0-999999的整数',
        'flow.between'          => '流量只能是0-999999的整数',
        'defence.integer'       => '防御只能是0-999999的整数',
        'defence.between'       => '防御只能是0-999999的整数',
        'ip_num.integer'        => '附加IP数量只能是0-999999的整数',
        'ip_num.between'        => '附加IP数量只能是0-999999的整数',
    ];

    protected $scene = [
        'update' => ['bw','in_bw','out_bw','flow','defence','ip_num'],
    ];


}