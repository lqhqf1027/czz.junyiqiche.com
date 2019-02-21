define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    // var goeasy = new GoEasy({
    //     appkey: 'BC-04084660ffb34fd692a9bd1a40d7b6c2'
    // });

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
                    url: 'merchant/quoted/saleCar',

                    toolbar: '#toolbar1',
                    pk: 'id',
                    sortName: 'id',
                    searchFormVisible: true,

                    columns: [
                        [
                            {checkbox: true},
                            {field: 'id', title: __('Id')},
                            {field: 'nickname', title: __('用户昵称')},
                            {field: 'avatar', title: __('用户头像'), formatter: Table.api.formatter.image, operate: false},
                            {field: 'mobile', title: __('手机号')},
                            {field: 'salecount', title: __('上架销售车辆台数')},
                            {field: 'quotecount', title: __('共收到报价次数')},
                            {field: 'operate', title: __('Operate'), table: saleCar, 
                                buttons: [

                                    {
                                        name: 'quote',
                                        text: '查看销售车型',
                                        title: __('查看销售车型'),
                                        icon: 'fa fa-share',
                                        classname: 'btn btn-xs btn-info btn-salequotedPrice',

                                    }

                                ],
                                events: Controller.api.operate, 
                                formatter: Controller.api.formatter.operate

                            }

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
                    url: 'merchant/quoted/buyCar',
                    toolbar: '#toolbar2',
                    pk: 'id',
                    sortName: 'id',
                    searchFormVisible: true,
                    columns: [
                        [
                            {checkbox: true},
                            {field: 'id', title: __('Id')},
                            {field: 'nickname', title: __('用户昵称')},
                            {field: 'avatar', title: __('用户头像'), formatter: Table.api.formatter.image, operate: false},
                            {field: 'mobile', title: __('手机号')},
                            {field: 'salecount', title: __('上架销售车辆台数')},
                            {field: 'quotecount', title: __('共收到报价次数')},
                            {field: 'operate', title: __('Operate'), table: buyCar, 
                                buttons: [

                                    {
                                        name: 'quote',
                                        text: '查看想买车型',
                                        title: __('查看想买车型'),
                                        icon: 'fa fa-share',
                                        classname: 'btn btn-xs btn-info btn-buyquotedPrice',
                                        url: 'merchant/quoted/buyquoted',

                                    }

                                ],
                                events: Controller.api.operate, 
                                formatter: Controller.api.formatter.operate
                            }

                        ]
                    ]
                });
                // 为表格2绑定事件
                Table.api.bindevent(buyCar);

            }


        },
        //商家在售----报价
        salequoted: function () {

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });
    
            var table = $("#table");
            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){};
            // 初始化表格
            table.bootstrapTable({
                url: 'merchant/quoted/salequoted',
                pk: 'id',
                sortName: 'id',
                toolbar: '#toolbar',
                columns: [
                    [
                        {field: 'id', title: __('Id'),operate:false},

                        {field: 'user.nickname', title: __('报价用户昵称')},
                        {field: 'user.avatar', title: __('报价用户头像')},
                        {field: 'user.mobile', title: __('报价用户手机')},

                        {field: 'money', title: __('报价价格（元）')},
                        {field: 'quotationtime', title: __('报价时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                    
                    ]
                ] 
                });
    
                // 为表格绑定事件
                Table.api.bindevent(table);

    
        },
        //有人想买----报价
        buyquoted: function () {

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });
    
            var table = $("#table");
            // $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){return "快速搜索:车型";};
            // 初始化表格
            table.bootstrapTable({
                url: 'merchant/quoted/buyquoted',
                pk: 'id',
                sortName: 'id',
                toolbar: '#toolbar',
                columns: [
                    [
                        {field: 'id', title: __('Id'),operate:false},

                        {field: 'user.nickname', title: __('报价用户昵称')},
                        {field: 'user.avatar', title: __('报价用户头像')},
                        {field: 'user.mobile', title: __('报价用户手机')},

                        {field: 'money', title: __('报价价格（元）')},
                        {field: 'quotationtime', title: __('报价时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                    
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
            operate:{
                /**
                 * 查看商家在售车型报价
                 * @param e
                 * @param value
                 * @param row
                 * @param index
                 */
                'click .btn-salequotedPrice': function (e, value, row, index) {
                    e.stopPropagation();
                    e.preventDefault();
                    var table = $(this).closest('table');
                    var options = table.bootstrapTable('getOptions');
                    var ids = row[options.pk];
                    row = $.extend({}, row ? row : {}, {ids: ids});
                    var url = 'merchant/quoted/salequoted';
                    Fast.api.open(Table.api.replaceurl(url, row, table), __('查看报价'), $(this).data() || {});
                },
                /**
                 * 查看有人买车车型报价
                 * @param e
                 * @param value
                 * @param row
                 * @param index
                 */
                'click .btn-buyquotedPrice': function (e, value, row, index) {
                    e.stopPropagation();
                    e.preventDefault();
                    var table = $(this).closest('table');
                    var options = table.bootstrapTable('getOptions');
                    var ids = row[options.pk];
                    row = $.extend({}, row ? row : {}, {ids: ids});
                    var url = 'merchant/quoted/buyquoted';
                    Fast.api.open(Table.api.replaceurl(url, row, table), __('查看报价'), $(this).data() || {});
                },
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
                    var classname = typeof this.classname !== 'undefined' ? this.classname : 'img-sm img-center';
                    var arr = value.split(',');
                    var html = [];
                    $.each(arr, function (i, value) {
                        value = value ? value : '/assets/img/blank.gif';
                        html.push('<a href="https://czz.junyiqiche.com' + value + '" target="_blank"><img class="' + classname + '" src="https://czz.junyiqiche.com' + value + '" /></a>');
                    });
                    return html.join(' ');
                },
    
            }
        }

    };
    return Controller;
});