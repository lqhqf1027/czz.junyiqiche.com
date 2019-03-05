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

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'order_number', title: __('Order_number')},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'store.store_name', title: __('Store.store_name')},
                        {field: 'store.store_img', title: __('Store.store_img'), formatter: Controller.api.formatter.images},
                        {field: 'store.bank_card', title: __('Store.bank_card')},
                        {field: 'user.nickname', title: __('User.nickname')},
                        {field: 'user.avatar', title: __('User.avatar'), formatter: Table.api.formatter.image},
                        {field: 'user.mobile', title: __('User.mobile')},
                        {field: 'level.partner_rank', title: __('Level.partner_rank')},
                        {field: 'pay_type', title: __('Pay_type'), formatter: Controller.api.formatter.normal},
                        {field: 'pay_time', title: __('Pay_time'), formatter: Controller.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });z

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
                        html.push('<a href="https://czz.junyiqiche.com' + value + '" target="_blank"><img class="' + classname + '" src="https://czz.junyiqiche.com' + value + '" /></a>');
                    });
                    return html.join(' ');
                },
                datetime: function (value, row, index) {

                    var datetimeFormat = typeof this.datetimeFormat === 'undefined' ? 'YYYY-MM-DD' : this.datetimeFormat;
                    if (isNaN(value)) {
                        return value ? Moment(value).format(datetimeFormat) : __('None');
                    } else {
                        return value ? Moment(parseInt(value) * 1000).format(datetimeFormat) : __('None');
                    }

                },
                normal: function (value, row, index) {
                    if (value == "certification") {
                        return "<strong class='text-success'>认证成功</strong>"
                    }
                    if (value == "up") {
                        return "<strong class='text-success'>升级</strong>"
                    }
                }
    
            }
        }
    };
    return Controller;
});