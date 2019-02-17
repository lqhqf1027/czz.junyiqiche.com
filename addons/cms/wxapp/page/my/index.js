var app = getApp();
Page({
  data: {
    isWxapp: true,
    userInfo: {
      id: 0,
      avatar: '/assets/images/avatar.png',
      nickname: '游客',
      balance: 0,
      score: 0,
      level: 0,
      pics: []
    }
  },
  onLoad: function () {
    console.log(app.globalData.config.upload.uploadurl);
    var that = this;
  },
  onShow: function () {
    var that = this;
    if (app.globalData.userInfo) {
      that.setData({ userInfo: app.globalData.userInfo, isWxapp: that.isWxapp() });
    }
  },
  login: function () {
    var that = this;
    app.login(function () {
      that.setData({ userInfo: app.globalData.userInfo, isWxapp: that.isWxapp() });
    });
  },
  isWxapp: function () {
    return app.globalData.userInfo ? app.globalData.userInfo.username.match(/^u\d+$/) : true;
  },
   
  //点击头像上传
  // uploadAvatar: function () {
  //   var that = this;
  //   wx.chooseImage({
  //     success: function (res) { 
  //       var tempFilePaths = res.tempFilePaths;
  //       wx.uploadFile({
  //         url: 'https://czz.junyiqiche.com/wechat/wechat/uploadsfiles',
  //         // url:'https://v0.api.upyun.com/static-czz-jy',
  //         filePath: tempFilePaths[0],
  //         name: 'file',
  //         formData: app.globalData.config.upload.multipart,
  //         success: function (res) {
  //           console.log(res);return;
  //           var data = JSON.parse(res.data);
  //           if (data.code == 200) {
  //             app.request("/user/avatar", { avatar: data.url }, function (data, ret) {
  //               app.success('头像上传成功!');
  //               app.globalData.userInfo = data.userInfo;
  //               that.setData({ userInfo: data.userInfo, isWxapp: that.isWxapp()});
  //             }, function (data, ret) {
  //               app.error(ret.msg);
  //             });
  //           }
  //         }, error: function (res) {
  //           app.error("上传头像失败!");
  //         }
          
  //       });
  //     }
  //   });

  // }



/**
 * 上传图片
 */
uploadAvatar:function(){
  wx.chooseImage({
    count: 9,
    success: function ({ tempFilePaths }) {
      var promise = Promise.all(tempFilePaths.map((tempFilePath, index) => {
        return new Promise(function (resolve, reject) {
          wx.uploadFile({
            url: 'https://czz.junyiqiche.com/addons/cms/wxapp.index/upModelImg',
            filePath: tempFilePath,
            name: 'file',
            formData: null,
            success: function (res) { 
              //上传成功后的图片地址imgUrl，需要与服务器地址（app.js全局设置）做拼接, setData出去做预览
              let imgUrl = JSON.parse(res.data).data.url; //eg:'https://czz.junyiqiche.com'+imgUrl
              console.log(JSON.parse(res.data)); 
              resolve(res.data);
            },
            fail: function (err) {
              reject(new Error('failed to upload file'));
            }
          });
        });
      }));
      promise.then(function (results) {
        console.log(results);
      }).catch(function (err) {
        console.log(err);
      });
    }
  });
}






  // uploadAvatar: function () {//这里是选取图片的方法
  //   var that = this;
  //   wx.chooseImage({
  //     // count: 9 - pics.length, // 最多可以选择的图片张数，默认9
  //     sizeType: ['original', 'compressed'], // original 原图，compressed 压缩图，默认二者都有
  //     sourceType: ['album', 'camera'], // album 从相册选图，camera 使用相机，默认二者都有
  //     success: function (res) {
  //       console.log(res);
  //       var imgsrc = res.tempFilePaths;
  //       that.setData({
  //         pics: imgsrc
  //       });
  //     },
  //     fail: function () {
  //       // fail
  //     },
  //     complete: function () {
  //       // complete
  //     }
  //   })
  // },
  // uploadimg: function () {//这里触发图片上传的方法
  //   var pics = this.data.pics;
  //   console.log(pics);return;
  //   // var carInfo = JSON.stringify({
  //   //   test: 'test',
  //   //   test1: 'test1',
  //   //   test2: 'test2'
  //   // })
  //   app.uploadimg({
  //     url: 'https://czz.junyiqiche.com/addons/cms/wxapp.index/uploadModels',// 
  //     path: pics,//这里是选取的图片的地址数组
  //     parms: {
  //       carInfo: carInfo 
  //     }
  //   });
  // },
 
  

})
