const { Tab } = require('../../assets/libs/zanui/index');

var app = getApp();
Page(Object.assign({}, Tab, {
  data: {
    bannerList: [],
    
    loading: false,  
  }, 
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
}))