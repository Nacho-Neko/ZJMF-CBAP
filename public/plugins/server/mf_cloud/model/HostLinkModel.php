<?php 
namespace server\mf_cloud\model;

use think\Model;
use app\common\model\HostModel;
use app\common\model\ProductModel;
use app\common\model\MenuModel;
use app\admin\model\PluginModel;
use app\common\model\CountryModel;
use addon\idcsmart_cloud\model\IdcsmartSecurityGroupHostLinkModel;
use addon\idcsmart_cloud\model\IdcsmartSecurityGroupModel;
use addon\idcsmart_cloud\model\SecurityGroupRuleModel;
use addon\idcsmart_cloud\model\IdcsmartSecurityGroupLinkModel;
use addon\idcsmart_cloud\model\IdcsmartSecurityGroupRuleLinkModel;
use addon\idcsmart_cloud\model\IdcsmartSecurityGroupRuleModel;
use addon\idcsmart_ssh_key\model\IdcsmartSshKeyModel;
use addon\idcsmart_renew\model\IdcsmartRenewAutoModel;
use server\mf_cloud\logic\ToolLogic;
use server\mf_cloud\idcsmart_cloud\IdcsmartCloud;

/**
 * @title 产品关联模型
 * @use server\mf_cloud\model\HostLinkModel
 */
class HostLinkModel extends Model{

	protected $name = 'module_mf_cloud_host_link';

    // 设置字段信息
    protected $schema = [
        'id'                => 'int',
        'host_id'           => 'int',
        'rel_id'            => 'int',
        'data_center_id'    => 'int',
        'image_id'          => 'int',
        'backup_num'        => 'int',
        'snap_num'          => 'int',
        'power_status'      => 'string',
        'ip'                => 'string',
        'ssh_key_id'        => 'int',
        'password'          => 'string',
        'vpc_network_id'    => 'int',
        'config_data'       => 'string',
        'create_time'       => 'int',
        'update_time'       => 'int',
        'type'              => 'string',
    ];

    /**
     * 时间 2023-02-08
     * @title 魔方云产品列表页
     * @desc 魔方云产品列表页
     * @author hh
     * @version v1
     * @param   int param.page 1 页数
     * @param   int param.limit - 每页条数
     * @param   string param.orderby - 排序(id,due_time,status)
     * @param   string param.sort - 升/降序
     * @param   string param.keywords - 关键字搜索
     * @param   int param.data_center_id - 数据中心搜索
     * @param   string param.status - 产品状态(Unpaid=未付款,Pending=开通中,Active=已开通,Suspended=已暂停,Deleted=已删除)
     * @return  array data.list - 列表数据
     * @return  int data.list[].id - 列表数据
     * @return  string data.list[].name - 产品标识
     * @return  string data.list[].status - 产品状态(Unpaid=未付款,Pending=开通中,Active=已开通,Suspended=已暂停,Deleted=已删除)
     * @return  int data.list[].due_time - 到期时间
     * @return  string data.list[].country - 国家
     * @return  string data.list[].country_code - 国家代码
     * @return  string data.list[].city - 城市
     * @return  string data.list[].package_name - 套餐名称
     * @return  string data.list[].ip - IP
     * @return  string data.list[].image_name - 镜像名称
     * @return  string data.list[].image_group_name - 镜像分组名称
     * @return  string data.list[].power_status - 电源状态(on=开机,off=关机,operating=操作中,fault=故障)
     * @return  int data.list[].active_time - 开通时间
     * @return  string data.list[].product_name - 商品名称
     */
    public function idcsmartCloudList($param){
        $result = [
            'status' => 200,
            'msg'    => lang_plugins('success_message'),
            'data'   => [
                'list'  => [],
                'count' => [],
            ]
        ];

        $clientId = get_client_id();

        if(empty($clientId)){
            return $result;
        }
        
        $param['page'] = isset($param['page']) ? ($param['page'] ? (int)$param['page'] : 1) : 1;
        $param['limit'] = isset($param['limit']) ? ($param['limit'] ? (int)$param['limit'] : config('idcsmart.limit')) : config('idcsmart.limit');
        $param['sort'] = isset($param['sort']) ? ($param['sort'] ?: config('idcsmart.sort')) : config('idcsmart.sort');
        $param['orderby'] = isset($param['orderby']) && in_array($param['orderby'], ['id','due_time','status']) ? $param['orderby'] : 'id';
        $param['orderby'] = 'h.'.$param['orderby'];  

        $where = [];
        $whereDataCenter = [];

        $where[] = ['h.client_id', '=', $clientId];
        $whereDataCenter[] = ['h.client_id', '=', $clientId];
        $where[] = ['h.status', '<>', 'Cancelled'];
        $whereDataCenter[] = ['h.status', '<>', 'Cancelled'];
        if(isset($param['keywords']) && trim($param['keywords']) !== ''){
            $where[] = ['pro.name|h.name|hl.ip', 'LIKE', '%'.$param['keywords'].'%'];
        }
        if(isset($param['data_center_id']) && !empty($param['data_center_id'])){
            $where[] = ['hl.data_center_id', '=', $param['data_center_id']];
        }
        if(isset($param['status']) && !empty($param['status'])){
            if($param['status'] == 'Pending'){
                $where[] = ['h.status', 'IN', ['Pending','Failed']];
            }else if(in_array($param['status'], ['Unpaid','Active','Suspended','Deleted'])){
                $where[] = ['h.status', '=', $param['status']];
            }
        }
        if(isset($param['m']) && !empty($param['m'])){
            $MenuModel = MenuModel::where('menu_type', 'module')
                        ->where('module', 'mf_cloud')
                        ->where('id', $param['m'])
                        ->find();
            if(!empty($MenuModel) && !empty($MenuModel['product_id'])){
                $MenuModel['product_id'] = json_decode($MenuModel['product_id'], true);
                if(!empty($MenuModel['product_id'])){
                    $where[] = ['h.product_id', 'IN', $MenuModel['product_id'] ];
                    $whereDataCenter[] = ['h.product_id', 'IN', $MenuModel['product_id'] ];
                }
            }
        }

        // 获取子账户可见产品
        $res = hook('get_client_host_id', ['client_id' => get_client_id(false)]);
        $res = array_values(array_filter($res ?? []));
        foreach ($res as $key => $value) {
            if(isset($value['status']) && $value['status']==200){
                $hostId = $value['data']['host'];
            }
        }
        if(isset($hostId) && !empty($hostId)){
            $where[] = ['h.id', 'IN', $hostId];
            $whereDataCenter[] = ['h.id', 'IN', $hostId];
        }

        $language = get_client_lang();
        $countryField = ['en-us'=> 'nicename'];
        $countryName = $countryField[ $language ] ?? 'name_zh';

        $count = $this
            ->alias('hl')
            ->join('host h', 'hl.host_id=h.id')
            ->leftJoin('product pro', 'h.product_id=pro.id')
            ->leftJoin('module_mf_cloud_data_center dc', 'hl.data_center_id=dc.id')
            ->leftJoin('module_mf_cloud_image i', 'hl.image_id=i.id')
            ->where($where)
            ->count();

        $host = $this
            ->alias('hl')
            ->field('h.id,h.name,h.status,h.active_time,h.due_time,h.client_notes,pro.name product_name,c.'.$countryName.' country,c.iso country_code,dc.city,dc.area,hl.ip,hl.power_status,i.name image_name,ig.name image_group_name')
            ->join('host h', 'hl.host_id=h.id')
            ->leftJoin('product pro', 'h.product_id=pro.id')
            ->leftJoin('module_mf_cloud_data_center dc', 'hl.data_center_id=dc.id')
            ->leftJoin('country c', 'dc.country_id=c.id')
            ->leftJoin('module_mf_cloud_image i', 'hl.image_id=i.id')
            ->leftJoin('module_mf_cloud_image_group ig', 'i.image_group_id=ig.id')
            ->where($where)
            ->withAttr('status', function($val){
                return $val == 'Failed' ? 'Pending' : $val;
            })
            ->limit($param['limit'])
            ->page($param['page'])
            ->order($param['orderby'], $param['sort'])
            ->group('h.id')
            ->select()
            ->toArray();

        // 获取所有可用数据中心
        $dataCenter = $this
                    ->alias('hl')
                    ->field('dc.id,dc.city,dc.area,c.'.$countryName.' country_name,c.iso')
                    ->join('host h', 'hl.host_id=h.id')
                    ->join('module_mf_cloud_data_center dc', 'hl.data_center_id=dc.id')
                    ->leftJoin('country c', 'dc.country_id=c.id')
                    ->where($whereDataCenter)
                    ->group('dc.id')
                    ->select()
                    ->toArray();

        $result['data']['list']  = $host;
        $result['data']['count'] = $count;
        $result['data']['data_center'] = $dataCenter;
        return $result;
    }

