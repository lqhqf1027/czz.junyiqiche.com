define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/withdraw/index',
                    // add_url: 'merchant/withdraw/add',
                    // edit_url: 'merchant/withdraw/edit',
                    del_url: 'merchant/withdraw/del',
                    multi_url: 'merchant/withdraw/multi',
                    table: 'withdrawals_record',
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

                        {field: 'store.store_name', title: __('Store.store_name')},
                        {field: 'store.phone', title: __('Store.phone')},
                        {field: 'store.bank_card', title: __('Store.bank_card')},
                        {field: 'store.real_name', title: __('Store.real_name')},

                        {field: 'withdrawal_amount', title: __('Withdrawal_amount'), operate:'BETWEEN'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('提现状态'), searchList: {"cash_in":__('提现中'),"has_been_presented":__('提现成功')}, formatter: Table.api.formatter.status},
                      
                        {field: 'operate', title: __('Operate'), table: table, 
                            buttons: [
                                /**
                                 * 是否确认已经打款
                                 */
                                {
                                    name: 'cash_in',
                                    text: '确认已经打款',
                                    icon: 'fa fa-share',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('确认已经打款'),
                                    classname: 'btn btn-xs btn-success btn-withdraw_money',

                                    hidden: function (row) {
                                        if (row.status == 'cash_in') {
                                            return false;
                                        }
                                        else if (row.status == 'has_been_presented') {

                                            return true;
                                        }
                                        
                                    },

                                },
                                /**
                                 * 已经打款，提现成功
                                 */
                                {
                                    name: 'has_been_presented',
                                    text: '已经打款，提现成功',
                                    icon: 'fa fa-check-square',
                                    extend: 'data-toggle="tooltip"',
                                    title: __('已经打款，提现成功'),
                                    classname: 'btn btn-xs btn-success',

                                    hidden: function (row) {
                                        if (row.status == 'has_been_presented') {
                                            return false;
                                        }
                                        else if (row.status == 'cash_in') {

                                            return true;
                                        }
                                        
                                    },

                                },
                            ],
                            
                            events: Controller.api.events.operate, 
                            formatter: Controller.api.formatter.operate
                        }
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
            events: {
                operate: {
                    /**
                     * 是否确认已经打款
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-withdraw_money': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var that = this;
                        var top = $(that).offset().top - $(window).scrollTop();
                        var left = $(that).offset().left - $(window).scrollLeft() - 260;
                        if (top + 154 > $(window).height()) {
                            top = top - 154;
                        }
                        if ($(window).width() < 480) {
                            top = left = undefined;
                        }
                        Layer.confirm(
                            __('是否确认已经打款?'),
                            {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                            function (index) {
                                var table = $(that).closest('table');
                                var options = table.bootstrapTable('getOptions');
                                Fast.api.ajax({

                                    url: 'merchant/withdraw/money',
                                    data: {id: row[options.pk]}

                                }, function (data, ret) {

                                    Toastr.success('操作成功');
                                    Layer.close(index);
                                    table.bootstrapTable('refresh');
                                    return false;
                                }, function (data, ret) {
                                    //失败的回调
                                    Toastr.success(ret.msg);

                                    return false;
                                });
                            }
                        );
                    },

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
                }

            },
        }
    };
    return Controller;
});