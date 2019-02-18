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
             * 我的报价
             */
            my_quote: function () {
                // 表格1
                var myQuote = $("#myQuote");
                myQuote.on('load-success.bs.table', function (e, data) {

                })
                myQuote.on('post-body.bs.table', function (e, settings, json, xhr) {
                    
                });
                // 初始化表格
                myQuote.bootstrapTable({
                    url: 'merchant/quoted/myQuote',

                    toolbar: '#toolbar1',
                    pk: 'id',
                    sortName: 'id',
                    searchFormVisible: true,

                    columns: [
                        [
                            {checkbox: true},
                            {field: 'id', title: Fast.lang('Id'),operate:false},
                            
                            {field: 'user.nickname', title: __('报价人昵称')},

                            {field: 'buycar_model.models_name', title: __('报价车型')},

                            {field: 'buycar_model.parkingposition', title: __('车辆停放位置')},

                            {field: 'money', title: __('Money')},
                            
                            {
                                field: 'quotationtime',
                                title: __('Quotationtime'),
                                operate: false,
                                formatter: Table.api.formatter.datetime
                            },

                        ]
                    ]
                });
                // 为表格1绑定事件
                Table.api.bindevent(myQuote);


            },
            /**
             * 收到报价
             */
            offer_received: function () {
                // 表格2
                var offerReceived = $("#offerReceived");
                offerReceived.on('post-body.bs.table', function (e, settings, json, xhr) {
                    
                });
                // 初始化表格
                offerReceived.bootstrapTable({
                    url: 'merchant/quoted/offerReceived',
                   
                    toolbar: '#toolbar2',
                    pk: 'id',
                    sortName: 'id',
                    searchFormVisible: true,
                    columns: [
                        [
                            {checkbox: true},
                            {field: 'id', title: Fast.lang('Id'),operate:false},
                            
                            {field: 'user.nickname', title: __('报价人昵称')},

                            {field: 'models_info.models_name', title: __('报价车型')},

                            {field: 'models_info.parkingposition', title: __('车辆停放位置')},

                            {field: 'money', title: __('Money')},
                            
                            {
                                field: 'quotationtime',
                                title: __('Quotationtime'),
                                operate: false,
                                formatter: Table.api.formatter.datetime
                            },

                        ]
                    ]
                });
                // 为表格2绑定事件
                Table.api.bindevent(offerReceived);

                offerReceived.on('load-success.bs.table', function (e, data) {
                    
                })

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
                
                Form.api.bindevent($("form[role=form]"));
            }
        }

    };
    return Controller;
});