    /**
     * 时间 2022-06-30
     * @title 详情
     * @desc 详情
     * @author hh
     * @version v1
     * @param   int $hostId - 产品ID
     * @return  int data.rel_id - 魔方云ID
     * @return  string data.ip - IP地址
     * @return  int data.backup_num - 允许备份数量
     * @return  int data.snap_num - 允许快照数量
     * @return  string data.power_status - 电源状态(on=开机,off=关机,operating=操作中,fault=故障)
     * @return  int data.data_center.id - 数据中心ID
     * @return  string data.data_center.city - 城市
     * @return  string data.data_center.country_name - 国家
     * @return  string data.data_center.iso - 图标
     * @return  int data.image.id - 镜像ID
     * @return  string data.image.name - 镜像名称
     * @return  string data.image.image_group_name - 镜像分组
     * @return  int data.package.id - 套餐ID
     * @return  string data.package.name - 套餐名称
     * @return  string data.package.description - 套餐描述
     * @return  string data.package.cpu - cpu
     * @return  string data.package.memory - 内存(MB)
     * @return  string data.package.in_bw - 进带宽
     * @return  string data.package.out_bw - 出带宽
     * @return  string data.package.system_disk_size - 系统盘(GB)
     * @return  int data.security_group.id - 关联的安全组ID(0=没关联)
     * @return  string data.security_group.name - 关联的安全组名称
     * @return  string data.duration - 周期
     * @return  string data.first_payment_amount - 首付金额
     * @return  int data.config.reinstall_sms_verify - 重装短信验证(0=不启用,1=启用)
     * @return  int data.config.reset_password_sms_verify - 重置密码短信验证(0=不启用,1=启用)
     */
    public function detail($hostId, $useCache = true){
        $res = [
            'status'=>200,
            'msg'=>lang_plugins('success_message'),
            'data'=>(object)[]
        ];

        $data = [];
        // if($useCache){
        //     // cache('MODULE_MF_CLOUD_DETAIL_'.$hostId, null);
        //     $cache = cache('MODULE_MF_CLOUD_DETAIL_'.$hostId);
        //     if(!empty($cache)){
        //         $data = $cache;

        //         if(app('http')->getName() == 'home' && $data['client_id'] != get_client_id()){
        //             return $res;
        //         }
        //     }
        // }
        // 获取当前
        if(empty($data)){
            $host = HostModel::find($hostId);
            if(empty($host)){
                return $res;
            }
            if(app('http')->getName() == 'home' && $host['client_id'] != get_client_id()){
                return $res;
            }

            $hostLink = $this->where('host_id', $hostId)->find();
            
            $configData = json_decode($hostLink['config_data'], true);

            $data['type'] = $hostLink['type'] ?? 'host';
            $data['order_id'] = $host['order_id'];
            $data['client_id'] = $host['client_id'];
            $data['ip'] = $hostLink['ip'];
            $data['ip_num'] = $configData['ip']['value'] ?? 0;
            $data['backup_num'] = $hostLink['backup_num'];
            $data['snap_num'] = $hostLink['snap_num'];
            $data['power_status'] = $hostLink['power_status'];
            $data['cpu'] = $configData['cpu']['value'];
            $data['memory'] = $configData['memory']['value'];
            $data['system_disk'] = [
                'size' => $configData['system_disk']['value'],
                'type' => $configData['system_disk']['other_config']['disk_type'],
            ];
            $data['line'] = [
                'id' => $configData['line']['id'] ?? 0,
                'name' => $configData['line']['name'] ?? '',
                'bill_type' => $configData['line']['bill_type'] ?? 'bw',
            ];
            $data['bw'] = $configData['bw']['value'] ?? 0;
            if(isset($configData['flow'])){
                $data['flow'] = $configData['flow']['value'];
            }
            $data['peak_defence'] = $configData['defence']['value'] ?? 0;
            $data['network_type'] = $configData['network_type'];
            
            $image = ImageModel::alias('i')
                    ->field('i.id,i.name,ig.name image_group_name,ig.icon')
                    ->leftJoin('module_mf_cloud_image_group ig', 'i.image_group_id=ig.id')
                    ->where('i.id', $hostLink['image_id'])
                    ->find();
            if(!empty($image)){
                if($image['image_group_name'] == 'Windows'){
                    $data['username'] = 'administrator';
                }else{
                    $data['username'] = 'root';
                }
            }else{
                $data['username'] = '';
            }
            $data['password'] = aes_password_decode($hostLink['password']);

            $dataCenter = DataCenterModel::find($configData['data_center']['id']);
            if(empty($dataCenter)){
                $dataCenter = $configData['data_center'];
            }
            $data['data_center'] = [
                'id' => $dataCenter['id'],
                'city' => $dataCenter['city'],
                'area' => $dataCenter['area'],
            ];
            $country = CountryModel::find($dataCenter['country_id']);

            $language = get_client_lang();
            $countryField = ['en-us'=> 'nicename'];
            $countryName = $countryField[ $language ] ?? 'name_zh';

            $data['data_center']['country'] = $country[ $countryName ];
            $data['data_center']['iso'] = $country['iso'];
            
            $data['image'] = $image ?? (object)[];

            // 当是VPC时,获取当前网络信息
            if($hostLink['vpc_network_id']>0){
                $data['vpc_network'] = VpcNetworkModel::field('id,name')->find($hostLink['vpc_network_id']) ?? (object)[];
            }
            cache('MODULE_MF_CLOUD_DETAIL_'.$hostId, $data, 3600);

            unset($data['client_id']);

            if($hostLink['ssh_key_id']>0){
                // ssh密钥
                $enableIdcsmartSshKeyAddon = PluginModel::where('name', 'IdcsmartSshKey')->where('module', 'addon')->where('status',1)->find();
                if(!empty($enableIdcsmartSshKeyAddon)){
                    $sshKey = IdcsmartSshKeyModel::find($hostLink['ssh_key_id']);
                    
                    $data['ssh_key'] = [
                        'id' => $hostLink['ssh_key_id'],
                        'name' => $sshKey['name'] ?? '',
                    ];
                }
            }else{
                $data['ssh_key'] = [
                    'id' => 0,
                    'name' => '',
                ];
            }
            $data['nat_acl_limit'] = $configData['nat_acl_limit'] ?? 0;
            $data['nat_web_limit'] = $configData['nat_web_limit'] ?? 0;
            $data['nat_acl_limit'] = (int)$data['nat_acl_limit'];
            $data['nat_web_limit'] = (int)$data['nat_web_limit'];

            $data['config'] = ConfigModel::field('reinstall_sms_verify,reset_password_sms_verify')->where('product_id', $host['product_id'])->find() ?? (object)[];
        }
        // 安全组不放入缓存
        $securityGroupId = 0;
        try{
            if(class_exists('addon\idcsmart_cloud\model\IdcsmartSecurityGroupHostLinkModel')){
                $addon = PluginModel::where('name', 'IdcsmartCloud')->where('module', 'addon')->where('status',1)->find();
                if($addon){
                    $IdcsmartSecurityGroupHostLinkModel = new IdcsmartSecurityGroupHostLinkModel();
                    $securityGroupId = IdcsmartSecurityGroupHostLinkModel::where('host_id', $hostId)->value('addon_idcsmart_security_group_id');
                    if(!empty($securityGroupId)){
                        $IdcsmartSecurityGroupModel = IdcsmartSecurityGroupModel::find($securityGroupId);
                    }
                }
            }
        }catch(\Exception $e){
            //$securityGroupId = 0;
        }
        if(!empty($securityGroupId)){
            $data['security_group'] = [
                'id'=>$securityGroupId,
                'name'=>$IdcsmartSecurityGroupModel['name'] ?? '',
            ];
        }else{
            $data['security_group'] = [
                'id'=>0,
                'name'=>'',
            ];
        }
        $res['data'] = $data;

        return $res;
    }

    /**
     * 时间 2023-02-27
     * @title
     * @desc
     * @url
     * @method  POST
     * @author hh
     * @version v1
     * @param   string x       -             x
     * @param   [type] $hostId [description]
     * @return  [type]         [description]
     */
    public function detailPart($hostId){
        $res = [
            'status'=>200,
            'msg'=>lang_plugins('success_message'),
            'data'=>(object)[]
        ];

        $data = [];
        // 先不弄缓存试试
        $cache = '';//cache('MODULE_MF_CLOUD_DETAIL_'.$hostId);
        if(!empty($cache)){
            if(app('http')->getName() == 'home' && $cache['client_id'] != get_client_id()){
                return $res;
            }
            $data = [
                'data_center' => $cache['data_center'],
                'ip' => $cache['ip'],
                'power_status' => $cache['power_status'],
                'image' => $cache['image'],
            ];
        }else{
            $host = HostModel::find($hostId);
            if(empty($host)){
                return $res;
            }
            if(app('http')->getName() == 'home' && $host['client_id'] != get_client_id()){
                return $res;
            }

            $hostLink = $this->where('host_id', $hostId)->find();
            $configData = json_decode($hostLink['config_data'], true);

            $dataCenter = DataCenterModel::find($configData['data_center']['id']);
            if(empty($dataCenter)){
                $dataCenter = $configData['data_center'];
            }
            $data['data_center'] = [
                'id' => $dataCenter['id'],
                'city' => $dataCenter['city'],
                'area' => $dataCenter['area'],
            ];
            $country = CountryModel::find($dataCenter['country_id']);

            $language = get_client_lang();
            $countryField = ['en-us'=> 'nicename'];
            $countryName = $countryField[ $language ] ?? 'name_zh';

            $data['data_center']['country'] = $country[ $countryName ];
            $data['data_center']['iso'] = $country['iso'];

            $data['ip'] = $hostLink['ip'];
            $data['power_status'] = $hostLink['power_status'];
            
            $image = ImageModel::alias('i')
                    ->field('i.id,i.name,ig.name image_group_name,ig.icon')
                    ->leftJoin('module_mf_cloud_image_group ig', 'i.image_group_id=ig.id')
                    ->where('i.id', $hostLink['image_id'])
                    ->find();
            $data['image'] = $image ?? (object)[];
        }
        $res['data'] = $data;
        return $res;
    }

    /* 模块定义操作 */

