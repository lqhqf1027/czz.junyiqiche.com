define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    
    var Controller = {
        
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/store/index',
                    add_url: 'merchant/store/add',
                    edit_url: 'merchant/store/edit',
                    del_url: 'merchant/store/del',
                    multi_url: 'merchant/store/multi',
                    table: 'company_store',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'store_name', title: __('Store_name')},
                        {field: 'store_address', title: __('店铺地址'),formatter:function (v,r,i) {
                                return r.cities_name+r.store_address
                        }},
                        {field: 'storelevel.partner_rank', title: __('店铺等级')}, 
                        // {field: 'user.name', title: __('店铺所有人姓名')},
                        {field: 'store_address', title: __('Store_address'), operate:false},
                        {field: 'phone', title: __('Phone')},
                        {field: 'store_img', title: __('Store_img'), operate:false, formatter: Controller.api.formatter.images},
                        {field: 'user.invitation_code_img', title: __('Store_qrcode'), operate:false, formatter: Controller.api.formatter.invitation_code_img},
                        {field: 'count', title: __('邀请店铺数量'), operate:false, formatter: Controller.api.formatter.count},
                        {field: 'user.invite_code', title: __('Invitation_code'), operate:false, formatter: Controller.api.formatter.invite_code},
                        {field: 'main_camp', title: __('Main_camp')},
                        {field: 'salecount', title: __('店铺在售车型数量'), operate:false, formatter: Controller.api.formatter.count},
                        {field: 'buycount', title: __('店铺想买车型数量'), operate:false, formatter: Controller.api.formatter.count}, 
                        {
                            field: 'recommend',
                            title: __('是否为推荐'),
                            operate:false,
                            events: Controller.api.events.operate,
                            formatter: Controller.api.formatter.toggle, searchList: {"1": "是", "0": "否"},
                        },
                        {field: 'statuss', title: __('Statuss'), searchList: {"normal":__('Normal'),"hidden":__('Hidden')}, operate:false, formatter: Table.api.formatter.normal},
                        // {field: 'auditstatus', title: __('审核状态'), searchList: {"audit_failed":__('Audit_failed'),"pass_the_audit":__('Pass_the_audit'),"wait_the_review":__('Wait_the_review')}, formatter: Table.api.formatter.status},
                         {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,datetimeFormat: "YYYY-MM-DD",},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', title: __('Operate'), table: table, 
                            buttons: [
                                /**
                                 * 删除 
                                 */
                                {
                                    icon: 'fa fa-trash', name: 'del', icon: 'fa fa-trash', extend: 'data-toggle="tooltip"', title: __('Del'), classname: 'btn btn-xs btn-danger btn-delone',
                                       
                                    hidden: function (row) {
                                        if (row.auditstatus == 'wait_the_review') {
                                            return false;
                                        }
                                        else if (row.auditstatus == 'pass_the_audit') {

                                            return true;
                                        }
                                        else if (row.auditstatus == 'audit_failed') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'paid_the_money') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'in_the_review') {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 审核店铺
                                 */
                                {
                                    name: 'wait_the_review', text: '审核店铺', icon: 'fa fa-check-square-o', extend: 'data-toggle="tooltip"', 
                                    title: __('审核店铺'), classname: 'btn btn-xs btn-info btn-auditResult',
                                        
                                    hidden: function (row, value, index) {
                                        if (row.auditstatus == 'wait_the_review') {
                                            return false;
                                        }
                                        else if (row.auditstatus == 'pass_the_audit') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'audit_failed') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'paid_the_money') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'in_the_review') {
                                            return true;
                                        }
                                    },
                                },
                                /**
                                 * 店铺审核中
                                 */
                                {
                                    name: 'in_the_review', text: '店铺审核中',
                                    title: __('店铺审核中'), classname: 'btn btn-xs btn-success',
                                        
                                    hidden: function (row, value, index) {
                                        if (row.auditstatus == 'in_the_review') {
                                            return false;
                                        }
                                        else if (row.auditstatus == 'wait_the_review') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'audit_failed') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'pass_the_audit') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'paid_the_money') {
                                            return true;
                                        }
                                    },
                                },
                                /**
                                 * 审核店铺-----通过待支付
                                 */
                                {
                                    name: 'pass_the_audit', text: '审核店铺通过，待支付',
                                    title: __('审核店铺通过，待支付'), classname: 'btn btn-xs btn-success',
                                        
                                    hidden: function (row, value, index) {
                                        if (row.auditstatus == 'pass_the_audit') {
                                            return false;
                                        }
                                        else if (row.auditstatus == 'wait_the_review') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'audit_failed') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'paid_the_money') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'in_the_review') {
                                            return true;
                                        }
                                    },
                                },
                                /**
                                 * 审核店铺-----已支付
                                 */
                                {
                                    name: 'paid_the_money', text: '已支付',
                                    title: __('已支付'), classname: 'btn btn-xs btn-success',
                                        
                                    hidden: function (row, value, index) {
                                        if (row.auditstatus == 'paid_the_money') {
                                            return false;
                                        }
                                        else if (row.auditstatus == 'wait_the_review') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'audit_failed') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'pass_the_audit') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'in_the_review') {
                                            return true;
                                        }
                                    },
                                },
                                /**
                                 * 审核店铺-----未通过---待修改资料
                                 */
                                {
                                    name: 'information_audit', text: '审核店铺未通过，等待修改资料',
                                    title: __('审核店铺未通过，等待修改资料'), classname: 'btn btn-xs btn-danger',
                                        
                                    hidden: function (row, value, index) {
                                        if (row.auditstatus == 'audit_failed') {
                                            return false;
                                        }
                                        else if (row.auditstatus == 'pass_the_audit') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'wait_the_review') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'paid_the_money') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'in_the_review') {
                                            return true;
                                        }
                                    },
                                },
                                /**
                                 * 查看店铺的推广
                                 */
                                {
                                    name: 'store_promotion',
                                    text: '查看店铺推广',
                                    icon: 'fa fa-eye', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('查看店铺推广'), 
                                    classname: 'btn btn-xs btn-success btn-store_promotion',
                                    hidden: function (row, value, index) {
                                        if (row.count != 0) {
                                            return false;
                                        }
                                        else if (row.count == 0) {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 暂无店铺推广可查看
                                 */
                                {
                                    name: 'store_promotion',
                                    text: '暂无店铺推广可查看',
                                    icon: 'fa fa-eye-slash', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('暂无店铺推广可查看'), 
                                    classname: 'btn btn-xs btn-danger',
                                    hidden: function (row, value, index) {
                                        if (row.count == 0) {
                                            return false;
                                        }
                                        else if (row.count != 0) {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 查看店铺在售车型
                                 */
                                {
                                    name: 'store_salemodels',
                                    text: '查看店铺在售车型',
                                    icon: 'fa fa-eye', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('查看店铺在售车型'), 
                                    classname: 'btn btn-xs btn-primary btn-store_salemodels',
                                    hidden: function (row, value, index) {
                                        if (row.salecount != 0) {
                                            return false;
                                        }
                                        else if (row.salecount == 0) {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 查看店铺想买车型
                                 */
                                {
                                    name: 'store_salemodels',
                                    text: '查看店铺想买车型',
                                    icon: 'fa fa-eye', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('查看店铺想买车型'), 
                                    classname: 'btn btn-xs btn-info btn-store_buymodels',
                                    hidden: function (row, value, index) {
                                        if (row.buycount != 0) {
                                            return false;
                                        }
                                        else if (row.buycount == 0) {
                                            return true;
                                        }
                                    },

                                },
                                
                            ],
                            events: Controller.api.events.operate,
                            formatter: Controller.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            
        },
        
        //店铺推广
        storepromotion: function () {

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });
    
            var table = $("#table");
            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){};
            // 初始化表格
            table.bootstrapTable({
                url: 'merchant/store/storepromotion',
                pk: 'id',
                sortName: 'id',
                toolbar: '#toolbar',
                searchFormVisible: true,
                queryParams:function (params) {
                    params.filter = JSON.stringify({'level_store_id': Config.level_store_id});
                    params.op = JSON.stringify({'level_store_id': 'in'});
                    return {
                        search: params.search,
                        sort: params.sort,
                        order: params.order,
                        filter: params.filter,
                        op: params.op,
                        offset: params.offset,
                        limit: params.limit
                    }
                },
                columns: [
                    [
                        {field: 'store.id', title: __('Id')},

                        {field: 'store.store_name', title: __('一级店铺名称')},
                        {field: 'earnings', title: __('一级店铺收益（元）')},
                        {field: 'count', title: __('邀请店铺数量'), formatter: Controller.api.formatter.count},
                        {
                            field: 'operate', title: __('Operate'), table: table, 
                            buttons: [
                                /**
                                 * 查看店铺的推广
                                 */
                                {
                                    name: 'store_promotion',
                                    text: '查看店铺推广',
                                    icon: 'fa fa-eye', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('查看店铺推广'), 
                                    classname: 'btn btn-xs btn-success btn-levelstore_promotion',
                                    hidden: function (row, value, index) {
                                        if (row.count != 0) {
                                            return false;
                                        }
                                        else if (row.count == 0) {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 暂无店铺推广可查看
                                 */
                                {
                                    name: 'store_promotion',
                                    text: '暂无店铺推广可查看',
                                    icon: 'fa fa-eye-slash', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('暂无店铺推广可查看'), 
                                    classname: 'btn btn-xs btn-danger',
                                    hidden: function (row, value, index) {
                                        if (row.count == 0) {
                                            return false;
                                        }
                                        else if (row.count != 0) {
                                            return true;
                                        }
                                    },

                                },
                                
                            ],
                            events: Controller.api.events.operate,
                            formatter: Controller.api.formatter.operate
                        }
                    
                    ]
                ] 
                });
    
                // 为表格绑定事件
                Table.api.bindevent(table);

    
        },
        //下级店铺推广
        levelstorepromotion: function () {

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });
    
            var table = $("#table");
            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){};
            // 初始化表格
            table.bootstrapTable({
                url: 'merchant/store/levelstorepromotion',
                pk: 'id',
                sortName: 'id',
                toolbar: '#toolbar',
                searchFormVisible: true,
                queryParams:function (params) {
                    params.filter = JSON.stringify({'level_store_id': Config.level_store_ids});
                    params.op = JSON.stringify({'level_store_id': 'in'});
                    return {
                        search: params.search,
                        sort: params.sort,
                        order: params.order,
                        filter: params.filter,
                        op: params.op,
                        offset: params.offset,
                        limit: params.limit
                    }
                },
                columns: [
                    [
                        {field: 'store.id', title: __('Id'),operate:false},

                        {field: 'store.store_name', title: __('二级店铺名称')},
                        {field: 'second_earnings', title: __('二级店铺收益（元）')},
                    
                    ]
                ] 
                });
    
                // 为表格绑定事件
                Table.api.bindevent(table);

    
        },
        /**
         * 店铺在售车型
         */
        salemodels: function () {
           // 初始化表格参数配置
           Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");
            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){};
            // 初始化表格
            table.bootstrapTable({
                url: 'merchant/store/salemodels',
                toolbar: '#toolbar',
                pk: 'id',
                sortName: 'id',
                searchFormVisible: true,
                queryParams:function (params) {
                    params.filter = JSON.stringify({'store_id': Config.store_id});
                    params.op = JSON.stringify({'store_id': '='});
                    return {
                        search: params.search,
                        sort: params.sort,
                        order: params.order,
                        filter: params.filter,
                        op: params.op,
                        offset: params.offset,
                        limit: params.limit
                    }
                },
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'brand.name', title: __('品牌名称')},
                        {field: 'models_name', title: __('车型名称')},
                        {field: 'kilometres', title: __('公里数'), operate:false},
                        {field: 'parkingposition', title: __('车辆所在地'), operate:false},
                        {field: 'phone', title: __('手机号')},
                        {field: 'modelsimages', title: __('车型亮点图'), operate:false, formatter: Controller.api.formatter.images},
                        {field: 'guide_price', title: __('批发一口价（元）'), operate:false},
                        {field: 'emission_standard', title: __('过户次数'), operate:false},
                        {field: 'browse_volume', title: __('浏览量'), operate:false},
                        {field: 'car_licensetime', title: __('上牌时间'), operate:false, addclass:'datetimerange', formatter: Controller.api.formatter.datetime},
                        {field: 'factorytime', title: __('出厂时间'), operate:false, addclass:'datetimerange', formatter: Controller.api.formatter.datetime},
                        {field: 'count', title: __('共收到报价次数'), operate:false, formatter: Controller.api.formatter.count},
                        {field: 'shelfismenu', title: __('是否上下架'), formatter: Controller.api.formatter.toggle1},
                        {field: 'createtime', title: __('创建时间'), operate:false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('更新时间'), operate:false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, 
                            buttons: [
                                /**
                                 * 查看车型的报价
                                 */
                                {
                                    name: 'salemodels_price',
                                    text: '查看车型的报价',
                                    icon: 'fa fa-eye', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('查看车型的报价'), 
                                    classname: 'btn btn-xs btn-success btn-salemodels_price',
                                    hidden: function (row, value, index) {
                                        if (row.count != 0) {
                                            return false;
                                        }
                                        else if (row.count == 0) {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 暂无车型报价可查看
                                 */
                                {
                                    name: 'salemodels_noprice',
                                    text: '暂无车型报价可查看',
                                    icon: 'fa fa-eye-slash', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('暂无车型报价可查看'), 
                                    classname: 'btn btn-xs btn-danger',
                                    hidden: function (row, value, index) {
                                        if (row.count == 0) {
                                            return false;
                                        }
                                        else if (row.count != 0) {
                                            return true;
                                        }
                                    },

                                },
                                
                            ],
                            events: Controller.api.events.operate, 
                            formatter: Controller.api.formatter.operate
                        }

                    ]
                ]
            });
            // 为表格1绑定事件
            Table.api.bindevent(table);

        },
        /**
         * 店铺想买车型
         */
        buymodels: function () {
            // 初始化表格参数配置
            Table.api.init({
                 extend: {
                     'dragsort_url': ''
                 }
             });
 
             var table = $("#table");
             $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){};
             // 初始化表格
             table.bootstrapTable({
                 url: 'merchant/store/buymodels',
                 toolbar: '#toolbar',
                 pk: 'id',
                 sortName: 'id',
                 searchFormVisible: true,
                 queryParams:function (params) {
                     params.filter = JSON.stringify({'store_id': Config.store_id});
                     params.op = JSON.stringify({'store_id': '='});
                     return {
                         search: params.search,
                         sort: params.sort,
                         order: params.order,
                         filter: params.filter,
                         op: params.op,
                         offset: params.offset,
                         limit: params.limit
                     }
                 },
                 columns: [
                     [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'brand.name', title: __('品牌名称')},
                        {field: 'models_name', title: __('车型名称')},
                        {field: 'phone', title: __('手机号')},
                        {field: 'browse_volume', title: __('浏览量'), operate:false},
                        {field: 'parkingposition', title: __('期望车辆所在地'), operate:false},
                        {field: 'guide_price', title: __('心理价（元）'), operate:false},
                        {field: 'count', title: __('共收到报价次数'), operate:false, formatter: Controller.api.formatter.count},
                        {field: 'shelfismenu', title: __('是否上下架'), formatter: Controller.api.formatter.toggle1},
                        {field: 'createtime', title: __('创建时间'), operate:false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('更新时间'), operate:false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, 
                            buttons: [
                                /**
                                 * 查看车型的报价
                                 */
                                {
                                    name: 'buymodels_price',
                                    text: '查看车型的报价',
                                    icon: 'fa fa-eye', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('查看车型的报价'), 
                                    classname: 'btn btn-xs btn-success btn-buymodels_price',
                                    hidden: function (row, value, index) {
                                        if (row.count != 0) {
                                            return false;
                                        }
                                        else if (row.count == 0) {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 暂无车型报价可查看
                                 */
                                {
                                    name: 'buymodels_noprice',
                                    text: '暂无车型报价可查看',
                                    icon: 'fa fa-eye-slash', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('暂无车型报价可查看'), 
                                    classname: 'btn btn-xs btn-danger',
                                    hidden: function (row, value, index) {
                                        if (row.count == 0) {
                                            return false;
                                        }
                                        else if (row.count != 0) {
                                            return true;
                                        }
                                    },

                                },
                                
                            ],
                            events: Controller.api.events.operate, 
                            formatter: Controller.api.formatter.operate
                        }
 
                     ]
                 ]
             });
             // 为表格1绑定事件
             Table.api.bindevent(table);
 
        },
         //店铺在售车型----报价
        salemodelsprice: function () {

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });
    
            var table = $("#table");
            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){};
            // 初始化表格
            table.bootstrapTable({
                url: 'merchant/store/salemodelsprice',
                pk: 'id',
                sortName: 'id',
                toolbar: '#toolbar',
                searchFormVisible: true,
                queryParams:function (params) {
                    params.filter = JSON.stringify({'models_info_id': Config.models_info_id});
                    params.op = JSON.stringify({'models_info_id': '='});
                    return {
                        search: params.search,
                        sort: params.sort,
                        order: params.order,
                        filter: params.filter,
                        op: params.op,
                        offset: params.offset,
                        limit: params.limit
                    }
                },
                columns: [
                    [
                        {field: 'id', title: __('Id'),operate:false},

                        {field: 'user.nickname', title: __('报价用户昵称')},
                        {field: 'user.avatar', title: __('报价用户头像'), formatter: Table.api.formatter.images, operate:false},
                        {field: 'user.mobile', title: __('报价用户手机')},

                        {field: 'money', title: __('报价价格（元）')},
                        {field: 'quotationtime', title: __('报价时间'), operate:false, addclass:'datetimerange', formatter: Controller.api.formatter.datetime},
                    
                    ]
                ] 
                });
    
                // 为表格绑定事件
                Table.api.bindevent(table);

    
        },
        //店铺想买车型----报价
        buymodelsprice: function () {

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });
    
            var table = $("#table");
            // $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){return "快速搜索:车型";};
            // 初始化表格
            table.bootstrapTable({
                url: 'merchant/store/buymodelsprice',
                pk: 'id',
                sortName: 'id',
                toolbar: '#toolbar',
                searchFormVisible: true,
                queryParams:function (params) {
                    params.filter = JSON.stringify({'buy_car_id': Config.buy_car_id});
                    params.op = JSON.stringify({'buy_car_id': '='});
                    return {
                        search: params.search,
                        sort: params.sort,
                        order: params.order,
                        filter: params.filter,
                        op: params.op,
                        offset: params.offset,
                        limit: params.limit
                    }
                },
                columns: [
                    [
                        {field: 'id', title: __('Id'), operate:false},

                        {field: 'user.nickname', title: __('报价用户昵称')},
                        {field: 'user.avatar', title: __('报价用户头像'), formatter: Table.api.formatter.images, operate:false},
                        {field: 'user.mobile', title: __('报价用户手机')},

                        {field: 'money', title: __('报价价格（元）')},
                        {field: 'quotationtime', title: __('报价时间'), operate:false, addclass:'datetimerange', formatter: Controller.api.formatter.datetime},
                    
                    ]
                ] 
                });
    
                // 为表格绑定事件
                Table.api.bindevent(table);

                
    
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                //推荐
                $(document).on('click', "input[name='row[recommend]']", function () {
                    var name = $("input[name='row[name]']");
                    name.prop("placeholder", $(this).val() == 1 ? name.data("placeholder-menu") : name.data("placeholder-node"));
                });
                $("input[name='row[recommend]']:checked").trigger("click");

                Form.api.bindevent($("form[role=form]"));
            },
            events: {
                operate: {
                    //编辑
                    // 'click .btn-editone': function (e, value, row, index) {
                    //     e.stopPropagation();
                    //     e.preventDefault();
                    //     var table = $(this).closest('table');
                    //     var options = table.bootstrapTable('getOptions');
                    //     var ids = row[options.pk];
                    //     row = $.extend({}, row ? row : {}, {ids: ids});
                    //     var url = options.extend.edit_url;
                    //     Fast.api.open(Table.api.replaceurl(url, row, table), __('Edit'), $(this).data() || {});
                    // },

                    /**
                     * 删除按钮
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-delone': function (e, value, row, index) {  /**删除按钮 */

                        e.stopPropagation();
                        e.preventDefault();
                        var that = this;
                        var top = $(that).offset().top - $(window).scrollTop();
                        var left = $(that).offset().left - $(window).scrollLeft() - 260;
                        if (top + 154 > $(window).height()) {
                            top = top - 154;
                        }
                        if ($(window).width() < 480) {
                            top = left = undefined;
                        }
                        Layer.confirm(
                            __('Are you sure you want to delete this item?'),
                            { icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true },
                            function (index) {
                                var table = $(that).closest('table');
                                var options = table.bootstrapTable('getOptions');
                                Table.api.multi("del", row[options.pk], table, that);
                                Layer.close(index);
                            }
                        );
                    },
                    /**
                     * 店铺审核
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-auditResult': function (e, value, row, index) {
                        $(".btn-auditResult").data("area", ["40%", "95%"]);
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = 'merchant/store/auditResult';
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('店铺审核'), $(this).data() || {})

                    },
                    /**
                     * 查看店铺推广
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-store_promotion': function (e, value, row, index) {
                        $(".btn-store_promotion").data("area", ["95%", "95%"]);
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = 'merchant/store/storepromotion';
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('查看店铺推广'), $(this).data() || {})

                    },
                    /**
                     * 查看下级店铺推广
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-levelstore_promotion': function (e, value, row, index) {
                        $(".btn-levelstore_promotion").data("area", ["95%", "95%"]);
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = 'merchant/store/levelstorepromotion';
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('查看店铺推广'), $(this).data() || {})

                    },
                    /**
                     * 查看店铺在售车型
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-store_salemodels': function (e, value, row, index) {
                        $(".btn-store_salemodels").data("area", ["95%", "95%"]);
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = 'merchant/store/salemodels';
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('查看店铺在售车型'), $(this).data() || {})

                    },
                    /**
                     * 查看店铺想买车型
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-store_buymodels': function (e, value, row, index) {
                        $(".btn-store_buymodels").data("area", ["95%", "95%"]);
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = 'merchant/store/buymodels';
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('查看店铺想买车型'), $(this).data() || {})

                    },
                    /**
                     * 查看店铺在售车型的报价
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-salemodels_price': function (e, value, row, index) {
                        $(".btn-salemodels_price").data("area", ["95%", "95%"]);
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = 'merchant/store/salemodelsprice';
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('查看车型报价'), $(this).data() || {})

                    },
                    /**
                     * 查看店铺想买车型的报价
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-buymodels_price': function (e, value, row, index) {
                        $(".btn-buymodels_price").data("area", ["95%", "95%"]);
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = 'merchant/store/buymodelsprice';
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('查看车型报价'), $(this).data() || {})

                    },

                },
            },
            formatter: {
                operate: function (value, row, index) {
                    var table = this.table;
                    // 操作配置
                    var options = table ? table.bootstrapTable('getOptions') : {};
                    // 默认按钮组
                    var buttons = $.extend([], this.buttons || []);

                    return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                },
                /**
                 * 是否推荐
                 * @param value
                 * @param row
                 * @param index
                 * @returns {string}
                 */
                toggle: function (value, row, index) {

                    var color = typeof this.color !== 'undefined' ? this.color : 'success';
                    var yes = typeof this.yes !== 'undefined' ? this.yes : 1;
                    var no = typeof this.no !== 'undefined' ? this.no : 0;
                    return "<a href='javascript:;' data-toggle='tooltip' title='" + __('Click to toggle') + "' class='btn-change' data-id='"
                        + row.id + "' data-params='" + this.field + "=" + (value ? no : yes) + "'><i class='fa fa-toggle-on " + (value == yes ? 'text-' + color : 'fa-flip-horizontal text-gray') + " fa-2x'></i></a>";

                   
                }, 
                toggle1: function (value, row, index) {
                        
                    if (row.shelfismenu ==  1) {
                        return "<strong class='text-success'>上架中</strong>";
                        
                    }
                    else {
                        return "<strong class='text-danger'>下架中</strong>";
                    }
                        
                },
                count: function (value, row, index) {

                    return '<strong class="text-success">'+ value +'</strong>';

                }, 
                images: function (value, row, index) {
                    value = value === null ? '' : value.toString();
                    var classname = typeof this.classname !== 'undefined' ? this.classname : 'img-sm img-center';
                    var arr = value.split(',');
                    var html = [];
                    $.each(arr, function (i, value) {
                        value = value ? value : '/assets/img/blank.gif';
                        html.push('<a href="https://czz.junyiqiche.com' + value + '" target="_blank"><img class="' + classname + '" src="https://czz.junyiqiche.com' + value + '" /></a>');
                    });
                    return html.join(' ');
                },
                invite_code: function (value, row, index) {
                    if (row.auditstatus == 'paid_the_money') {
                        return row.user.invite_code;
                    }
                },
                invitation_code_img: function (value, row, index) {
                    if (row.auditstatus == 'paid_the_money') {
                        value = value ? value : '/assets/img/blank.gif';
                        return '<a href="https://czz.junyiqiche.com' + value + '" target="_blank"><img class="img-sm img-center" src="https://czz.junyiqiche.com' + value + '" /></a>';
                    
                    }
                },
                datetime: function (value, row, index) {

                    var datetimeFormat = typeof this.datetimeFormat === 'undefined' ? 'YYYY-MM-DD' : this.datetimeFormat;
                    if (isNaN(value)) {
                        return value ? Moment(value).format(datetimeFormat) : __('None');
                    } else {
                        return value ? Moment(parseInt(value) * 1000).format(datetimeFormat) : __('None');
                    }

                },
            },
        }
    };
    return Controller;
});