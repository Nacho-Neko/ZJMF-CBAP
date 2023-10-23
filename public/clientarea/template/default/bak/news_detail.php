{include file="header"}
<!-- 页面独有样式 -->
<link rel="stylesheet" href="/{$template_catalog}/template/{$themes}/css/news.css">
</head>

<body>
  <!-- mounted之前显示 -->
  <div id="mainLoading">
    <div class="ddr ddr1"></div>
    <div class="ddr ddr2"></div>
    <div class="ddr ddr3"></div>
    <div class="ddr ddr4"></div>
    <div class="ddr ddr5"></div>
  </div>
  <div class="news news_detail" v-cloak>
    <el-container>
      <aside-menu></aside-menu>
      <el-container>
        <top-menu></top-menu>
        <el-main>
          <!-- 自己的东西 -->
          <div class="main-card">
            <div class="main-card-title">
              <img :src="`${baseUrl}img/finance/back.png`" alt="" @click="back" class="back">
              {{lang.news}}
            </div>
            <!-- pc端 -->
            <div class="new-box" v-if="newDetail">
              <p class="tit">{{newDetail.title}}</p>
              <p class="time">
                {{lang.updatw_time}}：{{newDetail.create_time | formateTime}} &nbsp;&nbsp;
                {{lang.news_key}}：{{newDetail.keywords}}
              </p>
              <div class="content" v-html="newDetail.content"></div>
              <div class="news_annex">
                <p>{{lang.news_annex}}： </p>
                <p v-for="(item,index) in newDetail.attachment">
                  <a :href="item" :key="index">
                    {{item.split('^')[1]}}
                  </a>
                </p>
              </div>
              <div class="page">
                <div class="link">
                  <a :href="`news_detail.html?id=${newDetail.prev?.id}`" class="prev" v-if="JSON.stringify(newDetail.prev) !== '{}' ">
                    <i class="el-icon-arrow-left" class="icon"></i>
                    <span><span class="txt">{{lang.news_prev}}：</span>{{newDetail.prev.title}}</span>
                  </a>
                </div>
                <div class="link">
                  <a :href="`news_detail.html?id=${newDetail.next?.id}`" class="prev" v-if="JSON.stringify(newDetail.next) !== '{}'">
                    <span v-if="newDetail.next"><span class="txt">{{lang.news_next}}：</span>{{newDetail.next.title}}</span>
                    <i class="el-icon-arrow-right" class="icon"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </el-main>
      </el-container>
    </el-container>
  </div>

  <!-- =======页面独有======= -->
  <script src="/{$template_catalog}/template/{$themes}/api/common.js"></script>
  <script src="/{$template_catalog}/template/{$themes}/utils/util.js"></script>
  <script src="/{$template_catalog}/template/{$themes}/api/news.js"></script>
  <script src="/{$template_catalog}/template/{$themes}/js/news_detail.js"></script>
  <script src="/{$template_catalog}/template/{$themes}/components/pagination/pagination.js"></script>
  {include file="footer"}