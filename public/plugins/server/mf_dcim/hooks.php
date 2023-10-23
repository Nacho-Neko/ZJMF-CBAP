<?php

use app\common\model\ProductModel;
use app\common\model\HostModel;
use app\common\model\ServerModel;
use server\mf_dcim\model\DurationModel;
use server\mf_dcim\model\HostLinkModel;
use server\mf_dcim\idcsmart_dcim\Dcim;
use think\db\exception\PDOException;


add_hook('after_product_delete', function($param){
	/*
	try{
		// 加入异常,有可能表不存在
		// $imageId = ImageModel::where('product_id', $param['id'])->column('id');

		// BackupConfigModel::where('product_id', $param['id'])->delete();
		// ConfigModel::where('product_id', $param['id'])->delete();
		// DataCenterModel::where('product_id', $param['id'])->delete();
		// ImageGroupModel::where('product_id', $param['id'])->delete();
		// ImageModel::where('product_id', $param['id'])->delete();
		// PackageModel::where('product_id', $param['id'])->delete();
		
		// if(!empty($imageId)){
		// 	HostImageLinkModel::whereIn('image_id', $imageId)->delete();
		// }
	}catch(\Exception $e){
		
	}
	*/
});

add_hook('after_host_delete', function($param){
	try{
		HostLinkModel::where('host_id', $param['id'])->delete();
	}catch(\Exception $e){
		
	}
});


add_hook('daily_cron', function($param){
	try{
		// 处理清零,超额暂停
		$host = HostLinkModel::alias('hl')
			->field('h.id,h.name,h.active_time,h.create_time,h.server_id,h.status,h.suspend_type,hl.rel_id,hl.config_data')
			->join('host h', 'hl.host_id=h.id')
			->whereIn('h.status', 'Active,Suspended')
			->where('hl.rel_id', '>', 0)
			->select()
			->toArray();
	}catch(\Exception $e){
		// 异常可能是表不存在
		return false;
	}

	$HostModel = new HostModel();
	$dcim = [];
	$date = date('d');
	foreach($host as $v){
		$configData = json_decode($v['config_data'], true);
		if($configData['line']['bill_type'] != 'flow'){
			continue;
		}
		// 流量周期
		$billCycle = $configData['flow']['other_config']['bill_cycle'] ?? 'month';

		if(!isset($dcim[$v['server_id']])){
			$ServerModel = ServerModel::find($v['server_id']);
			$ServerModel['password'] = aes_password_decode($ServerModel['password']);

			$Dcim = new Dcim($ServerModel);
			$dcim[$v['server_id']] = $Dcim;
		}else{
			$Dcim = $dcim[ $v['server_id'] ];
		}
		// 是否是开通日,开通日不清零流量
		$isCreateDay = false;
		if($billCycle == 'last_30days'){
			// 计算开通的日期
			$date = date('d', $v['active_time'] ?: $v['create_time']);
			
			if(date('Ymd') == date('Ymd', $v['active_time'] ?: $v['create_time'])){
				$isCreateDay = true;
			}
        }else{
        	$date = '01';
        }
        // 流量清零
        if($date == date('d') && !$isCreateDay){
        	$res = $Dcim->resetFlow(['id'=>$v['rel_id'], 'hostid'=>$v['id']]);

        	if($res['status'] == 200){
        		if($v['status'] == 'Suspended' && $v['suspend_type'] == 'overtraffic'){
        			$unsuspendRes = $HostModel->unsuspendAccount($v['id']);
        			if($unsuspendRes['status'] == 200){
        				$description = lang_plugins('mf_dcim_log_host_flow_clear_and_unsuspend_success', [
        					'{host}' => $v['name'],
        				]);
        			}else{
        				$description = lang_plugins('mf_dcim_log_host_flow_clear_but_unsuspend_fail', [
        					'{host}' 	=> $v['name'],
        					'{reason}' 	=> $unsuspendRes['msg'],
        				]);
        			}
        		}else{
        			$description = lang_plugins('mf_dcim_log_host_flow_clear_success', [
    					'{host}' => $v['name'],
    				]);
        		}
        	}else{
        		$description = lang_plugins('mf_dcim_log_host_flow_clear_fail', [
					'{host}' 	=> $v['name'],
					'{reason}' 	=> $res['msg'],
				]);
        	}
        	active_log($description, 'host', $v['id']);
        }else{
        	// 只有已激活的才检查
        	if($v['status'] == 'Suspended'){
        		continue;
        	}

        	// 检查是否超额
        	$post = [];
			$post['id'] = $v['rel_id'];
			$post['hostid'] = $v['id'];
			$post['unit'] = 'GB';

			$flow = $Dcim->flow($post);
			if($flow['status'] == 200){
				$data = $flow['data'][ $billCycle ];
				$percent = str_replace('%', '', $data['used_percent']);

				$total = $flow['limit'] > 0 ? $flow['limit'] + $flow['temp_traffic'] : 0;
				$used = round($total * $percent / 100, 2);
				if($percent >= 100){
					// 执行超额
					$post = [];
					$post['id'] = $v['rel_id'];
			        $post['type'] = $billCycle;
			        $post['hostid'] = $v['id'];

			        $overFlow = $Dcim->overFlow($post);
			        if($overFlow['status'] == 200){
			        	// 超额后执行超额暂停
			        	if($overFlow['act'] == 1){
			                //执行暂停
			                $suspendRes = $HostModel->suspendAccount([
			                	'id'			=> $v['id'],
			                	'suspend_type'	=> 'overtraffic',
			                	'suspend_reason'=> lang_plugins('mf_dcim_flow_limit_desc', ['{total}'=>$total, '{used}'=>$used]),
			                ]);
			                if($suspendRes['status'] == 200){
			                	$description = lang_plugins('mf_dcim_log_host_over_flow_suspend_success', [
			                		'{host}' 	=> $v['name'],
			                		'{total}' 	=> $total,
			                		'{used}' 	=> $used,
			                	]);
			                }else{
			                	$description = lang_plugins('mf_dcim_log_host_over_flow_success_but_suspend_fail', [
			                		'{host}' 	=> $v['name'],
			                		'{total}' 	=> $total,
			                		'{used}' 	=> $used,
			                		'{reason}' 	=> $suspendRes['msg'],
			                	]);
			                }
			            }else if($overFlow['act'] == 2){
			            	$description = lang_plugins('mf_dcim_log_host_over_flow_limit_bw_success', [
		                		'{host}' 	=> $v['name'],
		                		'{total}' 	=> $total,
		                		'{used}' 	=> $used,
		                	]);
			            }else if($overFlow['act'] == 3){
			            	$description = lang_plugins('mf_dcim_log_host_over_flow_close_port_success', [
		                		'{host}' 	=> $v['name'],
		                		'{total}' 	=> $total,
		                		'{used}' 	=> $used,
		                	]);
			            }else{
			            	$description = '';
			            }
			        }else{
			        	$description = lang_plugins('mf_dcim_log_host_over_flow_fail', [
	                		'{host}' 	=> $v['name'],
	                		'{total}' 	=> $total,
	                		'{used}' 	=> $used,
	                		'{reason}' 	=> $overFlow['msg'],
	                	]);
			        }
			        if(!empty($description)){
		            	active_log($description, 'host', $v['id']);
		            }
				}
			}
        }
        // 防止请求过快
        sleep(1);
	}
});

