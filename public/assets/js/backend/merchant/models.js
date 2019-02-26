define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {

            // 初始化表格参数配置
            Table.api.init({});

            //绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));
                if (panel.size() > 0) {
                    Controller.table[panel.attr("id")].call(this);
                    $(this).on('click', function (e) {
                        $($(this).attr("href")).find(".btn-refresh").trigger("click");
                    });
                }
                //移除绑定的事件
                $(this).unbind('shown.bs.tab');
            });

            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");

            $('ul.nav-tabs li a[data-toggle="tab"]').each(function () {
                $(this).trigger("shown.bs.tab");
            });

        },

        table: {
            /**
             * 商家在售
             */
            sale_car: function () {
                // 表格1
                var saleCar = $("#saleCar");
                saleCar.on('load-success.bs.table', function (e, data) {

                })
                saleCar.on('post-body.bs.table', function (e, settings, json, xhr) {
                   
                });
                // 初始化表格
                saleCar.bootstrapTable({
                    url: 'merchant/models/saleCar',

                    toolbar: '#toolbar1',
                    pk: 'id',
                    sortName: 'id',

                    columns: [
                        [
                            {checkbox: true},
                            {field: 'id', title: __('Id')},
                            {field: 'brand.name', title: __('品牌名称')},
                            {field: 'models_name', title: __('车型名称')},
                            {field: 'store.store_name', title: __('店铺名称')},
                            {field: 'kilometres', title: __('公里数'), operate:'BETWEEN'},
                            {field: 'parkingposition', title: __('车辆所在地')},
                            {field: 'phone', title: __('手机号')},
                            {field: 'modelsimages', title: __('车型亮点图'), formatter: Controller.api.formatter.images},
                            {field: 'guide_price', title: __('批发一口价（元）'), operate:'BETWEEN'},
                            {field: 'emission_standard', title: __('过户次数')},
                            {field: 'browse_volume', title: __('浏览量')},
                            {field: 'car_licensetime', title: __('上牌时间'), operate:'RANGE', addclass:'datetimerange', formatter: Controller.api.formatter.datetime},
                            {field: 'factorytime', title: __('出厂时间'), operate:'RANGE', addclass:'datetimerange', formatter: Controller.api.formatter.datetime},
                            {field: 'shelfismenu', title: __('是否上下架'), formatter: Controller.api.formatter.toggle},
                            {field: 'createtime', title: __('创建时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            {field: 'updatetime', title: __('更新时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'operate', title: __('Operate'), table: saleCar, events: Table.api.events.operate, formatter: Table.api.formatter.operate}

                        ]
                    ]
                });
                // 为表格1绑定事件
                Table.api.bindevent(saleCar);

            },
            /**
             * 有人想买
             */
            buy_car: function () {
                // 表格2
                var buyCar = $("#buyCar");
                buyCar.on('load-success.bs.table', function (e, data) {

                })
                buyCar.on('post-body.bs.table', function (e, settings, json, xhr) {
                   
                });
                // 初始化表格
                buyCar.bootstrapTable({
                    url: 'merchant/models/buyCar',
                    toolbar: '#toolbar2',
                    pk: 'id',
                    sortName: 'id',
                    columns: [
                        [
                            {checkbox: true},
                            {field: 'id', title: __('Id')},
                            {field: 'store.store_name', title: __('店铺名称')},
                            {field: 'brand.name', title: __('品牌名称')},
                            {field: 'models_name', title: __('车型名称')},
                            {field: 'phone', title: __('手机号')},
                              
                            {field: 'browse_volume', title: __('浏览量'), operate:'BETWEEN'},
                            {field: 'parkingposition', title: __('期望车辆所在地')},
                            {field: 'guide_price', title: __('心理价（元）'), operate:'BETWEEN'},
                            {field: 'shelfismenu', title: __('是否上下架'), formatter: Controller.api.formatter.toggle},
                            {field: 'createtime', title: __('创建时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            {field: 'updatetime', title: __('更新时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'operate', title: __('Operate'), table: buyCar, events: Table.api.events.operate, formatter: Table.api.formatter.operate}

                        ]
                    ]
                });
                // 为表格2绑定事件
                Table.api.bindevent(buyCar);

            }

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
                operate: function (value, row, index) {

                    var table = this.table;
                    // 操作配置
                    var options = table ? table.bootstrapTable('getOptions') : {};
                    // 默认按钮组
                    var buttons = $.extend([], this.buttons || []);


                    return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                },
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
                    var arr = value.split(',');
                    var html = [];
                    $.each(arr, function (i, value) {
                        value = value ? value : '/assets/img/blank.gif';
                        html.push('<a href="https://czz.junyiqiche.com' + value + '" target="_blank"><img class="img-sm img-center" src="https://czz.junyiqiche.com' + value + '" /></a>');
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