const { Tab } = require('../../assets/libs/zanui/index');

var app = getApp();
Page(Object.assign({}, Tab, {
  data: {
    bannerList: [], 
  },
  channel: 0,
  page: 1,
  onLoad: function () {
    var that = this; 
    app.request('/index/index', {}, function (data, ret) {
      that.setData({
        bannerList: data.bannerList,
      });
    }, function (data, ret) {
      app.error(ret.msg);
    });
  },
  onPullDownRefresh: function () {
    this.setData({ nodata: false, nomore: false });
    this.page = 1;
    this.loadArchives(function () {
      wx.stopPullDownRefresh();
    });
  },
  onReachBottom: function () {
    var that = this;
    this.loadArchives(function (data) {
      if (data.archivesList.length == 0) {
        app.info("暂无更多数据");
      }
    });
  },
  
}))