    /**
     * 时间 2023-02-09
     * @title 模块开通
     * @desc 模块开通
     * @author hh
     * @version v1
     * @param   ServerModel $param.server - ServerModel实例
     * @param   HostModel $param.host - HostModel实例
     * @param   ProductModel $param.product - ProductModel实例
     */
    public function createAccount($param){
        $productId = $param['product']['id'];
        $IdcsmartCloud = new IdcsmartCloud($param['server']);

        $serverHash = ToolLogic::formatParam($param['server']['hash']);
        $isAgent = isset($serverHash['account_type']) && $serverHash['account_type'] == 'agent';

        // 获取当前配置
        $hostLink = $this->where('host_id', $param['host']['id'])->find();
        if(!empty($hostLink) && $hostLink['rel_id'] > 0){
            return ['status'=>400, 'msg'=>lang_plugins('host_already_created')];
        }
        $configData = json_decode($hostLink['config_data'], true);

        // 开通参数
        $post = [];
        // $post['hostname'] = $param['host']['name'];

        // 定义用户参数
        $prefix = $serverHash['user_prefix'] ?? ''; // 用户前缀接口hash里面
        $username = $prefix.$param['client']['id'];
        
        $userData = [
            'username'=>$username,
            'email'=>$param['client']['email'] ?: '',
            'status'=>1,
            'real_name'=>$param['client']['username'] ?: '',
            'password'=>rand_str()
        ];
        if($isAgent){
            if(isset($configData['resource_package']['rid'])){
                $userData['rid'] = $configData['resource_package']['rid'];
            }else{
                // 创建的时候没选择直接默认后台创建的一个
                $userData['rid'] = ResourcePackageModel::where('product_id', $productId)->value('rid');
            }
            if(empty($userData['rid'])){
                $result['status']   = 400;
                $result['msg']      = lang_plugins('mf_cloud_resource_package_id_error');
                return $result;
            }
            $post['rid'] = $userData['rid'];
        }

        $IdcsmartCloud->userCreate($userData);
        $userCheck = $IdcsmartCloud->userCheck($username);
        if($userCheck['status'] != 200){
            return $userCheck;
        }
        $post['client'] = $userCheck['data']['id'];

        $dataCenter = DataCenterModel::find($hostLink['data_center_id']);
        if(!empty($dataCenter)){
            $post[ $dataCenter['cloud_config'] ] = $dataCenter['cloud_config_id'];
        }else{
            $post[ $configData['data_center']['cloud_config'] ] = $configData['data_center']['cloud_config_id'];
        }
        // 获取所有设置
        $ConfigModel = new ConfigModel();
        $config = $ConfigModel->indexConfig(['product_id'=>$param['product']['id']]);
        $config = $config['data'];

        // 匹配当前配置有高级配置的是否存在
        // 当前CPU配置是否存在
        $optionCpu = OptionModel::where('product_id', $productId)->where('rel_type', OptionModel::CPU)->where('value', $configData['cpu']['value'])->find();
        if(!empty($optionCpu)){
            $configData['cpu']['other_config'] = json_decode($optionCpu['other_config'], true);
        }
        // 匹配线路
        $line = LineModel::find($configData['line']['id']);
        if(!empty($line)){
            $configData['line'] = $line->toArray();
        }
        // 线路带宽
        if($configData['line']['bill_type'] == 'bw' && isset($configData['bw'])){
            $optionBw = OptionModel::where('product_id', $productId)->where('rel_type', OptionModel::LINE_BW)->where('rel_id', $configData['line']['id'])->where(function($query) use ($configData) {
                $query->whereOr('value', $configData['bw']['value'])
                      ->whereOr('(min_value<='.$configData['bw']['value'].' AND max_value>='.$configData['bw']['value'].')');
            })->find();
            if(!empty($optionBw)){
                $configData['bw']['other_config'] = json_decode($optionBw['other_config'], true);
            }
        }

        $post['type'] = isset($config['type']) && !empty($config['type']) ? $config['type'] : 'host';
        $post['link_clone'] = $configData['line']['link_clone'] ?? 0;
        $post['node_priority'] = $config['node_priority'];
        $post['bind_mac'] = $configData['ip_mac_bind'] ?? 0;
        $post['cpu_model'] = $config['cpu_model'];
        $post['niccard'] = $config['niccard'] ?: null;
        if(!empty($config['rand_ssh_port'])){
            $post['port'] = mt_rand(100, 65535);
        }
        if($config['type'] == 'hyperv'){
            $post['bind_mac'] = 1;
        }
        // 网络类型
        $post['network_type'] = $configData['network_type'] ?? 'normal';
        $post['cpu'] = $configData['cpu']['value'];
        $post['advanced_cpu'] = $configData['cpu']['other_config']['advanced_cpu'] ?? 0;
        if(is_numeric($configData['cpu']['other_config']['cpu_limit'])){
            $post['cpu_limit'] = $configData['cpu']['other_config']['cpu_limit'];
        }
        if($post['network_type'] == 'normal' && isset($configData['ipv6_num'])){
            $post['ipv6_num'] = $configData['ipv6_num']; //$configData['cpu']['other_config']['ipv6_num'];
        }
        if(isset($configData['memory_unit']) && $configData['memory_unit'] == 'MB'){
            $post['memory'] = $configData['memory']['value'];
        }else{
            $post['memory'] = $configData['memory']['value'] * 1024;
        }
        $post['system_disk_size'] = $configData['system_disk']['value'];
        $post['store'] = $configData['system_disk']['other_config']['store_id'] ?? '';

        $support_nat = ($post['type'] == 'lightHost' || $post['network_type'] == 'vpc') && (isset($configData['nat_acl_limit']) || isset($configData['nat_web_limit']));
        if($support_nat){
            $post['nat_acl_limit'] = $configData['nat_acl_limit'] ?? -1;
            $post['nat_web_limit'] = $configData['nat_web_limit'] ?? -1;
        }
        if($config['disk_limit_enable'] == 1){
            // 获取磁盘限制
            $diskLimit = DiskLimitModel::where('product_id', $param['product']['id'])
                        ->where('type', DiskLimitModel::SYSTEM_DISK)
                        ->where('min_value', '<=', $post['system_disk_size'])
                        ->where('max_value', '>=', $post['system_disk_size'])
                        ->find();
            if(!empty($diskLimit)){
                if($config['type'] == 'hyperv'){
                    $post['system_iops_min']   = $diskLimit['read_iops'];
                    $post['system_iops_max']  = $diskLimit['write_iops'];
                }else{
                    $post['system_read_bytes_sec']  = $diskLimit['read_bytes'];
                    $post['system_write_bytes_sec'] = $diskLimit['write_bytes'];
                    $post['system_read_iops_sec']   = $diskLimit['read_iops'];
                    $post['system_write_iops_sec']  = $diskLimit['write_iops'];
                }
            }
        }
        // 是否有免费磁盘
        if($config['free_disk_switch'] == 1 && $config['free_disk_size'] > 0){
            $diskLimit = DiskLimitModel::where('product_id', $param['product']['id'])
                        ->where('type', DiskLimitModel::DATA_DISK)
                        ->where('min_value', '<=', $config['free_disk_size'])
                        ->where('max_value', '>=', $config['free_disk_size'])
                        ->find();
            if(!empty($diskLimit)){
                if($config['type'] == 'hyperv'){
                    $post['other_data_disk'][] = [
                        'size'              => $config['free_disk_size'],
                        'iops_min'          => $diskLimit['read_iops'],
                        'iops_max'          => $diskLimit['write_iops'],
                        'store'             => '',
                    ];
                }else{
                    $post['other_data_disk'][] = [
                        'size'              => $config['free_disk_size'],
                        'read_bytes_sec'    => $diskLimit['read_bytes'],
                        'write_bytes_sec'   => $diskLimit['write_bytes'],
                        'read_iops_sec'     => $diskLimit['read_iops'],
                        'write_iops_sec'    => $diskLimit['write_iops'],
                        'store'             => '',
                    ];
                }
            }else{
                $post['other_data_disk'][] = [
                    'size'  => $config['free_disk_size'],
                    'store' => '',
                ];
            }
        }
        if(isset($configData['data_disk'])){
            foreach($configData['data_disk'] as $v){
                // 获取磁盘限制
                if($config['disk_limit_enable'] == 1){
                    $diskLimit = DiskLimitModel::where('product_id', $param['product']['id'])
                                ->where('type', DiskLimitModel::DATA_DISK)
                                ->where('min_value', '<=', $v['value'])
                                ->where('max_value', '>=', $v['value'])
                                ->find();
                    if(!empty($diskLimit)){
                        if($config['type'] == 'hyperv'){
                            $post['other_data_disk'][] = [
                                'size'              => $v['value'],
                                'iops_min'          => $diskLimit['read_iops'],
                                'iops_max'          => $diskLimit['write_iops'],
                                'store'             => $v['other_config']['store_id'] ?? '',
                            ];
                        }else{
                            $post['other_data_disk'][] = [
                                'size'              => $v['value'],
                                'read_bytes_sec'    => $diskLimit['read_bytes'],
                                'write_bytes_sec'   => $diskLimit['write_bytes'],
                                'read_iops_sec'     => $diskLimit['read_iops'],
                                'write_iops_sec'    => $diskLimit['write_iops'],
                                'store'             => $v['other_config']['store_id'] ?? '',
                            ];
                        }
                    }else{
                        $post['other_data_disk'][] = [
                            'size'  => $v['value'],
                            'store' => $v['other_config']['store_id'] ?? '',
                        ];
                    }
                }else{
                    $post['other_data_disk'][] = [
                        'size'  => $v['value'],
                        'store' => $v['other_config']['store_id'] ?? '',
                    ];
                }
            }
        }
        // 转发建站的实例默认不要IP
        if($support_nat){
            $ipNum = 0;
        }else{
            // 默认有一个IP数量
            $ipNum = 1;
        }
        if(isset($configData['line']) && !empty($configData['line'])){
            if($configData['line']['bill_type'] == 'bw'){
                $post['in_bw'] = $configData['bw']['value'] ?? 0;
                $post['out_bw'] = $configData['bw']['value'] ?? 0;

                if(is_numeric($configData['bw']['other_config']['in_bw'])){
                    $post['in_bw'] = $configData['bw']['other_config']['in_bw'];
                }
                $post['advanced_bw'] = $configData['bw']['other_config']['advanced_bw'] ?? 0;
            }else{
                // 流量方式开通
                $post['in_bw'] = $configData['flow']['other_config']['in_bw'];
                $post['out_bw'] = $configData['flow']['other_config']['out_bw'];

                $post['traffic_quota'] = $configData['flow']['value'];
                $post['traffic_type'] = $configData['flow']['other_config']['traffic_type'];

                if($configData['flow']['other_config']['bill_cycle'] == 'month'){
                    $post['reset_flow_day'] = 1;
                }else{
                    $post['reset_flow_day'] = date('j');
                }
            }
            $post['ip_group'] = (int)$configData['line']['bw_ip_group'];
            // IP分组
            if($configData['line']['defence_enable'] && !empty($configData['line']['defence_ip_group']) && isset($configData['defence']['value'])){
                $post['ip_group'] = $configData['line']['defence_ip_group'];
            }
            // 附加IP创建后添加为弹性IP并附加?弹性IP不行在弄成普通IP,弄成普通IP
            if($configData['line']['ip_enable'] && isset($configData['ip']['value'])){
                $ipNum += $configData['ip']['value'];
            }
        }
        // 默认自带一个IP
        $post['ip_num'] = $ipNum;

        // 是否有安全组,判断插件
        $addon = PluginModel::where('name', 'IdcsmartCloud')->where('module', 'addon')->where('status',1)->find();
        if(!empty($addon) && class_exists('addon\idcsmart_cloud\model\IdcsmartSecurityGroupHostLinkModel')){
            $securityGroupHostLink = IdcsmartSecurityGroupHostLinkModel::where('host_id', $param['host']['id'])->find();
            if(!empty($securityGroupHostLink)){
                $securityGroupLink = IdcsmartSecurityGroupLinkModel::where('addon_idcsmart_security_group_id', $securityGroupHostLink['addon_idcsmart_security_group_id'])
                                    ->where('server_id', $param['server']['id'])
                                    ->where('type', $post['type'])
                                    ->find();
                if(!empty($securityGroupLink)){
                    $post['security_group'] = $securityGroupLink['security_id'];
                }else{
                    // 获取安全组数据
                    $securityGroup = IdcsmartSecurityGroupModel::find($securityGroupHostLink['addon_idcsmart_security_group_id']);
                    if(empty($securityGroup)){
                        return ['status'=>400, 'msg'=>lang_plugins('security_group_not_found')];
                    }
                    // 自动创建安全组
                    $securityGroupData = [
                        'name'                  => 'security-'.rand_str(12),
                        'description'           => $securityGroup['name'],
                        'uid'                   => $post['client'],
                        'type'                  => $post['type'],
                        'create_default_rule'   => 0,   // 不创建默认规则
                    ];
                    if($isAgent){
                        $securityGroupData['rid'] = $post['rid'];
                    }
                    $securityGroupCreateRes = $IdcsmartCloud->securityGroupCreate($securityGroupData);
                    if($securityGroupCreateRes['status'] != 200){
                        return $securityGroupCreateRes;
                    }
                    if(!isset($securityGroupCreateRes['data']['id'])){
                        return ['status'=>400, 'msg'=>lang_plugins('mf_cloud_cannot_create_security_group')];
                    }
                    $post['security_group'] = $securityGroupCreateRes['data']['id'];
                    // 保存关联
                    $IdcsmartSecurityGroupLinkModel = new IdcsmartSecurityGroupLinkModel();
                    $IdcsmartSecurityGroupLinkModel->saveSecurityGroupLink([
                        'addon_idcsmart_security_group_id'  => $securityGroupHostLink['addon_idcsmart_security_group_id'],
                        'server_id'                         => $param['server']['id'],
                        'security_id'                       => $securityGroupCreateRes['data']['id'],
                        'type'                              => $post['type'],
                    ]);
                    // 创建规则
                    $IdcsmartSecurityGroupRuleLinkModel = new IdcsmartSecurityGroupRuleLinkModel();
                    $securityGroupRule = IdcsmartSecurityGroupRuleModel::where('addon_idcsmart_security_group_id', $securityGroupHostLink['addon_idcsmart_security_group_id'])->select()->toArray();
                    foreach($securityGroupRule as $v){
                        $ruleId = $v['id'];
                        unset($v['id'], $v['lock']);
                        $v = IdcsmartSecurityGroupRuleModel::transRule($v);

                        $securityGroupRuleCreateRes = $IdcsmartCloud->securityGroupRuleCreate($securityGroupCreateRes['data']['id'], $v);
                        if($securityGroupRuleCreateRes['status'] == 200){
                            $IdcsmartSecurityGroupRuleLinkModel->saveSecurityGroupRuleLink([
                                'addon_idcsmart_security_group_rule_id' => $ruleId,
                                'server_id'                             => $param['server']['id'],
                                'security_rule_id'                      => $securityGroupRuleCreateRes['data']['id'] ?? 0,
                                'type'                                  => $post['type'],
                            ]);
                        }
                    }
                    // 轻量版添加一条拒绝所有
                    if($post['type'] == 'lightHost'){
                        $IdcsmartCloud->securityGroupRuleCreate($securityGroupCreateRes['data']['id'], [
                            'description'   => lang_plugins('mf_cloud_deny_all'),
                            'direction'     => 'in',
                            'protocol'      => 'all',
                            'lock'          => 1,
                            'start_ip'      => '0.0.0.0',
                            'end_ip'        => '0.0.0.0',
                            'start_port'    => 1,
                            'end_port'      => 65535,
                            'priority'      => 1000,
                            'action'        => 'drop',
                        ]);
                    }
                }
            }
        }
        if($hostLink['backup_num']>0){
            $post['backup_num'] = $hostLink['backup_num'];
        }else{
            $post['backup_num'] = -1;
        }
        if($hostLink['snap_num']>0){
            $post['snap_num'] = $hostLink['snap_num'];
        }else{
            $post['snap_num'] = -1;
        }
        
        // 以镜像方式创建暂时,以后加入其他方式
        $image = ImageModel::find($hostLink['image_id']);
        if(!empty($image)){
            if($image['charge'] == 1 && !empty($image['price'])){
                $HostImageLinkModel = new HostImageLinkModel();
                $HostImageLinkModel->saveLink($param['host']['id'], $image['id']);
            }
        }else{
            $image = $configData['image'] ?? [];
        }
        if(empty($image)){
            return ['status'=>400, 'msg'=>lang_plugins('image_not_found')];
        }

        $post['os'] = $image['rel_image_id'];

        // 是否使用了SSH key
        if(!empty($hostLink['ssh_key_id'])){
            $enableIdcsmartSshKeyAddon = PluginModel::where('name', 'IdcsmartSshKey')->where('module', 'addon')->where('status',1)->find();
            if(empty($enableIdcsmartSshKeyAddon)){
                return ['status'=>400, 'msg'=>lang_plugins('mf_cloud_disable_ssh_key_addon')];
            }
            $sshKey = IdcsmartSshKeyModel::find($hostLink['ssh_key_id']);
            if(empty($sshKey)){
                return ['status'=>400, 'msg'=>lang_plugins('ssh_key_not_found')];
            }
            $sshKeyRes = $IdcsmartCloud->sshKeyCreate([
                'type' => 1,
                'uid'  => $post['client'],
                'name' => 'skey_'.rand_str(),
                'public_key'=>$sshKey['public_key'],
            ]);
            if($sshKeyRes['status'] != 200){
                return ['status'=>400, 'msg'=>$sshKeyRes['msg'] ?? lang_plugins('ssh_key_create_failed')];
            }
            $post['ssh_key'] = $sshKeyRes['data']['id'];
            $post['password_type'] = 1;
        }else{
            $post['password_type'] = 0;
            $post['rootpass'] = aes_password_decode($hostLink['password']);
        }
        $post['num'] = 1;

        // VPC
        if($post['network_type'] == 'vpc' && !$support_nat){
            // 获取当前VPC网络
            $vpcNetwork = VpcNetworkModel::find($hostLink['vpc_network_id']);
            if(!empty($vpcNetwork)){
                // 检查下VPC在魔方云是否还存在
                if(!empty($vpcNetwork['rel_id'])){
                    $remoteVpc = $IdcsmartCloud->vpcNetworkDetail($vpcNetwork['rel_id']);
                    if($remoteVpc['status'] == 200){
                        $post['vpc'] = $vpcNetwork['rel_id'];
                    }else{
                        // 批量开通并发是否有问题? 找不到了
                        $post['vpc_name'] = $vpcNetwork['vpc_name'];
                        $post['vpc_ips'] = $vpcNetwork['ips'];
                    }
                }else{
                    $post['vpc_name'] = $vpcNetwork['vpc_name'];
                    $post['vpc_ips'] = $vpcNetwork['ips'];
                }
            }else{
                // 连自己关联的VPC都找不到,随机创建个
                $post['vpc_name'] = 'VPC-'.rand_str(8);
            }
        }
        $res = $IdcsmartCloud->cloudCreate($post);
        if($res['status'] == 200){
            $result = [
                'status'=>200,
                'msg'   =>lang_plugins('host_create_success')
            ];

            $update = [];
            $update['rel_id'] = $res['data']['id'];
            $update['power_status'] = 'on';

            // 获取详情同步信息
            $detail = $IdcsmartCloud->cloudDetail($res['data']['id']);
            if($detail['status'] == 200){
                $update['password'] = aes_password_encode($detail['data']['rootpassword']);
                $update['ip'] = $detail['data']['mainip'] ?? '';
                $update['type'] = $detail['data']['type'];

                // 保存VPCID
                if(!$support_nat && $post['network_type'] == 'vpc' && isset($detail['data']['network'][0]['vpc']) && $detail['data']['network'][0]['vpc']>0){
                    VpcNetworkModel::where('id', $hostLink['vpc_network_id'])->update(['rel_id'=>$detail['data']['network'][0]['vpc'] ]);
                }

                // 获取默认转发
                if(empty($update['ip']) && $support_nat){
                    $natAclList = $IdcsmartCloud->natAclList($res['data']['id'], ['page'=>1, 'per_page'=>1]);
                    if(isset($natAclList['data']['data']) && !empty($natAclList['data']['data'])){
                        $update['ip'] = $natAclList['data']['nat_host_ip'] . ':' . $natAclList['data']['data'][0]['ext_port'];
                    }
                }
                
                HostModel::where('id', $param['host']['id'])->update(['name'=>$update['ip'] ?: $detail['data']['hostname']]);
            }
            $this->where('id', $hostLink['id'])->update($update);

            // 如果有免费盘放在config_data里面但是不保存
            if($config['free_disk_switch'] == 1 && $config['free_disk_size'] > 0){
                if(!isset($configData['data_disk'])) $configData['data_disk'] = [];

                array_unshift($configData['data_disk'], [
                    'value'         => $config['free_disk_size'],
                    'price'         => 0,
                    'is_free'       => 1,
                    'other_config'  => [
                        'disk_type' => ''
                    ],
                ]);
            }
            // 保存磁盘
            if(isset($configData['data_disk'])){
                $dataDisk = [];

                foreach($detail['data']['disk'] as $k=>$v){
                    if($v['type'] == 'system'){
                        continue;
                    }
                    $dataDisk[] = [
                        'name'          => $v['name'],
                        'size'          => $v['size'],
                        'rel_id'        => $v['id'],
                        'host_id'       => $param['host']['id'],
                        'create_time'   => time(),
                        'type'          => $configData['data_disk'][$k-1]['other_config']['disk_type'] ?? '',
                        'price'         => $configData['data_disk'][$k-1]['price'] ?? '0',
                        'is_free'       => $configData['data_disk'][$k-1]['is_free'] ?? 0,
                    ];
                }
                if(!empty($dataDisk)){
                    $DiskModel = new DiskModel();
                    $DiskModel->insertAll($dataDisk);
                }
            }
        }else{
            $result = [
                'status'=>400,
                'msg'=>$res['msg'] ?: lang_plugins('host_create_failed'),
            ];
            $this->where('id', $hostLink['id'])->update(['power_status'=>'fault']);
        }
        return $result;
    }

