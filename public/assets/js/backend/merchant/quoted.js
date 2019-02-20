define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/quoted/index',
                    // add_url: 'merchant/quoted/add',
                    // edit_url: 'merchant/quoted/edit',
                    del_url: 'merchant/quoted/del',
                    multi_url: 'merchant/quoted/multi',
                    table: 'quoted_price',
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

                        {field: 'user.nickname', title: __('报价人的昵称')},
                        {field: 'user.mobile', title: __('报价人的手机号')},
                        {field: 'user.avatar', title: __('报价人的头像'), formatter: Table.api.formatter.image, operate: false},

                        {field: 'models_info.models_name', title: __('被报价的车型')},
                        {field: 'models_info.kilometres', title: __('车型公里数')},
                        {field: 'models_info.license_plate', title: __('车型车牌所在地')},
                        {field: 'models_info.parkingposition', title: __('车辆停放位置')},

                        {field: 'by_user.nickname', title: __('被报价人的昵称')},
                        {field: 'by_user.mobile', title: __('被报价人的手机号')},
                        {field: 'by_user.avatar', title: __('被报价人的头像'), formatter: Table.api.formatter.image, operate: false},

                        // {field: 'models_info_id', title: __('Models_info_id')},
                        // {field: 'buy_car_id', title: __('Buy_car_id')},
                        
                        {field: 'money', title: __('所报价格（元）'), operate:'BETWEEN'},
                        {field: 'is_see', title: __('是否查看'), formatter: Controller.api.formatter.see},
                        {field: 'quotationtime', title: __('Quotationtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'type', title: __('Type'), searchList: {"buy":__('Buy'),"sell":__('Sell')}, formatter: Table.api.formatter.normal},
        
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
            formatter: {
                see: function (value, row, index) {
                    
                    if (value == 1) {

                        return "<storng class='text-success'>" + "已查看" + "</storng>";
                   
                    }
                    if (value == 2) {

                        return "<storng class='text-danger'>" + "未查看" + "</storng>";
                   
                    }
                }
            }
        }
    };
    return Controller;
});