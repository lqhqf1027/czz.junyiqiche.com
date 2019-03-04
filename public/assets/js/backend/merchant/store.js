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
                        {field: 'store_name', title: __('Store_name')},
                        {field: 'store_address', title: __('店铺地址'),formatter:function (v,r,i) {
                                return r.cities_name+r.store_address
                        }},
                        {field: 'storelevel.partner_rank', title: __('店铺等级')},
                        {field: 'user.name', title: __('店铺所有人微信昵称')},
                        {field: 'phone', title: __('Phone')},
                        // {field: 'store_img', title: __('Store_img'), formatter: Controller.api.formatter.images},
                        // {field: 'user.invitation_code_img', title: __('Store_qrcode'), formatter: Controller.api.formatter.invitation_code_img},
                        // {field: 'count', title: __('邀请店铺数量'), formatter: Controller.api.formatter.count},
                        // {field: 'user.invite_code', title: __('Invitation_code'), formatter: Controller.api.formatter.invite_code},
                        // {field: 'main_camp', title: __('Main_camp')},
                        {
                            field: 'recommend',
                            title: __('是否为推荐'),
                            events: Controller.api.events.operate,
                            formatter: Controller.api.formatter.toggle, searchList: {"1": "是", "0": "否"},
                        },
                        {field: 'statuss', title: __('Statuss'), searchList: {"normal":__('Normal'),"hidden":__('Hidden')}, formatter: Table.api.formatter.normal},
                        // {field: 'auditstatus', title: __('审核状态'), searchList: {"audit_failed":__('Audit_failed'),"pass_the_audit":__('Pass_the_audit'),"wait_the_review":__('Wait_the_review')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,datetimeFormat: "YYYY-MM-DD",},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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
                                        else if (row.auditstatus == 'paid_the_money') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'in_the_review') {
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
                                        else if (row.auditstatus == 'paid_the_money') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'in_the_review') {
                                            return true;
                                        }
                                    },
                                },
                                /**
                                 * 店铺审核中
                                 */
                                {
                                    name: 'in_the_review', text: '店铺审核中',
                                    title: __('店铺审核中'), classname: 'btn btn-xs btn-success',
                                        
                                    hidden: function (row, value, index) {
                                        if (row.auditstatus == 'in_the_review') {
                                            return false;
                                        }
                                        else if (row.auditstatus == 'wait_the_review') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'audit_failed') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'pass_the_audit') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'paid_the_money') {
                                            return true;
                                        }
                                    },
                                },
                                /**
                                 * 审核店铺-----通过待支付
                                 */
                                {
                                    name: 'pass_the_audit', text: '审核店铺通过，待支付',
                                    title: __('审核店铺通过，待支付'), classname: 'btn btn-xs btn-success',
                                        
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
                                        else if (row.auditstatus == 'paid_the_money') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'in_the_review') {
                                            return true;
                                        }
                                    },
                                },
                                /**
                                 * 审核店铺-----已支付
                                 */
                                {
                                    name: 'paid_the_money', text: '已支付',
                                    title: __('已支付'), classname: 'btn btn-xs btn-success',
                                        
                                    hidden: function (row, value, index) {
                                        if (row.auditstatus == 'paid_the_money') {
                                            return false;
                                        }
                                        else if (row.auditstatus == 'wait_the_review') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'audit_failed') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'pass_the_audit') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'in_the_review') {
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
                                        else if (row.auditstatus == 'paid_the_money') {
                                            return true;
                                        }
                                        else if (row.auditstatus == 'in_the_review') {
                                            return true;
                                        }
                                    },
                                },
                                /**
                                 * 查看店铺的推广
                                 */
                                {
                                    name: 'store_promotion',
                                    text: '查看店铺推广',
                                    icon: 'fa fa-eye', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('查看店铺推广'), 
                                    classname: 'btn btn-xs btn-success btn-store_promotion',
                                    hidden: function (row, value, index) {
                                        if (row.count != 0) {
                                            return false;
                                        }
                                        else if (row.count == 0) {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 暂无店铺推广可查看
                                 */
                                {
                                    name: 'store_promotion',
                                    text: '暂无店铺推广可查看',
                                    icon: 'fa fa-eye-slash', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('暂无店铺推广可查看'), 
                                    classname: 'btn btn-xs btn-danger',
                                    hidden: function (row, value, index) {
                                        if (row.count == 0) {
                                            return false;
                                        }
                                        else if (row.count != 0) {
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
        
        //店铺推广
        storepromotion: function () {

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
                url: 'merchant/store/storepromotion',
                pk: 'id',
                sortName: 'id',
                toolbar: '#toolbar',
                searchFormVisible: true,
                queryParams:function (params) {
                    params.filter = JSON.stringify({'level_store_id': Config.level_store_id});
                    params.op = JSON.stringify({'level_store_id': 'in'});
                    return {
                        search: params.search,
                        sort: params.sort,
                        order: params.order,
                        filter: params.filter,
                        op: params.op,
                        offset: params.offset,
                        limit: params.limit
                    }
                },
                columns: [
                    [
                        {field: 'store.id', title: __('Id')},

                        {field: 'store.store_name', title: __('一级店铺名称')},
                        {field: 'earnings', title: __('一级店铺收益（元）')},
                        {field: 'count', title: __('邀请店铺数量'), formatter: Controller.api.formatter.count},
                        {
                            field: 'operate', title: __('Operate'), table: table, 
                            buttons: [
                                /**
                                 * 查看店铺的推广
                                 */
                                {
                                    name: 'store_promotion',
                                    text: '查看店铺推广',
                                    icon: 'fa fa-eye', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('查看店铺推广'), 
                                    classname: 'btn btn-xs btn-success btn-levelstore_promotion',
                                    hidden: function (row, value, index) {
                                        if (row.count != 0) {
                                            return false;
                                        }
                                        else if (row.count == 0) {
                                            return true;
                                        }
                                    },

                                },
                                /**
                                 * 暂无店铺推广可查看
                                 */
                                {
                                    name: 'store_promotion',
                                    text: '暂无店铺推广可查看',
                                    icon: 'fa fa-eye-slash', 
                                    extend: 'data-toggle="tooltip"', 
                                    title: __('暂无店铺推广可查看'), 
                                    classname: 'btn btn-xs btn-danger',
                                    hidden: function (row, value, index) {
                                        if (row.count == 0) {
                                            return false;
                                        }
                                        else if (row.count != 0) {
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
        //下级店铺推广
        levelstorepromotion: function () {

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
                url: 'merchant/store/levelstorepromotion',
                pk: 'id',
                sortName: 'id',
                toolbar: '#toolbar',
                searchFormVisible: true,
                queryParams:function (params) {
                    params.filter = JSON.stringify({'level_store_id': Config.level_store_ids});
                    params.op = JSON.stringify({'level_store_id': 'in'});
                    return {
                        search: params.search,
                        sort: params.sort,
                        order: params.order,
                        filter: params.filter,
                        op: params.op,
                        offset: params.offset,
                        limit: params.limit
                    }
                },
                columns: [
                    [
                        {field: 'store.id', title: __('Id'),operate:false},

                        {field: 'store.store_name', title: __('二级店铺名称')},
                        {field: 'second_earnings', title: __('二级店铺收益（元）')},
                    
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
                        $(".btn-auditResult").data("area", ["40%", "95%"]);
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = 'merchant/store/auditResult';
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('店铺审核'), $(this).data() || {})

                    },
                    /**
                     * 查看店铺推广
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-store_promotion': function (e, value, row, index) {
                        $(".btn-store_promotion").data("area", ["95%", "95%"]);
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = 'merchant/store/storepromotion';
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('查看店铺推广'), $(this).data() || {})

                    },
                    /**
                     * 查看下级店铺推广
                     * @param e
                     * @param value
                     * @param row
                     * @param index
                     */
                    'click .btn-levelstore_promotion': function (e, value, row, index) {
                        $(".btn-levelstore_promotion").data("area", ["95%", "95%"]);
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = 'merchant/store/levelstorepromotion';
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('查看店铺推广'), $(this).data() || {})

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
                count: function (value, row, index) {

                    return '<strong class="text-success">'+ value +'</strong>';

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
                invite_code: function (value, row, index) {
                    if (row.auditstatus == 'paid_the_money') {
                        return row.user.invite_code;
                    }
                },
                invitation_code_img: function (value, row, index) {
                    if (row.auditstatus == 'paid_the_money') {
                        value = value ? value : '/assets/img/blank.gif';
                        return '<a href="https://czz.junyiqiche.com' + value + '" target="_blank"><img class="img-sm img-center" src="https://czz.junyiqiche.com' + value + '" /></a>';
                    
                    }
                },
            },
        }
    };
    return Controller;
});