    /**
     * 时间 2023-02-09
     * @title 模块暂停
     * @desc 模块暂停
     * @author hh
     * @version v1
     */
    public function suspendAccount($param){
        $hostLink = $this->where('host_id', $param['host']['id'])->find();
        $id = $hostLink['rel_id'] ?? 0;
        if(empty($id)){
            return ['status'=>400, 'msg'=>lang_plugins('not_input_idcsmart_cloud_id')];
        }
        $IdcsmartCloud = new IdcsmartCloud($param['server']);
        $res = $IdcsmartCloud->cloudSuspend($id);
        if($res['status'] == 200){
            $result = [
                'status'=>200,
                'msg'=>lang_plugins('suspend_success'),
            ];
        }else{
            $result = [
                'status'=>400,
                'msg'=>lang_plugins('suspend_failed'),
            ];
        }
        return $result;
    }

    /**
     * 时间 2023-02-09
     * @title 模块解除暂停
     * @desc 模块解除暂停
     * @author hh
     * @version v1
     */
    public function unsuspendAccount($param){
        $hostLink = $this->where('host_id', $param['host']['id'])->find();
        $id = $hostLink['rel_id'] ?? 0;
        if(empty($id)){
            return ['status'=>400, 'msg'=>lang_plugins('not_input_idcsmart_cloud_id')];
        }
        $IdcsmartCloud = new IdcsmartCloud($param['server']);
        $res = $IdcsmartCloud->cloudUnsuspend($id);
        if($res['status'] == 200){
            $result = [
                'status'=>200,
                'msg'=>lang_plugins('unsuspend_success'),
            ];
        }else{
            $result = [
                'status'=>400,
                'msg'=>lang_plugins('unsuspend_failed'),
            ];
        }
        return $result;
    }

    /**
     * 时间 2023-02-09
     * @title 模块删除
     * @desc 模块删除
     * @author hh
     * @version v1
     */
    public function terminateAccount($param){
        $hostLink = $this->where('host_id', $param['host']['id'])->find();
        $id = $hostLink['rel_id'] ?? 0;
        if(empty($id)){
            $result = [
                'status'    => 200,
                'msg'       => lang_plugins('delete_success'),
            ];
            return $result;
            // return ['status'=>400, 'msg'=>lang_plugins('not_input_idcsmart_cloud_id')];
        }
        $IdcsmartCloud = new IdcsmartCloud($param['server']);
        $res = $IdcsmartCloud->cloudDelete($id);
        if($res['status'] == 200 || $res['http_code'] == 404){
            // 把磁盘数据保存到config_data
            $diskData = DiskModel::field('size,price,type')->where('host_id', $param['host']['id'])->where('is_free', 0)->select();

            $diskConfig = [];
            foreach($diskData as $v){
                $diskConfig[] = [
                    'value' => $v['size'],
                    'price' => $v['price'],
                    'other_config' => [
                        'disk_type' => $v['type'],
                    ],
                ];
            }
            $configData = json_decode($hostLink['config_data'], true);
            $configData['data_disk'] = $diskConfig;

            $update = [
                'rel_id'            => 0,
                'ip'                => '',
                'vpc_network_id'    => 0,
                'config_data'       => json_encode($configData),
            ];

            $this->where('host_id', $param['host']['id'])->update($update);
            DiskModel::where('host_id', $param['host']['id'])->delete();

            $notes = [
                '产品标识：'.$param['host']['name'],
                'IP地址：'.$hostLink['ip'],
                '操作系统：'.$configData['image']['name'],
                'ID：'.$hostLink['rel_id']
            ];
            HostModel::where('id', $param['host']['id'])->update(['notes'=>implode("\r\n", $notes)]);

            $result = [
                'status'=>200,
                'msg'=>lang_plugins('delete_success'),
            ];
        }else{
            $result = [
                'status'=>400,
                'msg'=>lang_plugins('delete_failed'),
            ];
        }
        return $result;
    }

    public function renew($param){
        $hostId = $param['host']['id'];
        $productId = $param['product']['id'];

        $hostLink = $this->where('host_id', $hostId)->find();
        if(!empty($hostLink)){
            $configData = json_decode($hostLink['config_data'], true);

            // 获取当前周期
            $duration = DurationModel::where('product_id', $productId)->where('name', $param['host']['billing_cycle_name'])->find();
            if(!empty($duration)){
                $configData['duration'] = $duration;

                $this->where('host_id', $hostId)->update(['config_data'=>json_encode($configData)]);
            }
        }
    }

