define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/sellcar/index',
                    add_url: 'merchant/sellcar/add',
                    edit_url: 'merchant/sellcar/edit',
                    del_url: 'merchant/sellcar/del',
                    multi_url: 'merchant/sellcar/multi',
                    table: 'models_info',
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
                        {field: 'licenseplatenumber', title: __('Licenseplatenumber')},
                        {field: 'brand.name', title: __('Brand_name')},
                        {field: 'models_name', title: __('Models_name')},
                        {field: 'store.store_name', title: __('Store.store_name')},
                        {field: 'kilometres', title: __('Kilometres'), operate:'BETWEEN'},
                        {field: 'parkingposition', title: __('Parkingposition')},
                        {field: 'phone', title: __('Phone')},
                        {field: 'modelsimages', title: __('Modelsimages'), formatter: Table.api.formatter.images},
                        {field: 'guide_price', title: __('Guide_price'), operate:'BETWEEN'},
                        {field: 'browse_volume', title: __('浏览量')},
                        {field: 'car_licensetime', title: __('Car_licensetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'emission_standard', title: __('Emission_standard')},
                        {field: 'emission_load', title: __('Emission_load')},
                        {field: 'speed_changing_box', title: __('Speed_changing_box')},
                        {field: 'the_transfer_record', title: __('The_transfer_record')},
                        {field: 'license_plate', title: __('License_plate')},
                        {field: 'factorytime', title: __('Factorytime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'store_description', title: __('Store_description')},
                        {field: 'shelfismenu', title: __('Shelfismenu'), formatter: Controller.api.formatter.toggle,searchList:{"1":"是","0":"否"}},
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
                $(document).on('click', "input[name='row[shelfismenu]']", function () {
                    var name = $("input[name='row[name]']");
                    name.prop("placeholder", $(this).val() == 1 ? name.data("placeholder-menu") : name.data("placeholder-node"));
                });
                $("input[name='row[shelfismenu]']:checked").trigger("click");
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                toggle: function (value, row, index) {
                    
                    var color = typeof this.color !== 'undefined' ? this.color : 'success';
                    var yes = typeof this.yes !== 'undefined' ? this.yes : 1;
                    var no = typeof this.no !== 'undefined' ? this.no : 0;
                    return "<a href='javascript:;' data-toggle='tooltip' title='" + __('Click to toggle') + "' class='btn-change' data-id='"
                        + row.id + "' data-params='" + this.field + "=" + (value ? no : yes) + "'><i class='fa fa-toggle-on " + (value == yes ? 'text-' + color : 'fa-flip-horizontal text-gray') + " fa-2x'></i></a>";
                   
                }

            }
        }
    };
    return Controller;
});