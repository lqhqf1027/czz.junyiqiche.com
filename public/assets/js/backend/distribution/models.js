define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'distribution/models/index',
                    add_url: 'distribution/models/add',
                    edit_url: 'distribution/models/edit',
                    del_url: 'distribution/models/del',
                    multi_url: 'distribution/models/multi',
                    table: 'models_info',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'licenseplatenumber', title: __('Licenseplatenumber')},
                        {field: 'license_plate', title: __('License_plate')},
                        {field: 'car_licensedate', title: __('Car_licensedate')},
                        {field: 'models_name', title: __('Models_name')},
                        {field: 'kilometres', title: __('Kilometres'), operate:'BETWEEN'},
                        {field: 'store.store_name', title: __('Store.store_name')},
                        {field: 'newpayment', title: __('Newpayment')},
                        {field: 'monthlypaymen', title: __('Monthlypaymen')},
                        {field: 'periods', title: __('Periods')},
                        {field: 'totalprices', title: __('Totalprices')},
                        {field: 'bond', title: __('Bond'), operate:'BETWEEN'},
                        {field: 'tailmoney', title: __('Tailmoney'), operate:'BETWEEN'},
                        {field: 'drivinglicenseimages', title: __('Drivinglicenseimages'), formatter: Table.api.formatter.images},
                        {field: 'vin', title: __('Vin')},
                        {field: 'engine_number', title: __('Engine_number')},
                        {field: 'modelsimages', title: __('Modelsimages'), formatter: Table.api.formatter.images},
                        {field: 'guide_price', title: __('Guide_price'), operate:'BETWEEN'},
                        {field: 'models_main_images', title: __('Models_main_images'), formatter: Table.api.formatter.images},
                        
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