    /**
     * 时间 2022-06-28
     * @title 升降级后调用
     * @author hh
     * @version v1
     */
    public function changePackage($param){
        // 判断是什么类型
        if(!isset($param['custom']['type'])){
            return ['status'=>400, 'msg'=>lang_plugins('param_error')];
        }
        $productId = $param['product']['id'];   // 商品ID
        $hostId    = $param['host']['id'];      // 产品ID
        $custom    = $param['custom'] ?? [];    // 升降级参数

        if($custom['type'] == 'buy_image'){
            // 购买镜像
            $HostImageLinkModel = new HostImageLinkModel();
            $HostImageLinkModel->saveLink($hostId, $custom['image_id']);
        }else if($custom['type'] == 'upgrade_common_config'){
            $hostLink = HostLinkModel::where('host_id', $hostId)->find();

            $configData = json_decode($hostLink['config_data'], true);
            $oldConfigData = $configData;
            $newConfigData = $custom['new_config_data'];
            foreach($newConfigData as $k=>$v){
                $configData[$k] = $v;
            }

            // 保存新的配置
            $update = [
                'config_data' => json_encode($configData),
            ];

            HostLinkModel::update($update, ['host_id'=>$hostId]);
            
            $id = $hostLink['rel_id'] ?? 0;
            if(empty($id)){
                $description = lang_plugins('mf_cloud_upgrade_config_error_for_no_rel_id');
                active_log($description, 'host', $hostId);
                return ['status'=>400, 'msg'=>lang_plugins('not_input_idcsmart_cloud_id')];
            }
            $IdcsmartCloud = new IdcsmartCloud($param['server']);

            // 要升级
            $post = [];
            // 要修改的带宽
            $bw = [];

            // cpu变更
            if(isset($newConfigData['cpu'])){
                $post['cpu'] = $newConfigData['cpu']['value'];
                $post['advanced_cpu'] = $newConfigData['cpu']['other_config']['advanced_cpu'] ?? null;
            }
            if(isset($newConfigData['memory'])){
                if(isset($newConfigData['memory_unit']) && $newConfigData['memory_unit'] == 'MB'){
                    $post['memory'] = $newConfigData['memory']['value'];
                }else{
                    $post['memory'] = $newConfigData['memory']['value']*1024;
                }
            }
            if(isset($newConfigData['bw'])){
                $bw['in_bw'] = $newConfigData['bw']['value'];
                $bw['out_bw'] = $newConfigData['bw']['value'];

                if(is_numeric($newConfigData['bw']['other_config']['in_bw'])){
                    $bw['in_bw'] = $newConfigData['bw']['other_config']['in_bw'];
                }
                $post['advanced_bw'] = $newConfigData['cpu']['other_config']['advanced_bw'] ?? null;
            }
            if(isset($newConfigData['flow'])){
                $post['traffic_quota'] = $newConfigData['flow']['value'];
                $post['traffic_type'] = $newConfigData['flow']['other_config']['traffic_type'];
                if($newConfigData['flow']['other_config']['bill_cycle'] == 'month'){
                    $post['reset_flow_day'] = 1;
                }else{
                    $post['reset_flow_day'] = date('j', $param['host']['active_time']);
                }

                $bw['in_bw'] = $newConfigData['flow']['other_config']['in_bw'];
                $bw['out_bw'] = $newConfigData['flow']['other_config']['out_bw'];
            }
            $description = [];

            $autoBoot = false;
            if(isset($newConfigData['cpu']) || isset($newConfigData['memory'])){
                $status = $IdcsmartCloud->cloudStatus($id);
                if($status['status'] == 200){
                    // 关机
                    if($status['data']['status'] == 'on' || $status['data']['status'] == 'task' || $status['data']['status'] == 'paused'){
                        $this->safeCloudOff($IdcsmartCloud, $id);
                        // $res = $IdcsmartCloud->cloudHardOff($id);
                        // // 检查任务
                        // for($i = 0; $i<40; $i++){
                        //     $detail = $IdcsmartCloud->taskDetail($res['data']['taskid']);
                        //     if(isset($detail['data']['status']) && $detail['data']['status'] > 1){
                        //         break;
                        //     }
                        //     sleep(10);
                        // }
                        $autoBoot = true;
                    }
                }
            }
            // 修改cpu限制
            if(isset($newConfigData['cpu']) && $oldConfigData['cpu']['other_config']['cpu_limit'] != $newConfigData['cpu']['other_config']['cpu_limit']){
                $res = $IdcsmartCloud->cloudModifyCpuLimit($id, $newConfigData['cpu']['other_config']['cpu_limit']);
                if($res['status'] != 200){
                    $description[] = lang_plugins('mf_cloud_upgrade_cpu_limit_fail') . $res['msg'];
                }else{
                    $description[] = lang_plugins('mf_cloud_upgrade_cpu_limit_success');
                }
            }
            // 修改IPv6数量
            // if(isset($newConfigData['cpu']) && $oldConfigData['cpu']['other_config']['ipv6_num'] != $newConfigData['cpu']['other_config']['ipv6_num']){
            //     $res = $IdcsmartCloud->cloudModifyIpv6($id, (int)$newConfigData['cpu']['other_config']['ipv6_num']);
            //     if($res['status'] != 200){
            //         $description[] = '修改IPv6数量失败,原因:'.$res['msg'];
            //     }else{
            //         $description[] = '修改IPv6数量成功';
            //     }
            // }
            if(!empty($bw)){
                $res = $IdcsmartCloud->cloudModifyBw($id, $bw);
                if($res['status'] != 200){
                    $description[] = lang_plugins('mf_cloud_upgrade_bw_fail') . $res['msg'];
                }else{
                    $description[] = lang_plugins('mf_cloud_upgrade_bw_success');
                }
            }
            $res = $IdcsmartCloud->cloudModify($id, $post);
            if($res['status'] != 200){
                $description[] = lang_plugins('mf_cloud_upgrade_common_config_fail') . $res['msg'];
            }else{
                $description[] = lang_plugins('mf_cloud_upgrade_common_config_success');
            }
            if($autoBoot){
                $IdcsmartCloud->cloudOn($id);
            }
            $description = lang_plugins('mf_cloud_upgrade_config_complete').implode(',', $description);
            active_log($description, 'host', $param['host']['id']);
        }else if($custom['type'] == 'buy_disk'){
            $hostLink = HostLinkModel::where('host_id', $hostId)->find();
            $id = $hostLink['rel_id'] ?? 0;

            $IdcsmartCloud = new IdcsmartCloud($param['server']);
            // 这里不用验证了
            $autoBoot = false;

            $delSuccess = [];
            $delFail = [];
            $addSuccess = [];
            $addFail = [];
            $storeId = 0;

            $description = [];
            if(!empty($custom['remove_disk_id'])){
                $status = $IdcsmartCloud->cloudStatus($id);
                if($status['status'] == 200){
                    // 关机
                    if($status['data']['status'] == 'on' || $status['data']['status'] == 'task' || $status['data']['status'] == 'paused'){
                        $this->safeCloudOff($IdcsmartCloud, $id);
                        // $res = $IdcsmartCloud->cloudHardOff($id);
                        // // 检查任务
                        // for($i = 0; $i<40; $i++){
                        //     $detail = $IdcsmartCloud->taskDetail($res['data']['taskid']);
                        //     if(isset($detail['data']['status']) && $detail['data']['status'] > 1){
                        //         break;
                        //     }
                        //     sleep(10);
                        // }
                        $autoBoot = true;
                    }
                }
                foreach($custom['remove_disk_id'] as $v){
                    $deleteRes = $IdcsmartCloud->diskDelete($v);
                    if($deleteRes['status'] == 200){
                        DiskModel::where('host_id', $hostId)->where('rel_id', $v)->delete();

                        $delSuccess[] = $v;
                    }else{
                        $delFail[] = $v.','.lang_plugins('mf_cloud_reason').':'.$deleteRes['msg'];
                    }
                }
                if(!empty($delSuccess)){
                    $description[] = lang_plugins('mf_cloud_cancel_order_disk_success') . implode(',', $delSuccess);
                }
                if(!empty($delFail)){
                    $description[] = lang_plugins('mf_cloud_cancel_order_disk_fail').implode(',', $delFail);
                }
            }
            if(!empty($custom['add_disk'])){
                // 查找当前可用存储
                if(empty($storeId)){
                    // 和系统盘一致
                    $detail = $IdcsmartCloud->cloudDetail($id);

                    if($detail['status'] == 200){
                        $storeId = $detail['data']['disk'][0]['store_id'] ?? 0;
                    }
                }
                foreach($custom['add_disk'] as $v){
                    $addRes = $IdcsmartCloud->addAndMountDisk($id, [
                        'size'=>$v['size'],
                        'store'=>$storeId,
                        'driver'=>'virtio',
                        'cache'=>'writeback',
                        'io'=>'native'
                    ]);
                    if($addRes['status'] != 200){
                        $addFail[] = $v['size'].','.lang_plugins('mf_cloud_reason').':'.$addRes['msg'];
                    }else{
                        // $diskLimit = DiskLimitModel::where('product_id', $productId)->where('type', DiskLimitModel::DATA_DISK)->where('min_value', '<=', $v['size'])->where('max_value', '>=', $v['size'])->find();
                        // if(!empty($diskLimit)){
                        //     // 修改磁盘限制
                        // }
                        $addSuccess[] = $v['size'];

                        DiskModel::create([
                            'name' => '',
                            'size' => $v['size'],
                            'rel_id' => $addRes['data']['diskid'] ?? 0,
                            'host_id' => $hostId,
                            'create_time' => time(),
                            'type' => $v['type'],
                            'price' => $v['price'] ?? 0,
                        ]);
                    }
                }
                if(!empty($addSuccess)){
                    $description[] = lang_plugins('mf_cloud_buy_disk_success') . implode(',', $addSuccess);
                }
                if(!empty($addFail)){
                    $description[] = lang_plugins('mf_cloud_buy_disk_fail') . implode(',', $addFail);
                }
            }
            if($autoBoot){
                $IdcsmartCloud->cloudOn($id);
            }
            // 重新获取磁盘列表
            // $res = $IdcsmartCloud->cloudDetail($id);
            // if($res['status'] == 200 && isset($res['data']['disk'])){
            //     $disk = $res['data']['disk'];

            //     $dataDisk = [];
            //     foreach($disk as $v){
            //         if($v['type'] == 'data' && $v['id'] != $hostLink['free_disk_id']){
            //             $dataDisk[] = $v['size'];
            //         }
            //     }
            //     HostLinkModel::update(['data_disk_size'=>json_encode($dataDisk)], ['host_id'=>$param['host']['id']]);
            // }
            $description = lang_plugins('mf_cloud_upgrade_disk_complete') . implode(',', $description);
            active_log($description, 'host', $hostId);
        }else if($param['custom']['type'] == 'resize_disk'){
            $custom = $param['custom'];

            $hostLink = HostLinkModel::where('host_id', $param['host']['id'])->find();
            $id = $hostLink['rel_id'] ?? 0;

            $IdcsmartCloud = new IdcsmartCloud($param['server']);

            // 直接关机扩容
            $autoBoot = false;
            $status = $IdcsmartCloud->cloudStatus($id);
            if($status['status'] == 200){
                // 关机
                if($status['data']['status'] == 'on' || $status['data']['status'] == 'task' || $status['data']['status'] == 'paused'){
                    $this->safeCloudOff($IdcsmartCloud, $id);
                    // $res = $IdcsmartCloud->cloudHardOff($id);
                    // // 检查任务
                    // for($i = 0; $i<40; $i++){
                    //     $detail = $IdcsmartCloud->taskDetail($res['data']['taskid']);
                    //     if(isset($detail['data']['status']) && $detail['data']['status'] > 1){
                    //         break;
                    //     }
                    //     sleep(10);
                    // }
                    $autoBoot = true;
                }
            }

            $success = [];
            $fail = [];
            $description = [];

            foreach($custom['resize_disk'] as $v){
                $resizeRes = $IdcsmartCloud->diskModify($v['id'], ['size'=>$v['size']]);
                if($resizeRes['status'] == 200){
                    $success[] = $v['id'];
                }else{
                    $fail[] = lang_plugins('mf_cloud_disk') . 'ID:'.$v['id'].','.lang_plugins('mf_cloud_reason').':'.$resizeRes['msg'];
                }
                // 成功失败都修改
                DiskModel::where('host_id', $hostId)->where('rel_id', $v['id'])->update(['size'=>$v['size'], 'price'=>$v['price'] ]);
            }
            if($autoBoot){
                $IdcsmartCloud->cloudOn($id);
            }
            // 重新获取磁盘列表
            // $res = $IdcsmartCloud->cloudDetail($this->id);
            // if($res['status'] == 200 && isset($res['data']['disk'])){
            //     $disk = $res['data']['disk'];

            //     $dataDisk = [];
            //     foreach($disk as $v){
            //         if($v['type'] == 'data' && $v['id'] != $hostLink['free_disk_id']){
            //             $dataDisk[] = $v['size'];
            //         }
            //     }
            //     HostLinkModel::update(['data_disk_size'=>json_encode($dataDisk)], ['host_id'=>$param['host']['id']]);
            // }

            if(!empty($success)){
                $description[] = lang_plugins('mf_cloud_upgrade_resize_disk_success') . implode(',', $success);
            }
            if(!empty($fail)){
                $description[] = lang_plugins('mf_cloud_upgrade_resize_disk_fail') . implode(',', $fail);
            }
            $description = lang_plugins('mf_cloud_upgrade_resize_disk_complete') . implode(',', $description);
            active_log($description, 'host', $param['host']['id']);
        }else if($custom['type'] == 'modify_backup'){
            $hostLink = $this->where('host_id', $hostId)->find();
            $id = $hostLink['rel_id'] ?? 0;
            $IdcsmartCloud = new IdcsmartCloud($param['server']);

            $update = [ $custom['backup_type'].'_num'=>$custom['num'] ];

            $type = ['backup'=>lang_plugins('backup'), 'snap'=>lang_plugins('snap')];

            $configData = json_decode($hostLink['config_data'], true);
            $configData[ $custom['backup_type'] ] = $custom['backup_config'];
            $update['config_data'] = json_encode($configData);

            HostLinkModel::update($update, ['host_id'=>$param['host']['id']]);
            $res = $IdcsmartCloud->cloudModify($hostLink['rel_id'], $update);
            if($res['status'] == 200){
                $description = lang_plugins('log_mf_cloud_upgrade_backup_num_success', [
                    '{type}' => $type[$custom['backup_type']],
                    '{num}'  => $custom['num'],
                ]);
            }else{
                $description = lang_plugins('log_mf_cloud_upgrade_backup_num_fail', [
                    '{type}'    => $type[$custom['backup_type']],
                    '{num}'     => $custom['num'],
                    '{reason}'  => $res['msg'],
                ]);
            }
            active_log($description, 'host', $hostId);
        }else if($custom['type'] == 'upgrade_ip_num'){
            // 升级IP数量
            $hostLink = $this->where('host_id', $hostId)->find();
            $id = $hostLink['rel_id'] ?? 0;

            // 直接保存configData
            $configData = json_decode($hostLink['config_data'], true);
            $configData['ip'] = $custom['ip_data'];

            $this->where('id', $hostLink['id'])->update(['config_data'=>json_encode($configData)]);

            $ipGroup = 0;
            // 获取下线路信息
            $line = LineModel::find($configData['line']['id']);
            if(!empty($line)){
                if($line['defence_enable'] == 1 && isset($configData['defence']['value']) && !empty($configData['defence']['value'])){
                    $ipGroup = $line['defence_ip_group'];
                }else{
                    $ipGroup = $line['bw_ip_group'];
                }
            }
            $supportNat = ($hostLink['type'] == 'lightHost' || $configData['network_type'] == 'vpc') && (isset($configData['nat_acl_limit']) || isset($configData['nat_web_limit']));
            $baseIpNum = 1;
            if($supportNat){
                $baseIpNum = 0;
            }

            $IdcsmartCloud = new IdcsmartCloud($param['server']);
            // 修改IP数量
            $res = $IdcsmartCloud->cloudModifyIpNum($id, ['num'=>$custom['ip_data']['value']+$baseIpNum, 'ip_group'=>$ipGroup ]);
            if($res['status'] == 200){
                $description = lang_plugins('log_mf_cloud_upgrade_ip_num_success');
            }else{
                $description = lang_plugins('log_mf_cloud_upgrade_ip_num_fail', [
                    '{reason}' => $res['msg'],
                ]);
            }
            active_log($description, 'host', $hostId);
        }
        $this->detail($hostId, false);

        return ['status'=>200];
    }

