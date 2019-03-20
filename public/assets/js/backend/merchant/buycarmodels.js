define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/buycarmodels/index',
                    add_url: 'merchant/buycarmodels/add',
                    edit_url: 'merchant/buycarmodels/edit',
                    del_url: 'merchant/buycarmodels/del',
                    multi_url: 'merchant/buycarmodels/multi',
                    table: 'buycar_model',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('用户id')},
                        {field: 'store_name', title: __('店铺名称')},
                        {
                            field: 'store_address', title: __('店铺地址'), formatter: function (v, r, i) {
                                return Controller.cutString(r.cities_name + r.store_address,15)
                            }
                        },
                        {field: 'phone', title: __('联系电话')}, 
                        {field: 'nickname', title: __('用户昵称')},
                        {field: 'avatar', title: __('用户头像'), formatter: Table.api.formatter.images, operate: false},
                        {field: 'buycount', title: __('用户想买车辆数'), formatter: Controller.api.formatter.count},
                        {
                            field: 'buycount',
                            title: __('查看店铺想买车型'), table: table, buttons: [
                                {
                                    name: 'buycount', text: '查看用户想买车型', title: '查看用户想买车型', icon: 'fa fa-eye', classname: 'btn btn-xs btn-info btn-addtabs',
                                    url: 'merchant/buycarmodels/buymodels',
                                    hidden: function (row, value, index) {
                                        if (row.buycount != 0) {
                                            return false;
                                        }
                                        else if (row.buycount == 0) {
                                            return true;
                                        }
                                    },
                                },
                                {
                                    name: 'buycount', text: '暂无用户想买车型', title: '暂无用户想买车型', icon: 'fa fa-eye-slash', classname: 'btn btn-xs btn-danger',
                                    hidden: function (row, value, index) {
                                        if (row.buycount == 0) {
                                            return false;
                                        }
                                        else if (row.buycount != 0) {
                                            return true;
                                        }
                                    },
                                }
                            ],
                            operate: false,
                            formatter: Controller.api.formatter.buttons
                        },
        
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        /**参数说明：
         * 根据长度截取先使用字符串，超长部分追加…
         * str 对象字符串
         * len 目标字节长度
         * 返回值： 处理结果字符串
         */
        cutString: function (str, len) {
            //length属性读出来的汉字长度为1
            if (str.length * 2 <= len) {
                return str;
            }
            var strlen = 0;
            var s = "";
            for (var i = 0; i < str.length; i++) {
                s = s + str.charAt(i);
                if (str.charCodeAt(i) > 128) {
                    strlen = strlen + 2;
                    if (strlen >= len) {
                        return s.substring(0, s.length - 1) + "...";
                    }
                } else {
                    strlen = strlen + 1;
                    if (strlen >= len) {
                        return s.substring(0, s.length - 2) + "...";
                    }
                }
            }
            return s;
        },

        /**
         * 用户想买车型
         */
        buymodels: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");
            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function () {
            };
            // 初始化表格
            table.bootstrapTable({
                url: 'merchant/buycarmodels/buymodels',
                toolbar: '#toolbar',
                pk: 'id',
                sortName: 'id',
                searchFormVisible: true,
                queryParams: function (params) {
                    params.filter = JSON.stringify({'user_id': Config.user_id});
                    params.op = JSON.stringify({'user_id': '='});
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
                        {field: 'id', title: __('车型id')},
                        {field: 'brand.name', title: __('品牌名称')},
                        {field: 'models_name', title: __('车型名称')},
                        {field: 'phone', title: __('手机号')},
                        {field: 'browse_volume', title: __('浏览量'), operate: false},
                        {field: 'parkingposition', title: __('期望车辆所在地'), operate: false},
                        {field: 'guide_price', title: __('心理价（元）'), operate: false},
                        {
                            field: 'count',
                            title: __('共收到报价次数'),
                            operate: false,
                            formatter: Controller.api.formatter.count
                        },
                        {field: 'shelfismenu', title: __('是否上下架'), formatter: Controller.api.formatter.toggle},
                        {
                            field: 'createtime',
                            title: __('创建时间'),
                            operate: false,
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'updatetime',
                            title: __('更新时间'),
                            operate: false,
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate', title: __('Operate'), table: table,
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
                                    classname: 'btn btn-xs btn-success btn-buymodels_price btn-addtabs',
                                    url: 'merchant/buycarmodels/buymodelsprice',
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
                            formatter: Controller.api.formatter.buttons1
                        }

                    ]
                ]
            });
            // 为表格1绑定事件
            Table.api.bindevent(table);

        },
        //用户想买车型----报价
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
                url: 'merchant/buycarmodels/buymodelsprice',
                pk: 'id',
                sortName: 'id',
                toolbar: '#toolbar',
                searchFormVisible: true,
                queryParams: function (params) {
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
                        {field: 'id', title: __('报价id'), operate: false},

                        {field: 'user.nickname', title: __('报价用户昵称')},
                        {
                            field: 'user.avatar',
                            title: __('报价用户头像'),
                            formatter: Table.api.formatter.images,
                            operate: false
                        },
                        {field: 'user.mobile', title: __('报价用户手机')},

                        {
                            field: 'seller_payment_status',
                            title: __('卖家（报价人）支付状态/商户订单号'),
                            formatter: Controller.api.formatter.seller_payment,
                            operate: false
                        },
                        {
                            field: 'buyer_payment_status',
                            title: __('买家（被报价人）支付状态/商户订单号'),
                            formatter: Controller.api.formatter.buyer_payment,
                            operate: false
                        },

                        {field: 'money', title: __('报价价格（元）')},
                        {
                            field: 'quotationtime',
                            title: __('报价时间'),
                            operate: false,
                            addclass: 'datetimerange',
                            formatter: Controller.api.formatter.datetime
                        },
                        {field: 'id', title: __('是否可查看支付凭证'), operate: false, formatter: Controller.api.formatter.pay_order},
                        {
                            field: 'operate', title: __('Operate'), table: table,
                            buttons: [
                                /**
                                 * 是否确定交易
                                 */
                                {
                                    name: 'start_the_deal',
                                    text: '确定交易',
                                    icon: 'fa fa-eye',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('确定交易'),
                                    classname: 'btn btn-xs btn-success btn-start_deal',
                                    hidden: function (row, value, index) {
                                        if (row.deal_status == 'start_the_deal') {
                                            return false;
                                        }
                                        else if (row.deal_status == 'close_the_deal') {
                                            return true;
                                        }
                                        else if (row.deal_status == 'click_the_deal') {
                                            return true;
                                        }
                                        else if (row.deal_status == 'cannot_the_deal') {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 不可以交易
                                 */
                                {
                                    name: 'cannot_the_deal',
                                    text: '不可以交易',
                                    icon: 'fa fa-window-close',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('不可以交易'),
                                    classname: 'btn btn-xs btn-danger',
                                    hidden: function (row, value, index) {
                                        if (row.deal_status == 'cannot_the_deal') {
                                            return false;
                                        }
                                        else if (row.deal_status == 'close_the_deal') {
                                            return true;
                                        }
                                        else if (row.deal_status == 'start_the_deal') {
                                            return true;
                                        }
                                        else if (row.deal_status == 'click_the_deal') {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 等待买家支付保证金
                                 */
                                {
                                    name: 'to_be_paid',
                                    text: '等待买家支付保证金',
                                    icon: 'fa fa-eye',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('等待买家支付保证金'),
                                    classname: 'btn btn-xs btn-primary',
                                    hidden: function (row, value, index) {
                                        if (row.buyer_payment_status == 'to_be_paid' && row.deal_status != 'cannot_the_deal' && row.deal_status != 'start_the_deal' && row.deal_status != 'close_the_deal') {
                                            return false;
                                        }
                                        else if (row.buyer_payment_status == 'to_be_paid' && row.deal_status == 'cannot_the_deal') {
                                            return true;
                                        }
                                        else if (row.buyer_payment_status == 'to_be_paid' && row.deal_status == 'start_the_deal') {
                                            return true;
                                        }
                                        else if (row.buyer_payment_status == 'to_be_paid' && row.deal_status == 'close_the_deal') {
                                            return true;
                                        }
                                        else if (row.buyer_payment_status == 'already_paid') {
                                            return true;
                                        }
                                        else if (row.buyer_payment_status == 'to_the_account') {
                                            return true;
                                        }
                                        else if (row.buyer_payment_status == 'confirm_receipt') {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 确认买家保证金到账
                                 */
                                {
                                    name: 'buyer_payment_status',
                                    text: '确认买家保证金到账',
                                    icon: 'fa fa-eye',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('确认买家保证金到账'),
                                    classname: 'btn btn-xs btn-primary btn-buyer_account',
                                    hidden: function (row, value, index) {
                                        if (row.buyer_payment_status == 'already_paid') {
                                            return false;
                                        }
                                        else if (row.buyer_payment_status == 'to_be_paid') {
                                            return true;
                                        }
                                        else if (row.buyer_payment_status == 'to_the_account') {
                                            return true;
                                        }
                                        else if (row.buyer_payment_status == 'confirm_receipt') {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 买家保证金已经到账，等待发货
                                 */
                                {
                                    name: 'buyer_payment_status',
                                    text: '等待买家收货',
                                    icon: 'fa fa-eye',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('等待买家收货'),
                                    classname: 'btn btn-xs btn-danger',
                                    hidden: function (row, value, index) {
                                        if (row.buyer_payment_status == 'to_the_account') {
                                            return false;
                                        }
                                        else if (row.buyer_payment_status == 'to_be_paid') {
                                            return true;
                                        }
                                        else if (row.buyer_payment_status == 'already_paid') {
                                            return true;
                                        }
                                        else if (row.buyer_payment_status == 'confirm_receipt') {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 买家已经收货
                                 */
                                {
                                    name: 'buyer_payment_status',
                                    text: '买家已经收货',
                                    icon: 'fa fa-eye',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('买家已经收货'),
                                    classname: 'btn btn-xs btn-success',
                                    hidden: function (row, value, index) {
                                        if (row.buyer_payment_status == 'confirm_receipt') {
                                            return false;
                                        }
                                        else if (row.buyer_payment_status == 'to_be_paid') {
                                            return true;
                                        }
                                        else if (row.buyer_payment_status == 'already_paid') {
                                            return true;
                                        }
                                        else if (row.buyer_payment_status == 'to_the_account') {
                                            return true;
                                        }
                                    },


                                },
                                /**
                                 * 等待卖家支付保证金
                                 */
                                {
                                    name: 'to_be_paid',
                                    text: '等待卖家支付保证金',
                                    icon: 'fa fa-eye',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('等待卖家支付保证金'),
                                    classname: 'btn btn-xs btn-primary',
                                    hidden: function (row, value, index) {
                                        if (row.seller_payment_status == 'to_be_paid' && row.deal_status != 'cannot_the_deal'  && row.deal_status != 'start_the_deal' && row.deal_status != 'close_the_deal') {
                                            return false;
                                        }
                                        else if (row.seller_payment_status == 'to_be_paid' && row.deal_status == 'cannot_the_deal') {
                                            return true;
                                        }
                                        else if (row.seller_payment_status == 'to_be_paid' && row.deal_status == 'start_the_deal') {
                                            return true;
                                        }
                                        else if (row.seller_payment_status == 'to_be_paid' && row.deal_status == 'close_the_deal') {
                                            return true;
                                        }
                                        else if (row.seller_payment_status == 'already_paid') {
                                            return true;
                                        }
                                        else if (row.seller_payment_status == 'to_the_account') {
                                            return true;
                                        }
                                        else if (row.seller_payment_status == 'confirm_receipt') {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 确认卖家保证金到账
                                 */
                                {
                                    name: 'seller_payment_status',
                                    text: '确认卖家保证金到账',
                                    icon: 'fa fa-eye',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('确认卖家保证金到账'),
                                    classname: 'btn btn-xs btn-success btn-seller_account',
                                    hidden: function (row, value, index) {
                                        if (row.seller_payment_status == 'already_paid') {
                                            return false;
                                        }
                                        else if (row.seller_payment_status == 'to_be_paid') {
                                            return true;
                                        }
                                        else if (row.seller_payment_status == 'to_the_account') {
                                            return true;
                                        }
                                        else if (row.seller_payment_status == 'confirm_receipt') {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 卖家保证金已经到账，等待发货
                                 */
                                {
                                    name: 'seller_payment_status',
                                    text: '等待卖家发货',
                                    icon: 'fa fa-eye',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('等待卖家发货'),
                                    classname: 'btn btn-xs btn-danger',
                                    hidden: function (row, value, index) {
                                        if (row.seller_payment_status == 'to_the_account') {
                                            return false;
                                        }
                                        else if (row.seller_payment_status == 'to_be_paid') {
                                            return true;
                                        }
                                        else if (row.seller_payment_status == 'already_paid') {
                                            return true;
                                        }
                                        else if (row.seller_payment_status == 'confirm_receipt') {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 卖家已经发货
                                 */
                                {
                                    name: 'buyer_payment_status',
                                    text: '卖家已经发货',
                                    icon: 'fa fa-eye',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('卖家已经发货'),
                                    classname: 'btn btn-xs btn-success',
                                    hidden: function (row, value, index) {
                                        if (row.seller_payment_status == 'confirm_receipt') {
                                            return false;
                                        }
                                        else if (row.seller_payment_status == 'to_be_paid') {
                                            return true;
                                        }
                                        else if (row.seller_payment_status == 'already_paid') {
                                            return true;
                                        }
                                        else if (row.seller_payment_status == 'to_the_account') {
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

        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            events: {
                operate: {
                    /**
                     * 确定交易
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-start_deal': function (e, value, row, index) {
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
                            __('是否确定交易?'),
                            {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                            function (index) {
                                var table = $(that).closest('table');
                                var options = table.bootstrapTable('getOptions');
                                Fast.api.ajax({

                                    url: 'merchant/buycarmodels/startdeal',
                                    data: {id: row[options.pk]}

                                }, function (data, ret) {

                                    Toastr.success('操作成功');
                                    Layer.close(index);
                                    table.bootstrapTable('refresh');
                                    return false;
                                }, function (data, ret) {
                                    //失败的回调
                                    Toastr.success(ret.msg);

                                    return false;
                                });
                            }
                        );
                    },
                    /**
                     * 确认买家保证金到账
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-buyer_account': function (e, value, row, index) {
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
                            __('是否确认买家保证金到账?'),
                            {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                            function (index) {
                                var table = $(that).closest('table');
                                var options = table.bootstrapTable('getOptions');
                                Fast.api.ajax({

                                    url: 'merchant/buycarmodels/buyeraccount',
                                    data: {id: row[options.pk]}

                                }, function (data, ret) {

                                    Toastr.success('操作成功');
                                    Layer.close(index);
                                    table.bootstrapTable('refresh');
                                    return false;
                                }, function (data, ret) {
                                    //失败的回调
                                    Toastr.success(ret.msg);

                                    return false;
                                });
                            }
                        );
                    },
                    /**
                     * 确认卖家保证金到账
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-seller_account': function (e, value, row, index) {
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
                            __('是否确认卖家保证金到账?'),
                            {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                            function (index) {
                                var table = $(that).closest('table');
                                var options = table.bootstrapTable('getOptions');
                                Fast.api.ajax({

                                    url: 'merchant/buycarmodels/selleraccount',
                                    data: {id: row[options.pk]}

                                }, function (data, ret) {

                                    Toastr.success('操作成功');
                                    Layer.close(index);
                                    table.bootstrapTable('refresh');
                                    return false;
                                }, function (data, ret) {
                                    //失败的回调
                                    Toastr.success(ret.msg);

                                    return false;
                                });
                            }
                        );
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
                buttons: function (value, row, index) {
                    // 默认按钮组
                    var buttons = $.extend([], this.buttons || []);
                    return Controller.api.buttonlink(this, buttons, value, row, index, 'buttons');
                },
                buttons1: function (value, row, index) {
                    // 默认按钮组
                    var buttons = $.extend([], this.buttons || []);
                    return Controller.api.buttonlink1(this, buttons, value, row, index, 'buttons');
                },
                count: function (value, row, index) {

                    return value == 0 ? value : '<strong class="text-success">' + value + '</strong>';
                    
                },
                toggle: function (value, row, index) {

                    return value == 1 ? "<strong class='text-success'>上架中</strong>" : "<strong class='text-danger'>下架中</strong>";

                },
                datetime: function (value, row, index) {

                    var datetimeFormat = typeof this.datetimeFormat === 'undefined' ? 'YYYY-MM-DD' : this.datetimeFormat;
                    if (isNaN(value)) {
                        return value ? Moment(value).format(datetimeFormat) : __('None');
                    } else {
                        return value ? Moment(parseInt(value) * 1000).format(datetimeFormat) : __('None');
                    }

                },
                pay_order: function (value, row, index) {
                    //这里手动构造URL
                    url = "merchant/payorder?ref=addtabs";

                    if (row.seller_payment_status == 'to_be_paid'  || row.buyer_payment_status == 'to_be_paid') {

                        return "<strong class='label label-danger fa fa-eye-slash'>暂无支付凭证可查看</strong>"
                    }
                    else {
                        //方式一,直接返回class带有addtabsit的链接,这可以方便自定义显示内容
                        return '<a href="' + url + '" class="label label-success addtabsit fa fa-eye" title="' + __("查看支付凭证") + '">' + __('查看支付凭证') + '</a>';

                    }

                },
                buyer_payment: function (value, row, index) {

                    if (row.buyer_payment_status == 'to_be_paid') {
                        return "<strong class='text-danger'>待支付</strong>";

                    }
                    if (row.buyer_payment_status == 'already_paid') {
                        return "<strong class='text-success'>支付保证金成功/" + row.buyers_out_trade_no +"</strong>";
                    }
                    if (row.buyer_payment_status == 'to_the_account') {
                        return "<strong class='text-success'>保证金已到账，待收货/" + row.buyers_out_trade_no +"</strong>";
                    }
                    if (row.buyer_payment_status == 'confirm_receipt') {
                        return "<strong class='text-success'>买家已经收货/" + row.buyers_out_trade_no +"</strong>";
                    }
                    if (row.buyer_payment_status == 'refund_bond') {
                        return "<strong class='text-success'>买家保证金已退回/" + row.buyers_out_trade_no +"</strong>";
                    }

                },
                seller_payment: function (value, row, index) {

                    if (row.seller_payment_status == 'to_be_paid') {
                        return "<strong class='text-danger'>待支付</strong>";

                    }
                    if (row.seller_payment_status == 'already_paid') {
                        return "<strong class='text-success'>支付保证金成功/" + row.seller_out_trade_no +"</strong>";
                    }
                    if (row.seller_payment_status == 'to_the_account') {
                        return "<strong class='text-success'>保证金已到账，待发货/" + row.seller_out_trade_no +"</strong>";
                    }
                    if (row.seller_payment_status == 'confirm_receipt') {
                        return "<strong class='text-success'>卖家已经发货/" + row.seller_out_trade_no +"</strong>";
                    }
                    if (row.seller_payment_status == 'waiting_for_buyers') {
                        return "<strong class='text-success'>等待买家确认收货/" + row.seller_out_trade_no +"</strong>";
                    }
                    if (row.seller_payment_status == 'refund_bond') {
                        return "<strong class='text-success'>卖家保证金已退回/" + row.seller_out_trade_no +"</strong>";
                    }

                },
            },
            buttonlink: function (column, buttons, value, row, index, type) {
                var table = column.table;
                type = typeof type === 'undefined' ? 'buttons' : type;
                var options = table ? table.bootstrapTable('getOptions') : {};
                var html = [];
                var hidden, visible, disable, url, classname, icon, text, title, refresh, confirm, extend, click, dropdown, link;
                var fieldIndex = column.fieldIndex;
                var dropdowns = {};

                $.each(buttons, function (i, j) {
                    if (type === 'operate') {
                        if (j.name === 'dragsort' && typeof row[Table.config.dragsortfield] === 'undefined') {
                            return true;
                        }
                        if (['add', 'edit', 'del', 'multi', 'dragsort'].indexOf(j.name) > -1 && !options.extend[j.name + "_url"]) {
                            return true;
                        }
                    }
                    var attr = table.data(type + "-" + j.name);
                    if (typeof attr === 'undefined' || attr) {
                        hidden = typeof j.hidden === 'function' ? j.hidden.call(table, row, j) : (j.hidden ? j.hidden : false);
                        if (hidden) {
                            return true;
                        }
                        visible = typeof j.visible === 'function' ? j.visible.call(table, row, j) : (j.visible ? j.visible : true);
                        if (!visible) {
                            return true;
                        }
                        dropdown = j.dropdown ? j.dropdown : '';
                        url = j.url ? j.url : '';
                        url = typeof url === 'function' ? url.call(table, row, j) : (url ? Fast.api.fixurl(Table.api.replaceurl(url, row, table)) : 'javascript:;');
                        classname = j.classname ? j.classname : 'btn-primary btn-' + name + 'one';
                        icon = j.icon ? j.icon : '';
                        text = j.text ? j.text : '';
                        title = '用户(' + row.nickname + ')的想买车型';
                        refresh = j.refresh ? 'data-refresh="' + j.refresh + '"' : '';
                        confirm = j.confirm ? 'data-confirm="' + j.confirm + '"' : '';
                        extend = j.extend ? j.extend : '';
                        disable = typeof j.disable === 'function' ? j.disable.call(table, row, j) : (j.disable ? j.disable : false);
                        if (disable) {
                            classname = classname + ' disabled';
                        }
                        link = '<a href="' + url + '" class="' + classname + '" ' + (confirm ? confirm + ' ' : '') + (refresh ? refresh + ' ' : '') + extend + ' title="' + title + '" data-table-id="' + (table ? table.attr("id") : '') + '" data-field-index="' + fieldIndex + '" data-row-index="' + index + '" data-button-index="' + i + '"><i class="' + icon + '"></i>' + (text ? ' ' + text : '') + '</a>';
                        if (dropdown) {
                            if (typeof dropdowns[dropdown] == 'undefined') {
                                dropdowns[dropdown] = [];
                            }
                            dropdowns[dropdown].push(link);
                        } else {
                            html.push(link);
                        }
                    }
                });
                if (!$.isEmptyObject(dropdowns)) {
                    var dropdownHtml = [];
                    $.each(dropdowns, function (i, j) {
                        dropdownHtml.push('<div class="btn-group"><button type="button" class="btn btn-primary dropdown-toggle btn-xs" data-toggle="dropdown">' + i + '</button><button type="button" class="btn btn-primary dropdown-toggle btn-xs" data-toggle="dropdown"><span class="caret"></span></button><ul class="dropdown-menu pull-right"><li>' + j.join('</li><li>') + '</li></ul></div>');
                    });
                    html.unshift(dropdownHtml);
                }
                return html.join(' ');
            },
            buttonlink1: function (column, buttons, value, row, index, type) {
                var table = column.table;
                type = typeof type === 'undefined' ? 'buttons' : type;
                var options = table ? table.bootstrapTable('getOptions') : {};
                var html = [];
                var hidden, visible, disable, url, classname, icon, text, title, refresh, confirm, extend, click, dropdown, link;
                var fieldIndex = column.fieldIndex;
                var dropdowns = {};

                $.each(buttons, function (i, j) {
                    if (type === 'operate') {
                        if (j.name === 'dragsort' && typeof row[Table.config.dragsortfield] === 'undefined') {
                            return true;
                        }
                        if (['add', 'edit', 'del', 'multi', 'dragsort'].indexOf(j.name) > -1 && !options.extend[j.name + "_url"]) {
                            return true;
                        }
                    }
                    var attr = table.data(type + "-" + j.name);
                    if (typeof attr === 'undefined' || attr) {
                        hidden = typeof j.hidden === 'function' ? j.hidden.call(table, row, j) : (j.hidden ? j.hidden : false);
                        if (hidden) {
                            return true;
                        }
                        visible = typeof j.visible === 'function' ? j.visible.call(table, row, j) : (j.visible ? j.visible : true);
                        if (!visible) {
                            return true;
                        }
                        dropdown = j.dropdown ? j.dropdown : '';
                        url = j.url ? j.url : '';
                        url = typeof url === 'function' ? url.call(table, row, j) : (url ? Fast.api.fixurl(Table.api.replaceurl(url, row, table)) : 'javascript:;');
                        classname = j.classname ? j.classname : 'btn-primary btn-' + name + 'one';
                        icon = j.icon ? j.icon : '';
                        text = j.text ? j.text : '';
                        title = '用户(' + row.nickname + ')' + Controller.cutString(row.models_name,15) + '车型的报价';
                        refresh = j.refresh ? 'data-refresh="' + j.refresh + '"' : '';
                        confirm = j.confirm ? 'data-confirm="' + j.confirm + '"' : '';
                        extend = j.extend ? j.extend : '';
                        disable = typeof j.disable === 'function' ? j.disable.call(table, row, j) : (j.disable ? j.disable : false);
                        if (disable) {
                            classname = classname + ' disabled';
                        }
                        link = '<a href="' + url + '" class="' + classname + '" ' + (confirm ? confirm + ' ' : '') + (refresh ? refresh + ' ' : '') + extend + ' title="' + title + '" data-table-id="' + (table ? table.attr("id") : '') + '" data-field-index="' + fieldIndex + '" data-row-index="' + index + '" data-button-index="' + i + '"><i class="' + icon + '"></i>' + (text ? ' ' + text : '') + '</a>';
                        if (dropdown) {
                            if (typeof dropdowns[dropdown] == 'undefined') {
                                dropdowns[dropdown] = [];
                            }
                            dropdowns[dropdown].push(link);
                        } else {
                            html.push(link);
                        }
                    }
                });
                if (!$.isEmptyObject(dropdowns)) {
                    var dropdownHtml = [];
                    $.each(dropdowns, function (i, j) {
                        dropdownHtml.push('<div class="btn-group"><button type="button" class="btn btn-primary dropdown-toggle btn-xs" data-toggle="dropdown">' + i + '</button><button type="button" class="btn btn-primary dropdown-toggle btn-xs" data-toggle="dropdown"><span class="caret"></span></button><ul class="dropdown-menu pull-right"><li>' + j.join('</li><li>') + '</li></ul></div>');
                    });
                    html.unshift(dropdownHtml);
                }
                return html.join(' ');
            },
        }
    };
    return Controller;
});