<!DOCTYPE html>
<html lang="en" theme-color="default" theme-mode>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
    <title>tempalte</title>
    <link rel="stylesheet" href="/{$template_catalog}/template/{$themes}/css/common/tdesign.min.css" />
    <link rel="stylesheet" href="/{$template_catalog}/template/{$themes}/css/common/reset.css" />
    <link rel="stylesheet" href="/{$template_catalog}/template/{$themes}/css/install.css">
    <script src="/{$template_catalog}/template/{$themes}/js/common/vue.js"></script>
    <script src="/{$template_catalog}/template/{$themes}/js/common/tdesign.min.js"></script>
    <script>
        Vue.prototype.lang = window.lang
        const url = "/{$template_catalog}/template/{$themes}/"
    </script>
    <script src="/{$template_catalog}/template/{$themes}/js/common/lang.js"></script>
    <script src="/{$template_catalog}/template/{$themes}/js/common/moment.min.js"></script>
    <script src="/{$template_catalog}/template/{$themes}/js/common/layout.js"></script>
</head>

<body>
    <!-- loading -->
    <div id="loading">
        <div class="box">
            <div></div>
            <div></div>
        </div>
    </div>
    <div class="installAndUpdate" v-cloak>
        <div class="iu">
            <img class="iu-logo" src="/{$template_catalog}/template/{$themes}/img/logo.png">
            <div class="iu-card">
                <div class="iu-card-title">
                    欢迎使用智简魔方业务管理系统
                </div>
                <div class="iu-card-main">
                    <div class="main-left">
                        <div class="left-item" :class="item.id===activeId?'menu-active':''" v-for="item in menu" :key="item.id">
                            <img class="icon-l" src="/{$template_catalog}/template/{$themes}/img/iu/iu-menu-left.png">
                            <img class="icon-r" :src="item.icon">
                            <span class="item-text">{{item.text}}</span>
                        </div>
                    </div>
                    <div class="main-right">
                        <!-- 已安装提示 开始 -->
                        <div class="r-content" v-show="activeId===0">
                            <div class="warning-content">
                                <img class="warning-img" src="/{$template_catalog}/template/{$themes}/img/iu/warning.png">
                                <div class="warning-text">
                                    智简财务系统似乎已经完成了安装，无需重复安装
                                </div>
                            </div>
                        </div>
                        <!-- 已安装提示 结束 -->
                        <!-- 欢迎页开始 -->
                        <div class="r-content" v-show="activeId===1">
                            <div class="content-title">欢迎</div>
                            <div class="welcome-main">
                                <div class="welcome-row1">您需要准备以下信息开始安装：</div>
                                <div class="welcome-row2">
                                    <div class="row2-item">
                                        <div class="row2-item-l">1</div>
                                        <div class="row2-item-r">数据库名称</div>
                                    </div>
                                    <div class="row2-item">
                                        <div class="row2-item-l">2</div>
                                        <div class="row2-item-r">数据库地址</div>
                                    </div>
                                    <div class="row2-item">
                                        <div class="row2-item-l">3</div>
                                        <div class="row2-item-r">数据库用户名</div>
                                    </div>
                                    <div class="row2-item">
                                        <div class="row2-item-l">4</div>
                                        <div class="row2-item-r">数据库密码</div>
                                    </div>
                                </div>
                                <div class="welcome-row3">
                                    <div>我们将会自动创建配置文件config.php并存储数据库连接信息，<span class="row3-imp">如果未成功，您需要手工编辑config-simple.php并重命名为config.php即可</span></div>
                                </div>
                                <div class="welcome-row4">
                                    本程序是开源程序，您可修改、分发
                                </div>
                                <t-button class="welcome-btn" @click="bengin">立刻开始</t-button>
                            </div>
                        </div>
                        <!-- 欢迎页结束 -->
                        <!-- 环境检查开始 -->
                        <div class="r-content" v-show="activeId===2">
                            <div class="content-title">环境检查</div>
                            <div class="content-main">
                                <div class="main-table">
                                    <t-table row-key="index" max-height="324" size="medium" :data="baseData.modules" :columns="columns">
                                        <template #status="{row}">
                                            <t-icon v-if="row.status == 0" name="close-circle-filled" style="color:red"></t-icon>
                                            <t-icon v-else name="check-circle-filled" style="color: green"></t-icon>
                                        </template>
                                    </t-table>
                                </div>
                            </div>
                            <div class="content-footer">
                                <t-button :disabled="baseData.error==0?false:true" @click="activeId = 3">开始配置</t-button>
                                <t-button variant="outline" @click="doStep1">重新检查</t-button>
                            </div>
                        </div>
                        <!-- 环境检查结束 -->
                        <!-- 配置数据库开始 -->
                        <div class="r-content" v-show="activeId===3">
                            <div class="content-title">配置数据库</div>
                            <div class="content-main">
                                <div class="db-content">
                                    <div class="db-text">请准确填写数据库信息，如无法确认，请联系您的服务提供商</div>
                                    <div class="db-form">
                                        <t-form label-width="120px" label-align="left" :data="dbData" :rules="dbRules" @submit="dbSubmit" :colon="false">
                                            <t-form-item label="数据库地址" name="hostname">
                                                <t-input size="large" v-model="dbData.hostname" placeholder="请输入内容"></t-input>
                                            </t-form-item>
                                            <t-form-item label="数据库名" name="dbname">
                                                <t-input size="large" v-model="dbData.dbname" placeholder="请输入内容"></t-input>
                                            </t-form-item>
                                            <t-form-item label="用户名" name="username">
                                                <t-input size="large" v-model="dbData.username" placeholder="请输入内容"></t-input>
                                            </t-form-item>
                                            <t-form-item label="密码" name="password">
                                                <t-input type="password" size="large" v-model="dbData.password" placeholder="请输入内容"></t-input>
                                            </t-form-item>
                                            <t-form-item label="数据库端口" name="hostport">
                                                <t-input size="large" v-model="dbData.hostport" placeholder="请输入内容"></t-input>
                                            </t-form-item>
                                            <div class="submit-btn">
                                                <t-button theme="default" variant="base" @click="doStep1">上一步</t-button>
                                                <t-button theme="primary" type="submit">下一步</t-button>
                                            </div>
                                        </t-form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- 配置数据库结束 -->
                        <!-- 配置信息开始 -->
                        <div class="r-content" v-show="activeId===4">
                            <div class="content-title">配置信息</div>
                            <div class="content-main">
                                <div class="db-content">
                                    <div class="db-text">填写以下信息即可完成安装，使用强大的开源业务管理系统</div>
                                    <div class="db-form">
                                        <t-form label-width="120px" label-align="left" :data="configData" :rules="configRules" @submit="configSubmit" :colon="false">
                                            <t-form-item label="站点标题" name="sitename">
                                                <t-input size="large" v-model="configData.sitename" placeholder="请输入内容"></t-input>
                                            </t-form-item>
                                            <t-form-item label="管理员用户名" name="username">
                                                <t-input size="large" v-model="configData.username" placeholder="请输入内容"></t-input>
                                            </t-form-item>
                                            <t-form-item label="管理员密码" name="password">
                                                <t-input type="password" size="large" v-model="configData.password" placeholder="请输入内容">
                                                    <t-button variant="text" slot="suffix" class="auto-pass-btn" @click="autoPass">自动生成</t-button>
                                                </t-input>
                                            </t-form-item>
                                            <t-form-item label="邮箱" name="email">
                                                <t-input size="large" v-model="configData.email" placeholder="请输入内容"></t-input>
                                            </t-form-item>
                                            <t-form-item label="授权码" name="license">
                                                <t-input size="large" v-model="configData.license" placeholder="请输入内容"></t-input>
                                            </t-form-item>
                                            <div class="submit-btn">
                                                <t-button theme="default" variant="base" @click="activeId=3">上一步</t-button>
                                                <t-button theme="primary" type="submit">提交</t-button>
                                            </div>
                                        </t-form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- 配置信息结束 -->
                        <!-- 安装完成开始 -->
                        <div class="r-content" v-show="activeId===5">
                            <div class="content-title">安装完成</div>

                        </div>
                        <!-- 安装完成结束 -->
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- =======页面独有======= -->
    <script src="/{$template_catalog}/template/{$themes}/api/common.js"></script>
    <script src="/{$template_catalog}/template/{$themes}/api/install.js"></script>
    <script src="/{$template_catalog}/template/{$themes}/js/install.js"></script>
    <!-- =======公共======= -->
    <script src="/{$template_catalog}/template/{$themes}/js/common/axios.min.js"></script>
    <script src="/{$template_catalog}/template/{$themes}/utils/request.js"></script>
</body>

</html>
