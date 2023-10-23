/*
* 魔方云
*/
const base = 'pve'

/* 周期 */
function getDuration (params) {
  return Axios.get(`/${base}/duration`, { params })
}
function createAndUpdateDuration (type, params) {
  if (type === 'add') {
    return Axios.post(`/${base}/duration`, params)
  } else if (type === 'update') {
    return Axios.put(`/${base}/duration/${params.id}`, params)
  }
}
function delDuration (params) {
  return Axios.delete(`/${base}/duration/${params.id}`)
}
/* 操作系统 */
// 分类
function getImageGroup (params) {
  return Axios.get(`/${base}/image_group`, { params })
}
function createAndUpdateImageGroup (type, params) {
  if (type === 'add') {
    return Axios.post(`/${base}/image_group`, params)
  } else if (type === 'update') {
    return Axios.put(`/${base}/image_group/${params.id}`, params)
  }
}
function delImageGroup (params) {
  return Axios.delete(`/${base}/image_group/${params.id}`)
}
// 镜像分组排序
function changeImageGroup (params) {
  return Axios.put(`/${base}/image_group/order`, params)
}
// 系统
function getImage (params) {
  return Axios.get(`/${base}/image`, { params })
}
function createAndUpdateImage (type, params) {
  if (type === 'add') {
    return Axios.post(`/${base}/image`, params)
  } else if (type === 'update') {
    return Axios.put(`/${base}/image/${params.id}`, params)
  }
}
function delImage (params) {
  return Axios.delete(`/${base}/image/${params.id}`)
}
// 拉取系统
function refreshImage (params) {
  return Axios.get(`/${base}/image/sync`, { params })
}

/* 其他设置 */
function getCloudConfig (params) {
  return Axios.get(`/${base}/config`, { params })
}
function saveCloudConfig (params) {
  return Axios.put(`/${base}/config`, params)
}
function changeCloudSwitch (params) { // 存储tab切换性能
  return Axios.put(`/${base}/config/disk_limit_enable`, params)
}

/* 计算配置 */
// cpu配置
function getCpu (params) {
  return Axios.get(`/${base}/cpu`, { params })
}
function createAndUpdateCpu (type, params) {
  if (type === 'add') {
    return Axios.post(`/${base}/cpu`, params)
  } else if (type === 'update') {
    return Axios.put(`/${base}/cpu/${params.id}`, params)
  }
}
function delCpu (params) {
  return Axios.delete(`/${base}/cpu/${params.id}`)
}
function getCpuDetails (params) {
  return Axios.get(`/${base}/cpu/${params.id}`, { params })
}
// 内存
function getMemory (params) {
  return Axios.get(`/${base}/memory`, { params })
}
function createAndUpdateMemory (type, params) {
  if (type === 'add') {
    return Axios.post(`/${base}/memory`, params)
  } else if (type === 'update') {
    return Axios.put(`/${base}/memory/${params.id}`, params)
  }
}
function delMemory (params) {
  return Axios.delete(`/${base}/memory/${params.id}`)
}
function getMemoryDetails (params) {
  return Axios.get(`/${base}/memory/${params.id}`, { params })
}

/*
  存储配置
  系统盘 + 数据盘
 */
// name：接口名字(system_disk,data_disk,system_disk_limit,data_disk_limit)
// type: 新增，编辑
// parasm：参数
function getStore (name, params) {
  return Axios.get(`/${base}/${name}`, { params })
}
function createAndUpdateStore (name, type, params) {
  if (type === 'add') {
    return Axios.post(`/${base}/${name}`, params)
  } else if (type === 'update') {
    return Axios.put(`/${base}/${name}/${params.id}`, params)
  }
}
function delStore (name, params) {
  return Axios.delete(`/${base}/${name}/${params.id}`)
}
function getStoreDetails (name, params) {
  return Axios.get(`/${base}/${name}/${params.id}`, { params })
}
// 添加系统盘性能限制
function getStoreLimit (name, params) {
  return Axios.get(`/${base}/${name}`, { params })
}
function createAndUpdateStoreLimit (name, type, params) {
  if (type === 'add') {
    return Axios.post(`/${base}/${name}`, params)
  } else if (type === 'update') {
    return Axios.put(`/${base}/${name}/${params.id}`, params)
  }
}
function delStoreLimit (name, params) {
  return Axios.delete(`/${base}/${name}/${params.id}`)
}

