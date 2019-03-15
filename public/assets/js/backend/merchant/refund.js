define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {

            // 初始化表格参数配置
            Table.api.init({});

            //绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));
                if (panel.size() > 0) {
                    Controller.table[panel.attr("id")].call(this);
                    $(this).on('click', function (e) {
                        $($(this).attr("href")).find(".btn-refresh").trigger("click");
                    });
                }
                //移除绑定的事件
                $(this).unbind('shown.bs.tab');
            });

            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");

            $('ul.nav-tabs li a[data-toggle="tab"]').each(function () {
                $(this).trigger("shown.bs.tab");
            });

        },

        table: {
            /**
             * 保证金退款中
             */
            refund_bond: function () {
                // 表格1
                var refundBond = $("#refundBond");
                refundBond.on('load-success.bs.table', function (e, data) {
                    
                    //背景颜色
                    var td = $("#refundBond td:nth-child(n+5)");
                    console.log(td.length);
                    // return;
                    for (var i = 0; i<td.length;i++) {
                       
                        td[i].style.backgroundColor = "yellow";

                    }

                    //背景颜色
                    var td = $("#refundBond td:nth-child(n+11)");
                    console.log(td.length);
                    // return;
                    for (var i = 0; i<td.length;i++) {
                       
                        td[i].style.backgroundColor = "red";

                    }

                    //背景颜色
                    var td = $("#refundBond td:nth-child(17)");
                    console.log(td.length);
                    // return;
                    for (var i = 0; i<td.length;i++) {
                       
                        td[i].style.backgroundColor = "";

                    }
                    
                })
                refundBond.on('post-body.bs.table', function (e, settings, json, xhr) {
                   
                });
                // 初始化表格
                refundBond.bootstrapTable({
                    url: 'merchant/refund/refundBond',

                    toolbar: '#toolbar1',
                    pk: 'id',
                    sortName: 'id',
                    // searchFormVisible: true,

                    columns: [
                        [
                            {checkbox: true},

                            {field: 'id', title: __('Id')},
                            {field: 'models_info.models_name', title: __('交易车型名称')},
                            {field: 'bond', title: __('保证金'), operate:'BETWEEN'},
                            
                            {field: 'sell.bank_card', title: __('卖家银行卡')},
                            {field: 'sell.real_name', title: __('卖家真实姓名')},
                            {field: 'sell.phone', title: __('卖家手机号')},
                            {field: 'sell.out_trade_no', title: __('卖家支付商户订单号')},
                            {field: 'sell.total_fee', title: __('卖家最终支付金额')},
                            {field: 'sell.time_end', title: __('卖家支付完成时间'), formatter: Controller.api.formatter.datetime1},
                                
                            {field: 'buy.bank_card', title: __('买家银行卡')},
                            {field: 'buy.real_name', title: __('买家真实姓名')},
                            {field: 'buy.phone', title: __('买家手机号')},
                            {field: 'buy.out_trade_no', title: __('买家支付商户订单号')},
                            {field: 'buy.total_fee', title: __('买家最终支付金额')},
                            {field: 'buy.time_end', title: __('买家支付完成时间'), formatter: Controller.api.formatter.datetime1},

                            {
                                field: 'operate', title: __('Operate'), table: refundBond,
                                buttons: [
                                    /**
                                     * 确认买家的保证金已退回
                                     */
                                    {
                                        name: 'confirm_receipt',
                                        text: '确认买家的保证金已退回',
                                        icon: 'fa fa-eye',
                                        extend: 'data-toggle="tooltip"',
                                        title: __('确认买家的保证金已退回'),
                                        classname: 'btn btn-xs btn-primary btn-buy_refund',
                                        hidden: function (row, value, index) {
                                            if (row.buyer_payment_status == 'confirm_receipt') {
                                                return false;
                                            }
                                            else if (row.buyer_payment_status == 'already_paid') {
                                                return true;
                                            }
                                            else if (row.buyer_payment_status == 'to_the_account') {
                                                return true;
                                            }
                                            else if (row.buyer_payment_status == 'to_be_paid') {
                                                return true;
                                            }
                                            else if (row.buyer_payment_status == 'refund_bond') {
                                                return true;
                                            }
                                        },

                                    },
                                    /**
                                     * 买家的保证金已打款退回
                                     */
                                    {
                                        name: 'refund_bond',
                                        text: '买家的保证金已打款退回',
                                        icon: 'fa fa-eye',
                                        extend: 'data-toggle="tooltip"',
                                        title: __('买家的保证金已打款退回'),
                                        classname: 'btn btn-xs btn-primary',
                                        hidden: function (row, value, index) {
                                            if (row.buyer_payment_status == 'refund_bond') {
                                                return false;
                                            }
                                            else if (row.buyer_payment_status == 'already_paid') {
                                                return true;
                                            }
                                            else if (row.buyer_payment_status == 'to_the_account') {
                                                return true;
                                            }
                                            else if (row.buyer_payment_status == 'to_be_paid') {
                                                return true;
                                            }
                                            else if (row.buyer_payment_status == 'confirm_receipt') {
                                                return true;
                                            }
                                        },

                                    },
                                    /**
                                     * 确认卖家的保证金已退回
                                     */
                                    {
                                        name: 'confirm_receipt',
                                        text: '确认卖家的保证金已退回',
                                        icon: 'fa fa-eye',
                                        extend: 'data-toggle="tooltip"',
                                        title: __('确认卖家的保证金已退回'),
                                        classname: 'btn btn-xs btn-success btn-sell_refund',
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
                                            else if (row.seller_payment_status == 'waiting_for_buyers') {
                                                return true;
                                            }
                                            else if (row.buyer_payment_status == 'refund_bond') {
                                                return true;
                                            }
                                        },
                                    },
                                    /**
                                     * 卖家的保证金已打款退回
                                     */
                                    {
                                        name: 'refund_bond',
                                        text: '卖家的保证金已打款退回',
                                        icon: 'fa fa-eye',
                                        extend: 'data-toggle="tooltip"',
                                        title: __('卖家的保证金已打款退回'),
                                        classname: 'btn btn-xs btn-success',
                                        hidden: function (row, value, index) {
                                            if (row.seller_payment_status == 'refund_bond') {
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
                                            else if (row.seller_payment_status == 'waiting_for_buyers') {
                                                return true;
                                            }
                                            else if (row.seller_payment_status == 'confirm_receipt') {
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
                Table.api.bindevent(refundBond);


            },
            /**
             * 保证金已退回
             */
            refund_success: function () {
                // 表格2
                var refundSuccess = $("#refundSuccess");
                refundSuccess.on('load-success.bs.table', function (e, data) {

                    //背景颜色
                    var td = $("#refundSuccess td:nth-child(n+5)");
                    console.log(td.length);
                    // return;
                    for (var i = 0; i<td.length;i++) {
                       
                        td[i].style.backgroundColor = "yellow";

                    }

                    //背景颜色
                    var td = $("#refundSuccess td:nth-child(n+11)");
                    console.log(td.length);
                    // return;
                    for (var i = 0; i<td.length;i++) {
                       
                        td[i].style.backgroundColor = "red";

                    }

                    //背景颜色
                    var td = $("#refundSuccess td:nth-child(17)");
                    console.log(td.length);
                    // return;
                    for (var i = 0; i<td.length;i++) {
                       
                        td[i].style.backgroundColor = "";

                    }

                })
                refundSuccess.on('post-body.bs.table', function (e, settings, json, xhr) {
                   
                });
                // 初始化表格
                refundSuccess.bootstrapTable({
                    url: 'merchant/refund/refundSuccess',
                    
                    toolbar: '#toolbar2',
                    pk: 'id',
                    sortName: 'id',
                    // searchFormVisible: true,
                    columns: [
                        [
                            {checkbox: true},

                            {field: 'id', title: __('Id')},
                            {field: 'models_info.models_name', title: __('交易车型名称')},
                            {field: 'bond', title: __('保证金'), operate:'BETWEEN'},
                            
                            {field: 'sell.bank_card', title: __('卖家银行卡')},
                            {field: 'sell.real_name', title: __('卖家真实姓名')},
                            {field: 'sell.phone', title: __('卖家手机号')},
                            {field: 'sell.out_trade_no', title: __('卖家支付商户订单号')},
                            {field: 'sell.total_fee', title: __('卖家最终支付金额')},
                            {field: 'sell.time_end', title: __('卖家支付完成时间'), formatter: Controller.api.formatter.datetime1},

                            {field: 'buy.bank_card', title: __('买家银行卡')},
                            {field: 'buy.real_name', title: __('买家真实姓名')},
                            {field: 'buy.phone', title: __('买家手机号')},
                            {field: 'buy.out_trade_no', title: __('买家支付商户订单号')},
                            {field: 'buy.total_fee', title: __('买家最终支付金额')},
                            {field: 'buy.time_end', title: __('买家支付完成时间'), formatter: Controller.api.formatter.datetime1},

                            {
                                field: 'operate', title: __('Operate'), table: refundSuccess,
                                buttons: [
                                    /**
                                     * 买家的保证金已打款退回
                                     */
                                    {
                                        name: 'refund_bond',
                                        text: '买家的保证金已打款退回',
                                        icon: 'fa fa-eye',
                                        extend: 'data-toggle="tooltip"',
                                        title: __('买家的保证金已打款退回'),
                                        classname: 'btn btn-xs btn-primary',
                                        hidden: function (row, value, index) {
                                            if (row.buyer_payment_status == 'refund_bond') {
                                                return false;
                                            }
                                            else if (row.buyer_payment_status == 'already_paid') {
                                                return true;
                                            }
                                            else if (row.buyer_payment_status == 'to_the_account') {
                                                return true;
                                            }
                                            else if (row.buyer_payment_status == 'to_be_paid') {
                                                return true;
                                            }
                                            else if (row.buyer_payment_status == 'confirm_receipt') {
                                                return true;
                                            }
                                        },

                                    },
                                    /**
                                     * 卖家的保证金已打款退回
                                     */
                                    {
                                        name: 'refund_bond',
                                        text: '卖家的保证金已打款退回',
                                        icon: 'fa fa-eye',
                                        extend: 'data-toggle="tooltip"',
                                        title: __('卖家的保证金已打款退回'),
                                        classname: 'btn btn-xs btn-success',
                                        hidden: function (row, value, index) {
                                            if (row.seller_payment_status == 'refund_bond') {
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
                                            else if (row.seller_payment_status == 'waiting_for_buyers') {
                                                return true;
                                            }
                                            else if (row.buyer_payment_status == 'confirm_receipt') {
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
                // 为表格2绑定事件
                Table.api.bindevent(refundSuccess);

            }

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
                     * 确认买家的保证金已退回
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-buy_refund': function (e, value, row, index) {
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
                            __('是否确定买家的保证金已退回?'),
                            {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                            function (index) {
                                var table = $(that).closest('table');
                                var options = table.bootstrapTable('getOptions');
                                Fast.api.ajax({

                                    url: 'merchant/refund/buyrefund',
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
                     * 确认卖家的保证金已退回
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-sell_refund': function (e, value, row, index) {
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
                            __('是否确定卖家的保证金已退回?'),
                            {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                            function (index) {
                                var table = $(that).closest('table');
                                var options = table.bootstrapTable('getOptions');
                                Fast.api.ajax({

                                    url: 'merchant/refund/sellrefund',
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
                datetime1: function (value, row, index) {

                    var date1 = value.slice(0,4);
                    var date2 = value.slice(4,6);
                    var date3 = value.slice(6,8);
                    return date1 + '-' + date2 + '-' + date3;

                },
                normal: function (value, row, index) {
                    if (value == "certification") {
                        return "<strong class='text-success'>店铺认证支付</strong>"
                    }
                    if (value == "up") {
                        return "<strong class='text-success'>店铺升级支付</strong>"
                    }
                    if (value == "bond") {
                        return "<strong class='text-success'>保证金支付</strong>"
                    }
                }
    
            }
        }
    };
    return Controller;
});