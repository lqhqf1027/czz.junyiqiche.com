define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/automotiveinformation/index',
                    add_url: 'merchant/automotiveinformation/add',
                    edit_url: 'merchant/automotiveinformation/edit',
                    del_url: 'merchant/automotiveinformation/del',
                    multi_url: 'merchant/automotiveinformation/multi',
                    table: 'automotive_information',
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
                        {field: 'categories.categories_name', title: __('所属类别')},
                        {field: 'title', title: __('Title')},
                        {field: 'author', title: __('Author')},
                        {field: 'coverimage', title: __('Coverimage'), formatter: Table.api.formatter.image},
                        {field: 'browse_volume', title: __('Browse_volume')},
                        {field: 'createtime', title: __('发表时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), searchList: {"normal":__('Normal'),"hidden":__('Hidden')}, formatter: Table.api.formatter.status},
                        {field: 'operate', title: __('Operate'), table: table, 
                            buttons: [
                                /**
                                 * 编辑
                                 */
                                {
                                    name: 'edit',
                                    text: '编辑',
                                    icon: 'fa fa-pencil', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('edit'), 
                                    classname: 'btn btn-xs btn-success btn-editone',

                                },
                                /**
                                 * 删除
                                 */
                                {
                                    name: 'del',
                                    text: '删除',
                                    icon: 'fa fa-trash', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('del'), 
                                    classname: 'btn btn-xs btn-danger  btn-delone',
                        
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

            table.on('load-success.bs.table', function (e, data) {
                $(".btn-add").data("area", ["70%", "90%"]);
            })
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
                     * 编辑按钮
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-editone': function (e, value, row, index) {
                        $(".btn-editone").data("area", ["70%", "90%"]);
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = options.extend.edit_url;
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('Edit'), $(this).data() || {});
                    },
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