// 购买流量包后
add_hook('flow_packet_order_paid', function($param){
	$hostId = $param['host_id'];
	$flow = $param['flow_packet']['capacity'];
	$moduleParam = $param['module_param'];

	if(!empty($moduleParam['server']) && $moduleParam['server']['module'] == 'mf_dcim'){
		$Dcim = new Dcim($moduleParam['server']);

		$hostLink = HostLinkModel::where('host_id', $hostId)->find();
		$billCycle = 'month';
		if(!empty($hostLink)){
			$configData = json_decode($hostLink['config_data'], true);
			$billCycle = $configData['flow']['other_config']['bill_cycle'] ?? 'month';
		}

		$res = $Dcim->addTempTraffic([
			'id'		=> $hostLink['rel_id'] ?? 0,
			'type'		=> $billCycle,
			'traffic'	=> $flow,
			'hostid'	=> $hostId,
		]);
		if($res['status'] == 200){
			$description = lang_plugins('mf_dcim_log_buy_flow_packet_success', [
				'{host}'	=> 'host#'.$hostId.'#'.$moduleParam['host']['name'].'#',
				'{order}'	=> '#'.$param['order_id'],
				'{flow}'	=> $flow.'G',
			]);

            // 如果是流量暂停在检查流量
	        if(isset($res['act']) && $res['act'] == 1 && $moduleParam['host']['status'] == 'Suspended' && $moduleParam['host']['suspend_type'] == 'overtraffic'){
		        if($moduleParam['host']['due_time'] == 0 || time() < $moduleParam['host']['due_time']){
		        	$result = $moduleParam['host']->unsuspendAccount($hostId);
	                if ($result['status'] == 200){
                        $description .= lang_plugins('mf_dcim_log_buy_flow_packet_and_unsuspend_success');
                    }else{
                        $description .= lang_plugins('mf_dcim_log_buy_flow_packet_and_unsuspend_fail', ['{reason}'=>$result['msg']]);
                    }
		        }
		    }
        }else{
        	$description = lang_plugins('mf_dcim_log_buy_flow_packet_remote_fail', [
				'{host}'	=> 'host#'.$hostId.'#'.$moduleParam['host']['name'].'#',
				'{order}'	=> '#'.$param['order_id'],
				'{flow}'	=> $flow.'G',
			]);
        }
	    // 记录日志
	    active_log($description, 'host', $hostId);
	}
});

// 在购买流量包之前
add_hook('flow_packet_before_order', function($param){
	try{
		$hostLink = hostLinkModel::where('host_id', $param['host']['id'])->find();
		if(!empty($hostLink)){
			$configData = json_decode($hostLink['config_data'], true);
			if(isset($configData['line']['bill_type']) && $configData['line']['bill_type'] !== 'flow'){
				// 不是流量线路,不能购买
				return ['status'=>400, 'msg'=>lang_plugins('mf_dcim_cannot_buy_flow_packet')];
			}
		}
	}catch(PDOException $e){
		
	}
});