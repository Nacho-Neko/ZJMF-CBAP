<?php
namespace app\admin\validate;

use think\Validate;

/**
 * 管理员验证
 */
class AdminValidate extends Validate
{
	protected $rule = [
		'id' 		    => 'require|integer',
        'name' 		    => 'require|max:50|min:1|unique:admin',
        'password' 		=> 'require|max:32|min:6|checkPassword:thinkphp',
        'repassword'	=> 'require|confirm:password',
        'email' 		=> 'require|email|unique:admin',
        'nickname' 		=> 'require|max:20|min:1',
        'remember_password' => 'in:0,1',
        'phone_code'    => 'number',
        'phone'         => 'max:11|number',
    ];

    protected $message  =   [
    	'id.require'     			=> 'id_error',
    	'id.integer'     			=> 'id_error',
        'name.require'     			=> 'please_enter_admin_name',
        'name.min'     			    => 'admin_name_at_least_1_chars',
        'name.max'     			    => 'admin_name_cannot_exceed_50_chars',
        'name.unique'     			=> 'admin_name_unique',
        'password.require'        	=> 'please_enter_password',
        //'password.alphaNum'        	=> 'password_formatted_incorrectly',
        'password.max'        	    => 'password_formatted_incorrectly',
        'password.min'        	    => 'password_formatted_incorrectly',
        'repassword.require'        => 'please_enter_password_again',
        'repassword.confirm'        => 'passwords_not_match',
        'nickname.require'     		=> 'please_enter_admin_nickname',
        'nickname.max'     			=> 'admin_nickname_cannot_exceed_20_chars',
        'nickname.min'     			=> 'admin_nickname_at_least_1_chars',
        'email.require' 			=> 'please_enter_vaild_email',
        'email.email'        		=> 'please_enter_vaild_email',
        'email.unique'        		=> 'admin_email_unique',
        'remember_password.in'      => 'remember_password_value_0_or_1',
        'phone_code.number'         => '国际电话区号格式错误',
        'phone.max'                 => '手机号格式错误',
        'phone.number'              => '手机号格式错误',
    ];

    protected $scene = [
        'create' => ['name', 'email', 'password', 'repassword', 'nickname', 'phone', 'phone_code'],
        'update' => ['id', 'name', 'email', 'nickname', 'phone', 'phone_code'],
        'password' => ['password', 'repassword'],
    ];

    # 登录验证
    public function sceneLogin()
    {
        return $this->only(['name','password','remember_password'])
            ->remove('name', 'max|min|unique')
            ->remove('password', 'checkPassword|max|min');
    }

    // 验证密码无汉字
    protected function checkPassword($value,$rule,$data)
    {
        if (preg_match("/[\\x7f-\\xff]/", $value)) { //判断字符串中是否有中文
            return 'password_formatted_incorrectly';
        }

        return true;
    }
}