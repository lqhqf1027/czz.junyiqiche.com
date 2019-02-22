define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/store/index',
                    add_url: 'merchant/store/add',
                    edit_url: 'merchant/store/edit',
                    del_url: 'merchant/store/del',
                    multi_url: 'merchant/store/multi',
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
                        {field: 'cities_name', title: __('Cities_name')},
                        {field: 'store_name', title: __('Store_name')},
                        {field: 'storelevel.partner_rank', title: __('店铺等级')},
                        {field: 'user.name', title: __('店铺所有人姓名')},
                        {field: 'store_address', title: __('Store_address')},
                        {field: 'phone', title: __('Phone')},
                        {field: 'statuss', title: __('Statuss'), searchList: {"normal":__('Normal'),"hidden":__('Hidden')}, formatter: Table.api.formatter.normal},
                        {field: 'store_img', title: __('Store_img')},
                        {field: 'store_qrcode', title: __('Store_qrcode')},
                        {field: 'invitation_code', title: __('Invitation_code')},
                        {field: 'store_description', title: __('Store_description')},
                        {field: 'main_camp', title: __('Main_camp')},
                        {
                            field: 'recommend',
                            title: __('是否为推荐'),
                            events: Controller.api.events.operate,
                            formatter: Controller.api.formatter.toggle, searchList: {"1": "是", "0": "否"},
                        },
                        // {field: 'auditstatus', title: __('审核状态'), searchList: {"audit_failed":__('Audit_failed'),"pass_the_audit":__('Pass_the_audit'),"wait_the_review":__('Wait_the_review')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', title: __('Operate'), table: table, 
                            buttons: [
                                /**
                                 * 删除 
                                 */
                                {
                                    icon: 'fa fa-trash', name: 'del', icon: 'fa fa-trash', extend: 'data-toggle="tooltip"', title: __('Del'), classname: 'btn btn-xs btn-danger btn-delone',
                                       
                                    hidden: function (row) {
                                        if (row.auditstatus == 'wait_the_review') {
                                            return false;
                                        }
                                        else if (row.auditstatus == 'pass_the_audit') {

                                            return true;
                                        }
                                        else if (row.auditstatus == 'audit_failed') {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 审核店铺
                                 */
                                {
                                    name: 'wait_the_review', text: '审核店铺', icon: 'fa fa-check-square-o', extend: 'data-toggle="tooltip"', 
                                    title: __('审核店铺'), classname: 'btn btn-xs btn-info btn-auditResult',
                                        
                                    hidden: function (row, value, index) {
                                        if (row.auditstatus == 'wait_the_review') {
                                            return false;
                                        }
                                        else if (row.auditstatus == 'pass_the_audit') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'audit_failed') {
                                            return true;
                                        }
                                    },
                                },
                                /**
                                 * 审核店铺-----通过
                                 */
                                {
                                    name: 'pass_the_audit', text: '审核店铺通过',
                                    title: __('审核店铺通过'), classname: 'btn btn-xs btn-success',
                                        
                                    hidden: function (row, value, index) {
                                        if (row.auditstatus == 'pass_the_audit') {
                                            return false;
                                        }
                                        else if (row.auditstatus == 'wait_the_review') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'audit_failed') {
                                            return true;
                                        }
                                    },
                                },
                                /**
                                 * 审核店铺-----未通过---待修改资料
                                 */
                                {
                                    name: 'information_audit', text: '审核店铺未通过，等待修改资料',
                                    title: __('审核店铺未通过，等待修改资料'), classname: 'btn btn-xs btn-danger',
                                        
                                    hidden: function (row, value, index) {
                                        if (row.auditstatus == 'audit_failed') {
                                            return false;
                                        }
                                        else if (row.auditstatus == 'pass_the_audit') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'wait_the_review') {
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
                //推荐
                $(document).on('click', "input[name='row[recommend]']", function () {
                    var name = $("input[name='row[name]']");
                    name.prop("placeholder", $(this).val() == 1 ? name.data("placeholder-menu") : name.data("placeholder-node"));
                });
                $("input[name='row[recommend]']:checked").trigger("click");

                Form.api.bindevent($("form[role=form]"));
            },
            events: {
                operate: {
                    //编辑
                    // 'click .btn-editone': function (e, value, row, index) {
                    //     e.stopPropagation();
                    //     e.preventDefault();
                    //     var table = $(this).closest('table');
                    //     var options = table.bootstrapTable('getOptions');
                    //     var ids = row[options.pk];
                    //     row = $.extend({}, row ? row : {}, {ids: ids});
                    //     var url = options.extend.edit_url;
                    //     Fast.api.open(Table.api.replaceurl(url, row, table), __('Edit'), $(this).data() || {});
                    // },

                    /**
                     * 删除按钮
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-delone': function (e, value, row, index) {  /**删除按钮 */

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
                            __('Are you sure you want to delete this item?'),
                            { icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true },
                            function (index) {
                                var table = $(that).closest('table');
                                var options = table.bootstrapTable('getOptions');
                                Table.api.multi("del", row[options.pk], table, that);
                                Layer.close(index);
                            }
                        );
                    },
                    /**
                     * 店铺审核
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-auditResult': function (e, value, row, index) {
                        $(".btn-auditResult").data("area", ["95%", "95%"]);
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = 'merchant/store/auditResult';
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('店铺审核'), $(this).data() || {})

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
                },
                /**
                 * 是否推荐
                 * @param value
                 * @param row
                 * @param index
                 * @returns {string}
                 */
                toggle: function (value, row, index) {

                    var color = typeof this.color !== 'undefined' ? this.color : 'success';
                    var yes = typeof this.yes !== 'undefined' ? this.yes : 1;
                    var no = typeof this.no !== 'undefined' ? this.no : 0;
                    return "<a href='javascript:;' data-toggle='tooltip' title='" + __('Click to toggle') + "' class='btn-change' data-id='"
                        + row.id + "' data-params='" + this.field + "=" + (value ? no : yes) + "'><i class='fa fa-toggle-on " + (value == yes ? 'text-' + color : 'fa-flip-horizontal text-gray') + " fa-2x'></i></a>";

                   
                }, 
            },
        }
    };
    return Controller;
});