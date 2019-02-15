define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/clue/index',
                    add_url: 'merchant/clue/add',
                    edit_url: 'merchant/clue/edit',
                    del_url: 'merchant/clue/del',
                    multi_url: 'merchant/clue/multi',
                    table: 'clue',
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
                        {field: 'store.store_name', title: __('提供线索店铺名')},
                        {field: 'licenseplatenumber', title: __('Licenseplatenumber')},
                        {field: 'brand.name', title: __('Brand_name')},
                        {field: 'models_name', title: __('Models_name')},
                        {field: 'phone', title: __('Phone')},
                        {field: 'kilometres', title: __('Kilometres'), operate:'BETWEEN'},
                        {field: 'browse_volume', title: __('浏览量'), operate:'BETWEEN'},
                        {field: 'parkingposition', title: __('Parkingposition')},
                        {field: 'guide_price', title: __('Guide_price'), operate:'BETWEEN'},
                        {field: 'car_licensedate', title: __('Car_licensedate')},
                        {field: 'emission_standard', title: __('Emission_standard')},
                        {field: 'emission_load', title: __('Emission_load')},
                        {field: 'license_plate', title: __('License_plate')},
                        {field: 'factorydata', title: __('Factorydata')},
                        {field: 'store_description', title: __('Store_description')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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