    /**
     * 时间 2023-02-09
     * @title 结算后
     * @desc 结算后
     * @author hh
     * @version v1
     * @param   [type] $param [description]
     * @return  [type]        [description]
     */
    public function afterSettle($param){
        // 参数不需要重新验证了,计算已经验证了
        $custom = $param['custom'] ?? [];
        $clientId = !empty(get_admin_id()) ? HostModel::where('id', $param['host_id'])->value('client_id') : get_client_id();
        $hostId = $param['host_id'];
        $time = time();
        
        $ConfigModel = new ConfigModel();
        $config = $ConfigModel->indexConfig(['product_id'=>$param['product']['id']]);
        $config = $config['data'];

        $configData = DurationModel::$configData;
        $configData['network_type'] = $custom['network_type'];

        $data = [
            'host_id'           => $param['host_id'],
            'data_center_id'    => $custom['data_center_id'] ?? 0,
            'image_id'          => $custom['image_id'],
            'backup_num'        => $configData['backup']['num'] ?? 0,
            'snap_num'          => $configData['snap']['num'] ?? 0,
            'power_status'      => 'on',
            'password'          => aes_password_encode($custom['password'] ?? ''),
            'config_data'       => json_encode($configData),
            'create_time'       => time(),
            'type'              => $config['type'],
        ];
        if(isset($custom['ssh_key_id']) && !empty($custom['ssh_key_id'])){
            $addon = PluginModel::where('name', 'IdcsmartSshKey')->where('module', 'addon')->where('status',1)->find();
            if(!empty($addon)){
                $sshKey = IdcsmartSshKeyModel::find($custom['ssh_key_id']);
                if(empty($sshKey) || $sshKey['client_id'] != $clientId){
                    throw new \Exception(lang_plugins('ssh_key_not_found'));
                }
                $data['ssh_key_id'] = $custom['ssh_key_id'];
                $data['password'] = aes_password_encode('');
            }else{
                throw new \Exception(lang_plugins('mf_cloud_not_support_ssh_key'));
            }
        }
        // 创建VPC的情况
        if($custom['network_type'] == 'vpc'){
            // 支持转发建站
            if(isset($configData['nat_acl_limit']) || isset($configData['nat_web_limit'])){

            }else{
                if(isset($custom['vpc']['id']) && !empty($custom['vpc']['id'])){
                    $vpcNetwork = VpcNetworkModel::find($custom['vpc']['id']);
                    if(empty($vpcNetwork) || $vpcNetwork['client_id'] != $clientId || $vpcNetwork['data_center_id'] != $custom['data_center_id']){
                        throw new \Exception(lang_plugins('vpc_network_not_found'));
                    }
                    $data['vpc_network_id'] = $custom['vpc']['id'];
                }else{
                    $vpcName = 'VPC-'.rand_str(8);

                    $vpcData = [
                        'product_id' => $param['product']['id'],
                        'data_center_id' => $custom['data_center_id'],
                        'name' => $vpcName,
                        'vpc_name' => $vpcName,
                        'ips' => isset($custom['vpc']['ips']) && !empty($custom['vpc']['ips']) ? $custom['vpc']['ips'] : '10.0.0.0/16',
                        'client_id' => $clientId,
                        'create_time' => time(),
                    ];

                    $request = request()->param();
                    if(request()->is_api && isset($request['downstream_client_id']) && !empty($request['downstream_client_id'])){
                        $vpcData['downstream_client_id'] = $request['downstream_client_id'];
                    }
                    $vpc = VpcNetworkModel::create($vpcData);
                    $data['vpc_network_id'] = $vpc->id;
                }
            }
        }
        $res = $this->where('host_id', $param['host_id'])->find();
        if(empty($res)){
            $this->create($data);
        }else{
            $this->update($data, ['host_id'=>$param['host_id']]);
        }
        $hostData = [
            'client_notes' => $custom['notes'] ?? '',
        ];
        HostModel::where('id', $param['host_id'])->update($hostData);
        $addon = PluginModel::where('name', 'IdcsmartCloud')->where('module', 'addon')->where('status',1)->find();
        if(!empty($addon)){
            if(isset($custom['security_group_id']) && !empty($custom['security_group_id'])){
                $securityGroup = IdcsmartSecurityGroupModel::find($custom['security_group_id']);
                if(empty($securityGroup) || $securityGroup['client_id'] != $clientId){
                    throw new \Exception(lang_plugins('mf_cloud_security_group_not_found'));
                }
                // 直接关联
                $res = IdcsmartSecurityGroupHostLinkModel::where('host_id', $param['host_id'])->find();
                if(empty($res)){
                    IdcsmartSecurityGroupHostLinkModel::create(['addon_idcsmart_security_group_id'=>$custom['security_group_id'], 'host_id'=>$param['host_id']]);
                }else{
                    IdcsmartSecurityGroupHostLinkModel::update(['addon_idcsmart_security_group_id'=>$custom['security_group_id']], ['host_id'=>$param['host_id']]);
                }
            }else if(isset($custom['security_group_protocol']) && !empty($custom['security_group_protocol'])){
                // 传了安全组规则过来
                $securityGroup = IdcsmartSecurityGroupModel::create([
                    'client_id'     => $clientId,
                    'type'          => 'host',
                    'name'          => 'security-'.rand_str(),
                    'create_time'   => $time,
                ]);
                
                $protocol = [
                    'icmp' => [
                        'port'          => '1-65535',
                        'description'   => lang_plugins('mf_cloud_ping_service_release'),
                        'direction'     => 'in',
                    ],
                    'ssh' => [
                        'port'          => '22',
                        'description'   => lang_plugins('mf_cloud_release_linux_ssh_login'),
                        'direction'     => 'in',
                    ],
                    'telnet' => [
                        'port'          => '23',
                        'description'   => lang_plugins('mf_cloud_release_service_telnet'),
                        'direction'     => 'in',
                    ],
                    'http' => [
                        'port'          => '80',
                        'description'   => lang_plugins('mf_cloud_release_http_protocol'),
                        'direction'     => 'in',
                    ],
                    'https' => [
                        'port'          => '443',
                        'description'   => lang_plugins('mf_cloud_release_https_protocol'),
                        'direction'     => 'in',
                    ],
                    'mssql' => [
                        'port'          => '1433',
                        'description'   => lang_plugins('mf_cloud_release_service_mssql'),
                        'direction'     => 'in',
                    ],
                    'oracle' => [
                        'port'          => '1521',
                        'description'   => lang_plugins('mf_cloud_release_service_oracle'),
                        'direction'     => 'in',
                    ],
                    'mysql' => [
                        'port'          => '3306',
                        'description'   => lang_plugins('mf_cloud_release_service_mysql'),
                        'direction'     => 'in',
                    ],
                    'rdp' => [
                        'port'          => '3389',
                        'description'   => lang_plugins('mf_cloud_release_service_windows'),
                        'direction'     => 'in',
                    ],
                    'postgresql' => [
                        'port'          => '5432',
                        'description'   => lang_plugins('mf_cloud_release_service_postgresql'),
                        'direction'     => 'in',
                    ],
                    'redis' => [
                        'port'          => '6379',
                        'description'   => lang_plugins('mf_cloud_release_service_redis'),
                        'direction'     => 'in',
                    ],
                    'all'   => [
                        'port'          => '1-65535',
                        'description'   => lang_plugins('mf_cloud_release_all_out_traffic'),
                        'direction'     => 'out',
                    ]
                ];

                $custom['security_group_protocol'] = array_unique($custom['security_group_protocol']);
                if(!in_array('all', $custom['security_group_protocol'])){
                    $custom['security_group_protocol'][] = 'all';
                }
                
                $securityGroupRule = [];

                foreach($custom['security_group_protocol'] as $v){
                    $securityGroupRule[] = [
                        'addon_idcsmart_security_group_id'      => $securityGroup->id,
                        'description'                           => $protocol[$v]['description'],
                        'direction'                             => $protocol[$v]['direction'],
                        'protocol'                              => $v,
                        'port'                                  => $protocol[$v]['port'],
                        'ip'                                    => '0.0.0.0/0',
                        'create_time'                           => $time,
                    ];
                }

                $IdcsmartSecurityGroupRuleModel = new IdcsmartSecurityGroupRuleModel();
                $IdcsmartSecurityGroupRuleModel->insertAll($securityGroupRule);

                IdcsmartSecurityGroupHostLinkModel::create([
                    'addon_idcsmart_security_group_id'  => $securityGroup->id,
                    'host_id' => $hostId,
                ]);
            }
        }
        // 自动续费
        if(isset($custom['auto_renew']) && $custom['auto_renew'] == 1){
            $enableIdcsmartRenewAddon = PluginModel::where('name', 'IdcsmartRenew')->where('module', 'addon')->where('status',1)->find();
            if($enableIdcsmartRenewAddon && class_exists('addon\idcsmart_renew\model\IdcsmartRenewAutoModel')){
                IdcsmartRenewAutoModel::where('host_id', $hostId)->delete();
                IdcsmartRenewAutoModel::create([
                    'host_id' => $hostId,
                    'status'  => 1,
                ]);
            }
        }
    }

    /**
     * 时间 2023-02-20
     * @title 获取当前配置所有周期价格
     * @desc 获取当前配置所有周期价格
     * @author hh
     * @version v1
     * @param   [type] $param [description]
     */
    public function durationPrice($param){
        $data = $this->getCurrentConfig($param);

        $DurationModel = new DurationModel();
        $result = $DurationModel->getAllDurationPrice($data, true);
        if($result['status'] == 400){
            // return $result;
            $result = [
                'status' => 200,
                'msg'    => lang_plugins('success_message'),
                'data'   => [],
            ];
        }else{
            foreach($result['data'] as $k=>$v){
                if(empty($v['num'])){
                    unset($result['data'][$k]);
                    continue;
                }
                $result['data'][$k]['duration'] = strtotime('+ '.$v['num'].' '.$v['unit'], $param['host']['due_time']) - $param['host']['due_time'];
                $result['data'][$k]['billing_cycle'] = $v['name'];
                $result['data'][$k]['price'] = amount_format($v['price']);
                unset($result['data'][$k]['name'], $result['data'][$k]['num'], $result['data'][$k]['unit'], $result['data'][$k]['discount']);
            }
            $result['data'] = array_values($result['data']);
        }
        return $result;
    }

    public function currentConfigOption($param){
        $hostId = $param['host']['id'];

        $hostLink = $this->where('host_id', $hostId)->find();
        $configData = json_decode($hostLink['config_data'], true);

        $data = [
            'cpu' => $configData['cpu']['value'],
            'memory' => $configData['memory']['value'],
            'system_disk' => [
                'size' => $configData['system_disk']['value'],
                'disk_type' => $configData['system_disk']['other_config']['disk_type'],
            ],
            'backup_num' => $hostLink['backup_num'],
            'snap_num' => $hostLink['snap_num'],
            'line_id' => $configData['line']['id'],
            'duration_id' => $configData['duration']['id'],
        ];
        // 获取所有数据盘
        $disk = DiskModel::where('host_id', $hostId)->where('is_free', 0)->select();
        if(!empty($disk)){
            $data['data_disk'] = [];
            foreach($disk as $v){
                $data['data_disk'][] = [
                    'size' => $v['size'],
                    'disk_type' => $v['type'],
                ];
            }
        }
        if($configData['line']['bill_type'] == 'bw'){
            $data['bw'] = $configData['bw']['value'];
        }else{
            $data['flow'] = $configData['flow']['value'];
        }
        if(isset($configData['defence'])){
            $data['peak_defence'] = $configData['defence']['value'];
        }
        if(isset($configData['ip'])){
            $data['ip_num'] = $configData['ip']['value'];
        }
        $result = [
            'status' => 200,
            'msg'    => lang_plugins('success_message'),
            'data'   => $data,
        ];
        return $result;
    }

