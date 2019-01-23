define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'distribution/store/index',
                    add_url: 'distribution/store/add',
                    edit_url: 'distribution/store/edit',
                    del_url: 'distribution/store/del',
                    multi_url: 'distribution/store/multi',
                    table: 'company_store',
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
                        {field: 'cities_id', title: __('Cities_id')},
                        {field: 'store_name', title: __('Store_name')},
                        {field: 'store_address', title: __('Store_address')},
                        {field: 'phone', title: __('Phone')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'statuss', title: __('Statuss'), searchList: {"normal":__('Normal'),"hidden":__('Hidden')}, formatter: Table.api.formatter.normal},
                        {field: 'store_img', title: __('Store_img')},
                        {field: 'store_qrcode', title: __('Store_qrcode')},
                        {field: 'longitude', title: __('Longitude')},
                        {field: 'latitude', title: __('Latitude')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'invitation_code', title: __('Invitation_code')},
                        {field: 'level_id', title: __('Level_id')},
                        {field: 'store_description', title: __('Store_description')},
                        {field: 'store_user_id', title: __('Store_user_id')},
                        {field: 'cities.shortname', title: __('Cities.shortname')},
                        {field: 'level.partner_rank', title: __('Level.partner_rank')},
                        {field: 'user.name', title: __('User.name')},
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
            }
        }
    };
    return Controller;
});