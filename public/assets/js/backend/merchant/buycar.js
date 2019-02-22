define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/buycar/index',
                    // add_url: 'merchant/buycar/add',
                    // edit_url: 'merchant/buycar/edit',
                    del_url: 'merchant/buycar/del',
                    multi_url: 'merchant/buycar/multi',
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
                        {field: 'id', title: __('Id')},
                        {field: 'brand.name', title: __('Brand_name')},
                        {field: 'models_name', title: __('Models_name')},
                        {field: 'store.store_name', title: __('Store.store_name')},
                        {field: 'guide_price', title: __('心理价（元）')},
                        {field: 'phone', title: __('Phone')},
                        {field: 'parkingposition', title: __('期望车辆所在地')},
                        {field: 'browse_volume', title: __('浏览量'), operate:'BETWEEN'},
                        {field: 'shelfismenu', title: __('Shelfismenu'), formatter: Controller.api.formatter.toggle},
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
                        
                    if (row.shelfismenu ==  1) {
                        return "<strong class='text-success'>上架中</strong>";
                        
                    }
                    else {
                        return "<strong class='text-danger'>下架中</strong>";
                    }
                        
                },

            }
        }
    };
    return Controller;
});