    /**
     * 时间 2023-02-09
     * @title 获取商品最低价格周期
     * @desc 获取商品最低价格周期
     * @author hh
     * @version v1
     * @param   [type] $productId [description]
     * @return  [type]            [description]
     */
    public function getPriceCycle($productId){
        $ProductModel = ProductModel::find($productId);
        if(empty($ProductModel)){
            return false;
        }
        bcscale(2);

        $cycle = null;
        if($ProductModel['pay_type'] == 'free'){
            $price = 0;
        }else if($ProductModel['pay_type'] == 'onetime'){
            // 价格怎么算了?
            $price = 0;
        }else{
            // 获取cpu最低价格
            $option = OptionModel::alias('o')
                ->field('o.*,p.duration_id,p.price,d.price_factor')
                ->join('module_mf_cloud_price p', 'o.id=p.option_id')
                ->leftJoin('module_mf_cloud_duration d', 'p.duration_id=d.id')
                ->where('o.product_id', $productId)
                ->whereIn('o.rel_type', [OptionModel::CPU,OptionModel::MEMORY,OptionModel::LINE_BW,OptionModel::LINE_FLOW,OptionModel::SYSTEM_DISK])
                ->group('o.id,p.duration_id')
                ->order('p.price', 'asc')
                ->select()
                ->toArray();

            $minPrice = [];
            foreach($option as $v){
                if($v['type'] == 'radio'){
                    $price = $v['price'];
                }else if($v['type'] == 'step'){
                    $price = bcmul($v['min_value'], $v['price']);
                }else if($v['type'] == 'total'){
                    $price = bcmul($v['min_value'], $v['price']);
                }else{
                    $price = 0;
                }
                $price = bcmul($price, $v['price_factor'] ?? 1);

                if(!isset($minPrice[ $v['duration_id'] ][ $v['rel_type'] ])){
                    $minPrice[ $v['duration_id'] ][ $v['rel_type'] ] = $price;
                }else{
                    if($price < $minPrice[ $v['duration_id'] ][ $v['rel_type'] ]){
                        $minPrice[ $v['duration_id'] ][ $v['rel_type'] ] = $price;
                    }
                }
            }

            $price = null;
            $durationId = 0;
            foreach($minPrice as $k=>$v){
                $tmp = 0;
                if(isset($v[OptionModel::CPU])){
                    $tmp = bcadd($tmp, $v[OptionModel::CPU]);
                }else{
                    continue;
                }
                if(isset($v[OptionModel::MEMORY])){
                    $tmp = bcadd($tmp, $v[OptionModel::MEMORY]);
                }else{
                    continue;
                }
                if(isset($v[OptionModel::SYSTEM_DISK])){
                    $tmp = bcadd($tmp, $v[OptionModel::SYSTEM_DISK]);
                }else{
                    continue;
                }
                if(isset($v[OptionModel::LINE_BW]) && isset($v[OptionModel::LINE_FLOW])){
                    $tmp = bcadd($tmp, min($v[OptionModel::LINE_BW], $v[OptionModel::LINE_FLOW]));
                }else if(isset($v[OptionModel::LINE_BW])){
                    $tmp = bcadd($tmp, $v[OptionModel::LINE_BW]);
                }else if(isset($v[OptionModel::LINE_FLOW])){
                    $tmp = bcadd($tmp, $v[OptionModel::LINE_FLOW]);
                }
                if(is_null($price)){
                    $price = $tmp;
                    $durationId = $k;
                }else{
                    if($tmp < $price){
                        $price = $tmp;
                        $durationId = $k;
                    }
                }
            }

            $price = $price ?? 0;
            $cycle = DurationModel::where('id', $durationId)->value('name') ?? '';
        }
        return ['price'=>$price, 'cycle'=>$cycle, 'product'=>$ProductModel];
    }

    
    public function adminField($param){
        $hostLink = $this->where('host_id', $param['host']['id'])->find();
        if(empty($hostLink)){
            return [];
        }
        
        $configData = !empty($hostLink) ? json_decode($hostLink['config_data'], true) : [];
        $dataCenter = DataCenterModel::find($configData['data_center']['id'] ?? 0);
        if(!empty($dataCenter)){
            $configData['data_center'] = $dataCenter->toArray();
        }
        $line = LineModel::find($configData['line']['id'] ?? 0);
        if(!empty($line)){
            $configData['line'] = $line->toArray();
        }
        $image = ImageModel::find($hostLink['image_id']);

        // 获取磁盘
        $disk = DiskModel::where('host_id', $param['host']['id'])->order('is_free', 'desc')->select();

        $DataCenterModel = new DataCenterModel();

        $in_bw = '';
        $out_bw = '';
        if(isset($configData['bw'])){
            $in_bw = $configData['bw']['other_config']['in_bw'] ?: $configData['bw']['value'];
            $out_bw = $configData['bw']['value'];
        }else if(isset($configData['flow'])){
            $in_bw = $configData['flow']['other_config']['in_bw'];
            $out_bw = $configData['flow']['other_config']['out_bw'];
        }
        $data = [];

        // 基础配置
        $data[] = [
            'name' => lang_plugins('mf_cloud_base_config'),
            'field'=> [
                [
                    'name'      => lang_plugins('data_center'),
                    'key'       => 'data_center',
                    'value'     => $DataCenterModel->getDataCenterName($configData['data_center']),
                    'disable'   => true,
                ],
                [
                    'name'  => lang_plugins('mf_cloud_id'),
                    'key'   => 'zjmf_cloud_id',
                    'value' => $hostLink['rel_id'],
                ],
            ],
        ];

        // 实例配置
        $data[] = [
            'name' => lang_plugins('mf_cloud_instance_config'),
            'field'=> [
                [
                    'name'  => 'CPU',
                    'key'   => 'cpu',
                    'value' => $configData['cpu']['value'] ?? '',
                ],
                [
                    'name'  => lang_plugins('memory'),
                    'key'   => 'memory',
                    'value' => $configData['memory']['value'] ?? '',
                ],
                [
                    'name'      => lang_plugins('mf_cloud_os'),
                    'key'       => 'image',
                    'value'     => $image['name'] ?? '',
                    'disable'   => true,
                ],
                [
                    'name'      => lang_plugins('system_disk'),
                    'key'       => 'system_disk',
                    'value'     => $configData['system_disk']['value'] ?? '',
                    'disable'   => true,
                ],
            ],
        ];
        foreach($disk as $v){
            if($v['is_free'] == 1){
                $data[1]['field'][] = [
                    'name'      => lang_plugins('mf_cloud_free_disk').'('.$v['name'].')',
                    'key'       => 'disk_'.$v['id'],
                    'value'     => $v['size'],
                    'disable'   => true,
                ];
            }else{
                $data[1]['field'][] = [
                    'name'      => lang_plugins('mf_cloud_disk').'('.$v['name'].')',
                    'key'       => 'disk_'.$v['id'],
                    'value'     => $v['size'],
                ];
            }
        }
        // 快照备份
        if($hostLink['type'] != 'hyperv'){
            $data[1]['field'][] = [
                'name'      => lang_plugins('snap'),
                'key'       => 'snap_num',
                'value'     => $hostLink['snap_num'],
            ];
        }
        $data[1]['field'][] = [
            'name'      => lang_plugins('backup'),
            'key'       => 'backup_num',
            'value'     => $hostLink['backup_num'],
        ];

        // 网络配置
        $data[] = [
            'name' => lang_plugins('mf_cloud_network_config'),
            'field'=> [
                [
                    'name'      => lang_plugins('mf_cloud_recommend_config_network_type'),
                    'key'       => 'network_type',
                    'value'     => lang_plugins('mf_cloud_recommend_config_'.($configData['network_type'] ?? 'normal').'_network'),
                    'disable'   => true,
                ],
                [
                    'name'      => lang_plugins('mf_cloud_line'),
                    'key'       => 'line',
                    'value'     => $configData['line']['name'] ?? '',
                    'disable'   => true,
                ],
            ],
        ];

        // 带宽型
        if(isset($configData['bw'])){
            $data[2]['field'][] = [
                'name'      => lang_plugins('bw'),
                'key'       => 'bw',
                'value'     => $configData['bw']['value'] ?? '',
            ];

            if($hostLink['type'] != 'hyperv'){
                $data[2]['field'][] = [
                    'name'      => lang_plugins('mf_cloud_line_bw_in_bw'),
                    'key'       => 'in_bw',
                    'value'     => $configData['bw']['other_config']['in_bw'] ?? '',
                ];
            }
        }else if(isset($configData['flow'])){
            $data[2]['field'][] = [
                'name'      => lang_plugins('mf_cloud_out_server_bw'),
                'key'       => 'out_bw',
                'value'     => $configData['flow']['other_config']['out_bw'] ?? '',
            ];
            $data[2]['field'][] = [
                'name'      => lang_plugins('mf_cloud_in_server_bw'),
                'key'       => 'in_bw',
                'value'     => $configData['flow']['other_config']['in_bw'] ?? '',
            ];
            $data[2]['field'][] = [
                'name'      => lang_plugins('mf_cloud_option_value_3'),
                'key'       => 'flow',
                'value'     => $configData['flow']['value'] ?? '',
            ];
        }
        $data[2]['field'][] = [
            'name'  => lang_plugins('mf_cloud_append_ip_num'),
            'key'   => 'ip_num',
            'value' => $configData['ip']['value'] ?? '',
        ];
        $data[2]['field'][] = [
            'name'  => lang_plugins('mf_cloud_ip'),
            'key'   => 'ip',
            'value' => $hostLink['ip'],
        ];
        $data[2]['field'][] = [
            'name'  => lang_plugins('mf_cloud_option_value_4'),
            'key'   => 'defence',
            'value' => $configData['defence']['value'] ?? '',
        ];
        return $data;
    }