/* 推荐配置 */
function getRecommend (params) {
  return Axios.get(`/${base}/recommend_config`, { params })
}
function createAndUpdateRecommend (type, params) {
  if (type === 'add') {
    return Axios.post(`/${base}/recommend_config`, params)
  } else if (type === 'update') {
    return Axios.put(`/${base}/recommend_config/${params.id}`, params)
  }
}
function delRecommend (params) {
  return Axios.delete(`/${base}/recommend_config/${params.id}`)
}

/* 数据中心 */
function getDataCenter (params) {
  return Axios.get(`/${base}/data_center`, { params })
}
function getCountry () {
  return Axios.get(`/country`)
}
// 创建/修改
function createOrUpdateDataCenter (type, params) {
  if (type === 'add') {
    return Axios.post(`/${base}/data_center`, params)
  } else if (type === 'update') {
    return Axios.put(`/${base}/data_center/${params.id}`, params)
  }
}
// 删除
function deleteDataCenter (params) {
  return Axios.delete(`/${base}/data_center/${params.id}`)
}
// 数据中心选择
function chooseDataCenter (params) {
  return Axios.get(`/${base}/data_center/select`, { params })
}
/* 线路 */
function getLine (params) {
  return Axios.get(`/${base}/line`, { params })
}
function createAndUpdateLine (type, params) {
  if (type === 'add') {
    return Axios.post(`/${base}/line`, params)
  } else if (type === 'update') {
    return Axios.put(`/${base}/line/${params.id}`, params)
  }
}
function delLine (params) {
  return Axios.delete(`/${base}/line/${params.id}`)
}
function getLineDetails (params) {
  return Axios.get(`/${base}/line/${params.id}`)
}
/* 线路-子项配置 */
// name：接口名字(line_bw,line_flow,line_defence,line_ip)
// type: 新增，编辑
// parasm：参数
function getLineChiLd (name, params) {
  return Axios.get(`/${base}/${name}/${params.id}`, { params })
}
function createAndUpdateLineChild (name, type, params) {
  if (type === 'add') {
    return Axios.post(`/${base}/line/${params.id}/${name}`, params)
  } else if (type === 'update') {
    return Axios.put(`/${base}/${name}/${params.id}`, params)
  }
}
function getLineChildDetails (name, params) {
  return Axios.get(`/${base}/${name}/${params.id}`, { params })
}
function delLineChild (name, params) {
  return Axios.delete(`/${base}/${name}/${params.id}`)
}
// 获取系统盘/数据盘类型 system_disk , data_disk
function getDiskType (type, params) {
  return Axios.get(`/${base}/${type}/type`, { params })
}

// 配置限制
function getConfigLimit ( params) {
  return Axios.get(`/${base}/config_limit`, { params })
}
function createAndUpdateConfigLimit (type, params) {
  if (type === 'add') {
    return Axios.post(`/${base}/config_limit`, params)
  } else if (type === 'update') {
    return Axios.put(`/${base}/config_limit/${params.id}`, params)
  }
}
function delConfigLimit (params) {
  return Axios.delete(`/${base}/config_limit/${params.id}`)
}

// 检查切换类型后是否清空冲突数据
function checkType (params) {
  return Axios.post(`/${base}/config/check_clear`, params)
}

// 保存数据盘数量限制
function saveDiskNumLimit (params) {
  return Axios.post(`/${base}/config/disk_num_limit`, params)
}
// 保存免费数据盘
function saveFreeData (params) {
  return Axios.post(`/${base}/config/free_disk`, params)
}
