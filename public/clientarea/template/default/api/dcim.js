// 获取数据中心
function dataCenter(id) {
    return Axios.get(`/product/${id}/idcsmart_dcim/data_center`);
}

/* 产品列表 */
function cloudList(params) {
    return Axios.get(`/idcsmart_dcim`, { params });
}



// 产品接口
// 获取数据中心
// 产品详情
function hostDetail(params) {
    return Axios.get(`/host/${params.id}`, { params });
}
// 实例详情
function cloudDetail(params) {
    return Axios.get(`/idcsmart_dcim/${params.id}`, { params });
}
// cloud日志
function getLog(params) {
    return Axios.get(`/idcsmart_dcim/${params.id}/log`, { params });
}
// 快照列表
function snapshotList(params) {
    return Axios.get(`/idcsmart_dcim/${params.id}/snapshot`, { params });
}
// 创建快照
function createSnapshot(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/snapshot`, params);
}
// 快照还原
function restoreSnapshot(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/snapshot/restore`, params);
}

// 获取快照/备份数量升降级价格
function backupConfig(params) {
    return Axios.get(`/idcsmart_dcim/${params.id}/backup_config`, { params });
}

// 生成快照/备份数量升降级订单
function backupOrder(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/backup_config/order`, params);
}

// 删除快照
function delSnapshot(params) {
    return Axios.delete(`/idcsmart_dcim/${params.id}/snapshot/${params.snapshot_id}`, params);
}
// 备份列表
function backupList(params) {
    return Axios.get(`/idcsmart_dcim/${params.id}/backup`, { params });
}
// 创建备份
function createBackup(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/backup`, params);
}
// 备份还原
function restoreBackup(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/backup/restore`, params);
}
// 删除备份
function delBackup(params) {
    return Axios.delete(`/idcsmart_dcim/${params.id}/backup/${params.backup_id}`, params);
}


// 获取实例磁盘
function diskList(params) {
    return Axios.get(`/idcsmart_dcim/${params.id}/disk`, { params });
}
// 获取其它设置
function config(params) {
    return Axios.get(`/product/${params.product_id}/idcsmart_dcim/config`, { params });
}
// 获取购买磁盘价格
function diskPrice(params) {
    return Axios.get(`/idcsmart_dcim/${params.id}/disk/price`, { params });
}

// 生成购买磁盘订单
function diskOrder(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/disk/order`, params);
}

// 获取扩容磁盘价格
function expanPrice(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/disk/resize`, params);
}
// 生成磁盘扩容订单
function diskExpanOrder(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/disk/resize/order`, params);
}

// 修改产品备注
function editNotes(params) {
    return Axios.put(`/host/${params.id}/notes`, params);
}
// 获取可用操作系统
function image(params) {
    return Axios.get(`/product/${params.id}/idcsmart_dcim/image`, { params });
}
// 检查产品是否购买过镜像
function checkImage(params) {
    return Axios.get(`/idcsmart_dcim/${params.id}/image/check`, { params });
}
// 生成购买镜像订单
function imageOrder(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/image/order`, params);
}
// 获取SSH秘钥列表
function sshKey(params) {
    return Axios.get(`/ssh_key`, { params });
}
// 重装系统
function reinstall(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/reinstall`, params)
}
// 获取实例状态
function cloudStatus(params) {
    return Axios.get(`/idcsmart_dcim/${params.id}/status`, { params })
}
// 开机
function powerOn(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/on`, params)
}
// 关机
function powerOff(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/off`, params)
}
// 获取控制台地址
function vncUrl(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/vnc`, params)
}
// 救援模式
function rescue(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/rescue`, params)
}
// 退出救援模式
function exitRescue(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/rescue/exit`, params)
}
// 获取升降级订购页套餐
function upgradePackage(params) {
    return Axios.get(`/product/${params.product_id}/idcsmart_dcim/package`, { params })
}
// 获取升降级套餐价格
function upgradePackagePrice(params) {
    return Axios.get(`/idcsmart_dcim/${params.id}/package/upgrade`, { params })
}
// 生成升降级套餐订单
function upgradeOrder(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/package/upgrade/order`, params)
}
// 获取是否救援系统
function remoteInfo(params) {
    return Axios.get(`/idcsmart_dcim/${params.id}/remote_info`, { params })
}
// 重启
function reboot(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/reboot`, params)
}
// 强制重启
function hardReboot(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/hard_reboot`, params)
}
// 强制关机
function hardOff(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/hard_off`, params)
}
// 重置密码
function resetPassword(params) {
    return Axios.post(`/idcsmart_dcim/${params.id}/reset_password`, params)
}

/* 续费相关 */
// 续费页面
function renewPage(params) {
    return Axios.get(`/host/${params.id}/renew`, { params })
}
// 续费提交
function renew(params) {
    return Axios.post(`/host/${params.id}/renew`, params)
}

// 网络
// 获取ip列表
function ipList(params) {
    return Axios.get(`/idcsmart_dcim/${params.id}/ip`, { params })
}

// 产品内页获取优惠码信息
function promoCode(params) {
    return Axios.get(`/promo_code/host/${params.id}/promo_code`, { params })
}

// 统计图表
// 获取图表数据
function chartList(params) {
    return Axios.get(`/idcsmart_dcim/${params.id}/chart`, { params })
}

// 获取网络流量
function getFlow(params){
    return Axios.get(`/idcsmart_dcim/${params.id}/flow`, {params})
}