    public function hostUpdate($param){
        $hostId = $param['host']['id'];
        $moduleAdminField  = $param['module_admin_field'];

        $hostLink = $this->where('host_id', $param['host']['id'])->find();

        if(!empty($hostLink)){
            $oriAdminField = $this->adminField($param);

            $adminField = [];
            foreach($oriAdminField as $k=>$v){
                foreach($v['field'] as $kk=>$vv){
                    $adminField[ $vv['key'] ] = $vv['value'];
                }
            }
            // $adminField = array_column($adminField, 'value', 'key');

            $configData = json_decode($hostLink['config_data'], true);
            
            $update = [];           // 修改的参数
            $post = [];             // 云配置参数
            $bw = [];               // 带宽参数
            $change = false;        // 是否变更
            $ip_change = false;     // IP数量是否变更
            $disk_change = [];

            if(isset($moduleAdminField['cpu']) && !empty($moduleAdminField['cpu']) && $moduleAdminField['cpu'] != $adminField['cpu']){
                $configData['cpu']['value'] = $moduleAdminField['cpu'];

                $post['cpu'] = $moduleAdminField['cpu'];
                $change = true;
            }
            if(isset($moduleAdminField['memory']) && !empty($moduleAdminField['memory']) && $moduleAdminField['memory'] != $adminField['memory']){
                $configData['memory']['value'] = $moduleAdminField['memory'];

                // 获取单位
                $memoryUnit = ConfigModel::where('product_id', $param['product']['id'])->value('memory_unit') ?? 'GB';
                if($memoryUnit == 'MB'){
                    $post['memory'] = $moduleAdminField['memory'];
                }else{
                    $post['memory'] = $moduleAdminField['memory']*1024;
                }
                $change = true;
            }
            // 带宽型
            if(isset($configData['bw'])){
                if(isset($moduleAdminField['bw']) && is_numeric($moduleAdminField['bw']) && $moduleAdminField['bw'] != $adminField['bw']){
                    $configData['bw']['value'] = $moduleAdminField['bw'];

                    $bw['in_bw'] = $moduleAdminField['bw'];
                    $bw['out_bw'] = $moduleAdminField['bw'];
                    $change = true;
                }
                if($hostLink['type'] != 'hyperv'){
                    if(isset($moduleAdminField['in_bw']) && $moduleAdminField['in_bw'] != $adminField['in_bw']){
                        $configData['bw']['other_config']['in_bw'] = $moduleAdminField['in_bw'];

                        // 使用带宽参数
                        if($moduleAdminField['in_bw'] === '' && is_numeric($adminField['in_bw'])){
                            if($configData['bw']['value'] != $adminField['in_bw']){
                                $bw['in_bw'] = $configData['bw']['value'];
                            }
                        }else{
                            $bw['in_bw'] = $moduleAdminField['in_bw'];
                        }
                        $change = true;
                    }
                }
            }else if(isset($configData['flow'])){
                // 流量型
                if(isset($moduleAdminField['flow']) && $moduleAdminField['flow'] != $adminField['flow']){
                    $configData['flow']['value'] = $moduleAdminField['flow'];

                    $post['traffic_quota'] = (int)$moduleAdminField['flow'];
                    $change = true;
                }
                if(isset($moduleAdminField['in_bw']) && is_numeric($moduleAdminField['in_bw']) && $moduleAdminField['in_bw'] != $adminField['in_bw']){
                    $configData['flow']['other_config']['in_bw'] = $moduleAdminField['in_bw'];

                    $bw['in_bw'] = $moduleAdminField['in_bw'];
                    $change = true;
                }
                if(isset($moduleAdminField['out_bw']) && is_numeric($moduleAdminField['out_bw']) && $moduleAdminField['out_bw'] != $adminField['out_bw']){
                    $configData['flow']['other_config']['out_bw'] = $moduleAdminField['out_bw'];

                    $bw['out_bw'] = $moduleAdminField['out_bw'];
                    $change = true;
                }
            }
            // 备份快照
            if(isset($moduleAdminField['snap_num']) && is_numeric($moduleAdminField['snap_num']) && $moduleAdminField['snap_num'] >= 0 && $moduleAdminField['snap_num'] != $adminField['snap_num']){
                $update['snap_num'] = $moduleAdminField['snap_num'];
                if($update['snap_num'] < 1){
                    $post['snap_num'] = -1;
                }else{
                    $post['snap_num'] = $moduleAdminField['snap_num'];
                }
            }
            if(isset($moduleAdminField['backup_num']) && is_numeric($moduleAdminField['backup_num']) && $moduleAdminField['backup_num'] >= 0 && $moduleAdminField['backup_num'] != $adminField['backup_num']){
                $update['backup_num'] = $moduleAdminField['backup_num'];
                if($update['backup_num'] < 1){
                    $post['backup_num'] = -1;
                }else{
                    $post['backup_num'] = $moduleAdminField['backup_num'];
                }
            }
            if(isset($moduleAdminField['defence']) && $moduleAdminField['defence'] != $adminField['defence']){
                if(!isset($configData['defence'])){
                    $configData['defence'] = [
                        'value' => 0,
                        'price' => 0,
                    ];
                }
                $configData['defence']['value'] = (int)$moduleAdminField['defence'];

                $change = true;
            }
            if(isset($moduleAdminField['ip_num']) && $moduleAdminField['ip_num'] != $adminField['ip_num']){
                if(!isset($configData['ip_num'])){
                    $configData['ip_num'] = [
                        'value' => 0,
                        'price' => 0,
                    ];
                }
                $configData['ip']['value'] = (int)$moduleAdminField['ip_num'];

                $change = true;
                $ip_change = true;
            }
            if(isset($moduleAdminField['ip']) && $moduleAdminField['ip'] != $adminField['ip']){
                $update['ip'] = $moduleAdminField['ip'];
            }

            $IdcsmartCloud = new IdcsmartCloud($param['server']);

            if(isset($adminField['zjmf_cloud_id']) && isset($moduleAdminField['zjmf_cloud_id']) && is_numeric($moduleAdminField['zjmf_cloud_id']) && $adminField['zjmf_cloud_id'] != $moduleAdminField['zjmf_cloud_id']){
                $update['rel_id'] = (int)$moduleAdminField['zjmf_cloud_id'];
                $hostLink['rel_id'] = $update['rel_id'];

                if(!empty($update['rel_id'])){
                    $cloudDetail = $IdcsmartCloud->cloudDetail($update['rel_id']);
                    if($cloudDetail['status'] == 200){
                        $update['password'] = aes_password_encode($cloudDetail['data']['rootpassword']);
                        $update['type'] = $cloudDetail['data']['type'];
                        // 如果没有手动改就自动获取
                        if(!isset($update['ip'])){
                            $update['ip'] = $cloudDetail['data']['mainip'];
                        }
                        // 是否有转发/建站
                        if($cloudDetail['data']['nat_acl_limit'] > 0){
                            $configData['nat_acl_limit'] = $cloudDetail['data']['nat_acl_limit'];
                        }else{
                            if(isset($configData['nat_acl_limit'])){
                                unset($configData['nat_acl_limit']);
                            }
                        }
                        if($cloudDetail['data']['nat_web_limit'] > 0){
                            $configData['nat_web_limit'] = $cloudDetail['data']['nat_web_limit'];
                        }else{
                            if(isset($configData['nat_web_limit'])){
                                unset($configData['nat_web_limit']);
                            }
                        }
                    }else{
                        return ['status'=>400, 'msg'=>lang_plugins('mf_cloud_id_error')];
                    }
                }
            }
            if($change){
                $update['config_data'] = json_encode($configData);
            }
            if(!empty($update)){
                HostLinkModel::update($update, ['host_id'=>$hostId]);
            }
            
            // 如果实例ID修改了,磁盘不能扩容
            if(!isset($update['rel_id'])){
                foreach($moduleAdminField as $k=>$v){
                    if(strpos($k, 'disk_') === 0){
                        $disk = DiskModel::where('host_id', $hostId)->where('id', str_replace('disk_', '', $k))->find();
                        if(!empty($disk) && is_numeric($v) && $v > $disk['size'] && $disk['is_free'] == 0){
                            $disk_change[] = [
                                'id'        => $disk['id'],
                                'name'      => $disk['name'],
                                'rel_id'    => $disk['rel_id'],
                                'new_size'  => $v,
                                'old_size'  => $disk['size'],
                            ];
                        }
                    }
                }
            }
            $id = $hostLink['rel_id'] ?? 0;
            if(empty($id)){
                return ['status'=>200, 'msg'=>lang_plugins('not_input_idcsmart_cloud_id')];
            }
            
            $detail = '';

            $autoBoot = false;
            if(isset($post['cpu']) || isset($post['memory']) || $ip_change || !empty($disk_change)){
                $status = $IdcsmartCloud->cloudStatus($id);
                if($status['status'] == 200){
                    // 关机
                    if($status['data']['status'] == 'on' || $status['data']['status'] == 'task' || $status['data']['status'] == 'paused'){
                        $this->safeCloudOff($IdcsmartCloud, $id);
                        $autoBoot = true;
                    }
                }
            }
            if(!empty($bw)){
                $res = $IdcsmartCloud->cloudModifyBw($id, $bw);
                if($res['status'] != 200){
                    $detail .= ','.lang_plugins('mf_cloud_upgrade_bw_fail').$res['msg'];
                }else{
                    $detail .= ','.lang_plugins('mf_cloud_upgrade_bw_success');
                }
            }
            if(!empty($post)){
                $res = $IdcsmartCloud->cloudModify($id, $post);
                if($res['status'] != 200){
                    $detail .= ','.lang_plugins('mf_cloud_upgrade_common_config_fail').$res['msg'];
                }else{
                    $detail .= ','.lang_plugins('mf_cloud_upgrade_common_config_success');
                }
            }
            if($ip_change){
                $ipGroup = 0;
                // 获取下线路信息
                $line = LineModel::find($configData['line']['id']);
                if(!empty($line)){
                    if($line['defence_enable'] == 1 && isset($configData['defence']['value']) && !empty($configData['defence']['value'])){
                        $ipGroup = $line['defence_ip_group'];
                    }else if($line['bill_type'] == 'bw'){
                        $ipGroup = $line['bw_ip_group'];
                    }
                }
                $res = $IdcsmartCloud->cloudModifyIpNum($id, ['num'=>(int)$moduleAdminField['ip_num']+1, 'ip_group'=>$ipGroup ]);
                if($res['status'] == 200){
                    $detail .= ','.lang_plugins('mf_cloud_upgrade_ip_num_success');
                }else{
                    $detail .= ','.lang_plugins('mf_cloud_upgrade_ip_num_fail').$res['msg'];
                }
            }
            foreach($disk_change as $v){
                $resizeRes = $IdcsmartCloud->diskModify($v['rel_id'], ['size'=>$v['new_size']]);
                if($resizeRes['status'] == 200){
                    DiskModel::where('id', $v['id'])->update(['size'=>$v['new_size'] ]);

                    $detail .= lang_plugins('mf_cloud_modify_disk_size_success', [
                        '{name}' => $v['name'],
                        '{old}'  => $v['old_size'],
                        '{new}'  => $v['new_size'],
                    ]);
                }else{
                    $detail .= lang_plugins('mf_cloud_modify_disk_size_fail', [
                        '{name}'    => $v['name'],
                        '{reason}'  => $resizeRes['msg'],
                    ]);
                }
            }
            if($autoBoot){
                $IdcsmartCloud->cloudOn($id);
            }
            if(!empty($detail)){
                $description = lang_plugins('log_mf_cloud_host_update_complete', [
                    '{host}'    => 'host#'.$param['host']['id'].'#'.$param['host']['name'].'#',
                    '{detail}'  => $detail,
                ]);
                active_log($description, 'host', $param['host']['id']);
            }
        }
        return ['status'=>200, 'msg'=>lang_plugins('success_message')];
    }

    // 安全关机
    protected function safeCloudOff($IdcsmartCloud, $id){
        $off = false;
        // 先尝试3次软关机
        for($i = 0; $i<3; $i++){
            $res = $IdcsmartCloud->cloudOff($id);
            // 检查任务
            for($i = 0; $i<40; $i++){
                $detail = $IdcsmartCloud->taskDetail($res['data']['taskid']);
                if(isset($detail['data']['status']) && $detail['data']['status'] == 2){
                    $off = true;
                    break;
                }
                sleep(5);
            }
        }
        if(!$off){
            $res = $IdcsmartCloud->cloudHardOff($id);
            // 检查任务
            for($i = 0; $i<40; $i++){
                $detail = $IdcsmartCloud->taskDetail($res['data']['taskid']);
                if(isset($detail['data']['status']) && $detail['data']['status'] > 1){
                    break;
                }
                sleep(10);
            }
        }
        return $off;
    }

    /**
     * 时间 2023-05-12
     * @title 获取当前配置项
     * @desc  获取当前配置项
     * @author hh
     * @version v1
     * @param   [type] $param [description]
     * @return  array
     */
    public function getCurrentConfig($param){
        $hostId = $param['host']['id'];

        $hostLink = $this->where('host_id', $hostId)->find();
        $configData = json_decode($hostLink['config_data'], true);

        $data = [
            'id'                => $param['product']['id'],
            'data_center_id'    => $hostLink['data_center_id'],
            'cpu'               => $configData['cpu']['value'],
            'memory'            => $configData['memory']['value'],
            'system_disk'       => [
                'size'          => $configData['system_disk']['value'],
                'disk_type'     => $configData['system_disk']['other_config']['disk_type'],
            ],
            'backup_num'        => $hostLink['backup_num'],
            'snap_num'          => $hostLink['snap_num'],
            'line_id'           => $configData['line']['id'],
        ];
        // 获取所有数据盘
        $disk = DiskModel::where('host_id', $hostId)->where('is_free', 0)->select();
        if(!empty($disk)){
            $data['data_disk'] = [];
            foreach($disk as $v){
                $data['data_disk'][] = [
                    'id'        => $v['id'],
                    'size'      => $v['size'],
                    'disk_type' => $v['type'],
                ];
            }
        }
        if($configData['line']['bill_type'] == 'bw'){
            $data['bw'] = $configData['bw']['value'];
        }else{
            $data['flow'] = $configData['flow']['value'];
        }
        if(isset($configData['defence'])){
            $data['peak_defence'] = $configData['defence']['value'];
        }
        if(isset($configData['ip'])){
            $data['ip_num'] = $configData['ip']['value'];
        }
        return $data;
    }


}