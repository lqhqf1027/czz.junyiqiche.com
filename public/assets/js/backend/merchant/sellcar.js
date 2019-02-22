define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/sellcar/index',
                    // add_url: 'merchant/sellcar/add',
                    // edit_url: 'merchant/sellcar/edit',
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
                        {field: 'brand.name', title: __('Brand_name')},
                        {field: 'models_name', title: __('Models_name')},
                        {field: 'store.store_name', title: __('Store.store_name')},
                        {field: 'kilometres', title: __('Kilometres'), operate:'BETWEEN'},
                        {field: 'parkingposition', title: __('车辆所在地')},
                        {field: 'phone', title: __('Phone')},
                        {field: 'modelsimages', title: __('Modelsimages'), formatter: Controller.api.formatter.images},
                        {field: 'guide_price', title: __('Guide_price'), operate:'BETWEEN'},
                        {field: 'emission_standard', title: __('过户次数')},
                        {field: 'browse_volume', title: __('浏览量')},
                        {field: 'car_licensetime', title: __('Car_licensetime'), operate:'RANGE', addclass:'datetimerange', formatter: Controller.api.formatter.datetime},
                        {field: 'factorytime', title: __('Factorytime'), operate:'RANGE', addclass:'datetimerange', formatter: Controller.api.formatter.datetime},
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

            }
        }
    };
    return Controller;
});