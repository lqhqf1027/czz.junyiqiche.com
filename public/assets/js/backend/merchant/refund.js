define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/refund/index',
                }
            });

            var table = $("#table");
            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){return "快速搜索:商户订单号";};
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
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
                            field: 'operate', title: __('Operate'), table: table,
                            buttons: [
                                /**
                                 * 等待买家发起退款
                                 */
                                {
                                    name: 'to_be_paid',
                                    text: '等待买家发起退款',
                                    icon: 'fa fa-eye',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('等待买家发起退款'),
                                    classname: 'btn btn-xs btn-primary',
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
                                    },

                                },
                                /**
                                 * 等待卖家发起退款
                                 */
                                {
                                    name: 'buyer_payment_status',
                                    text: '等待卖家发起退款',
                                    icon: 'fa fa-eye',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('等待卖家发起退款'),
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
                                        else if (row.seller_payment_status == 'waiting_for_buyers') {
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

            // // 绑定TAB事件
            // $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            //     var field = $(this).closest("ul").data("field");
            //     var value = $(this).data("value");
            //     var options = table.bootstrapTable('getOptions');
            //     options.pageNumber = 1;
            //     options.queryParams = function (params) {
            //         var filter = {};
            //         if (value !== '') {
            //             filter[field] = value;
            //         }
            //         params.filter = JSON.stringify(filter);
            //         return params;
            //     };
            //     table.bootstrapTable('refresh', {});
            //     return false;
            // });

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
                     * 店铺审核
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-auditResult': function (e, value, row, index) {
                        $(".btn-auditResult").data("area", ["60%", "95%"]);
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

                                    url: 'merchant/store/startdeal',
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