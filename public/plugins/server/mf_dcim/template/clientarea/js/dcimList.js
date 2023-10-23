const template = document.getElementById("cloudList");
Vue.prototype.lang = window.lang;
new Vue({
  created() {
    this.analysisUrl();
    this.getCloudList();
    this.getCommon();
  },
  components: {
    asideMenu,
    topMenu,
    pagination,
  },
  data() {
    return {
      imgUrl: `${url}`,
      id: 0,
      menuActiveId: 1,
      hostData: {},
      commonData: {},
      menuList: [
        {
          id: 1,
          text: lang.cloud_menu_1,
        },
        {
          id: 2,
          text: lang.cloud_menu_2,
        },
        {
          id: 3,
          text: lang.cloud_menu_3,
        },
        {
          id: 4,
          text: lang.cloud_menu_4,
        },
        {
          id: 5,
          text: lang.cloud_menu_5,
        },
      ],
      powerStatus: {
        on: { text: lang.common_cloud_text10, icon: `${url}/img/cloud/on.png` },
        off: {
          text: lang.common_cloud_text11,
          icon: `${url}/img/cloud/off.png`,
        },
        operating: {
          text: lang.common_cloud_text12,
          icon: `${url}/img/cloud/operating.png`,
        },
        fault: {
          text: lang.common_cloud_text86,
          icon: `${url}/img/cloud/fault.png`,
        },
        suspend: {
          text: lang.common_cloud_text87,
          icon: `${url}/img/cloud/suspended.png`,
        },
      },
      status: {
        Unpaid: {
          text: lang.common_cloud_text88,
          color: "#F64E60",
          bgColor: "#FFE2E5",
        },
        Pending: {
          text: lang.common_cloud_text89,
          color: "#3699FF",
          bgColor: "#E1F0FF",
        },
        Active: {
          text: lang.common_cloud_text90,
          color: "#1BC5BD",
          bgColor: "#C9F7F5",
        },
        Suspended: {
          text: lang.common_cloud_text91,
          color: "#F99600",
          bgColor: "#FFF4DE",
        },
        Deleted: {
          text: lang.common_cloud_text92,
          color: "#9696A3",
          bgColor: "#F2F2F7",
        },
        Failed: {
          text: lang.common_cloud_text93,
          color: "#3699FF",
          bgColor: "#E1F0FF",
        },
      },
      statusSelect: [
        {
          id: 1,
          status: "Unpaid",
          label: lang.common_cloud_text88,
        },
        {
          id: 2,
          status: "Pending",
          label: lang.common_cloud_text89,
        },
        {
          id: 3,
          status: "Active",
          label: lang.common_cloud_text90,
        },
        {
          id: 4,
          status: "Suspended",
          label: lang.common_cloud_text91,
        },
        {
          id: 5,
          status: "Deleted",
          label: lang.common_cloud_text92,
        },
      ],
      // 数据中心
      center: [],
      // 产品列表
      cloudData: [],
      loading: false,
      params: {
        page: 1,
        limit: 20,
        pageSizes: [20, 50, 100],
        total: 200,
        orderby: "id",
        sort: "desc",
        keywords: "",
        data_center_id: "",
        status: "",
        m: null,
      },
      timerId: null,
    };
  },
  filters: {
    formateTime(time) {
      if (time && time !== 0) {
        return formateDate(time * 1000);
      } else {
        return "--";
      }
    },
  },
  methods: {
    analysisUrl() {
      let url = window.location.href;
      let getqyinfo = url.split("?")[1];
      let getqys = new URLSearchParams("?" + getqyinfo);
      let m = getqys.get("m");
      this.params.m = m;
    },
    getCommon() {
      this.commonData = JSON.parse(localStorage.getItem("common_set_before"));
      document.title =
        this.commonData.website_name + "-" + lang.common_cloud_text94;
    },
    // 切换分页
    sizeChange(e) {
      this.params.limit = e;
      this.params.page = 1;
      this.getCloudList();
    },
    currentChange(e) {
      this.params.page = e;
      this.getCloudList();
    },
    // 数据中心选择框变化时
    selectChange() {
      this.params.page = 1;
      this.getCloudList();
    },
    inputChange() {
      this.params.page = 1;
      this.getCloudList();
    },
    centerSelectChange() {
      this.params.page = 1;
      this.getCloudList();
    },
    statusSelectChange() {
      this.params.page = 1;
      this.getCloudList();
    },
    // 获取产品列表
    getCloudList() {
      this.loading = true;
      cloudList(this.params).then((res) => {
        if (res.data.status === 200) {
          let list = res.data.data.list;
          this.cloudData = list;
          this.params.total = res.data.data.count;
          const area = res.data.data.data_center;
          area &&
            area.map((item) => {
              item.label =
                item.country_name + "-" + item.city + "-" + item.area;
              return item;
            });
          this.center = area;
        }
        this.loading = false;
      });
    },
    // 跳转产品详情
    toDetail(row) {
      location.href = `productdetail.htm?id=${row.id}`;
    },
  },
}).$mount(template);
