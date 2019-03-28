define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/payorder/index',
                    add_url: 'merchant/payorder/add',
                    // edit_url: 'merchant/payorder/edit',
                    del_url: 'merchant/payorder/del',
                    multi_url: 'merchant/payorder/multi',
                    table: 'pay_order',
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
                        {field: 'out_trade_no', title: __('商户订单号')},
                        {field: 'total_fee', title: __('最终支付金额'), operate:'BETWEEN'},
                        {field: 'store.store_name', title: __('Store.store_name')},
                        {field: 'store.store_img', title: __('Store.store_img'), formatter: Controller.api.formatter.images},
                        {field: 'store.bank_card', title: __('Store.bank_card')},
                        {field: 'user.nickname', title: __('User.nickname')},
                        {field: 'user.avatar', title: __('User.avatar'), formatter: Table.api.formatter.image},
                        {field: 'user.mobile', title: __('User.mobile')},
                        // {field: 'level.partner_rank', title: __('Level.partner_rank')},
                        {field: 'pay_type', title: __('支付类型'), formatter: Controller.api.formatter.normal},
                        {field: 'time_end', title: __('支付完成时间'), formatter: Controller.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 绑定TAB事件
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).closest("ul").data("field");
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                options.queryParams = function (params) {
                    var filter = {};
                    if (value !== '') {
                        filter[field] = value;
                    }
                    params.filter = JSON.stringify(filter);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
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
                        html.push('<a href="' + row.server_name + value + '" target="_blank"><img class="' + classname + '" src="' + row.server_name + value + '" /></a>');
                    });
                    return html.join(' ');
                },
                datetime: function (value, row, index) {

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