define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'distribution/store/index',
                    add_url: 'distribution/store/add',
                    edit_url: 'distribution/store/edit',
                    del_url: 'distribution/store/del',
                    multi_url: 'distribution/store/multi',
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
                        {field: 'cities.shortname', title: __('所属城市')},
                        {field: 'store_name', title: __('Store_name')},
                        {field: 'user.name', title: __('店铺所属人')},
                        {field: 'level.partner_rank', title: __('店铺等级')},
                        {field: 'store_address', title: __('Store_address')},
                        {field: 'phone', title: __('Phone')},
                        {field: 'store_img', title: __('Store_img')},
                        {field: 'store_qrcode', title: __('Store_qrcode')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'longitude', title: __('Longitude')},
                        {field: 'latitude', title: __('Latitude')},
                        {field: 'invitation_code', title: __('Invitation_code')},
                        {field: 'store_description', title: __('Store_description')},
                        {field: 'statuss', title: __('Statuss'), searchList: {"normal":__('Normal'),"hidden":__('Hidden')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            $(document).on("click", "a.btn-channel", function () {
                $("#archivespanel").toggleClass("col-md-9", $("#channelbar").hasClass("hidden"));
                $("#channelbar").toggleClass("hidden");
            });

            require(['jstree'], function () {
                //全选和展开
                $(document).on("click", "#checkall", function () {
                    $("#channeltree").jstree($(this).prop("checked") ? "check_all" : "uncheck_all");
                });
                $(document).on("click", "#expandall", function () {
                    $("#channeltree").jstree($(this).prop("checked") ? "open_all" : "close_all");
                });
                $('#channeltree').on("changed.jstree", function (e, data) {
                    console.log(data);
                    console.log(data.selected);
                    var options = table.bootstrapTable('getOptions');
                    options.pageNumber = 1;
                    options.queryParams = function (params) {
                        params.filter = JSON.stringify(data.selected.length > 0 ? {cities_id: data.selected.join(",")} : {});
                        params.op = JSON.stringify(data.selected.length > 0 ? {cities_id: 'in'} : {});
                        return params;
                    };
                    table.bootstrapTable('refresh', {});
                    return false;
                });
                $('#channeltree').jstree({
                    "themes": {
                        "stripes": true
                    },
                    "checkbox": {
                        "keep_selected_style": false,
                    },
                    "types": {
                        "channel": {
                            "icon": "fa fa-th",
                        },
                        "list": {
                            "icon": "fa fa-list",
                        },
                        "link": {
                            "icon": "fa fa-link",
                        },
                        "disabled": {
                            "check_node": false,
                            "uncheck_node": false
                        }
                    },
                    'plugins': ["types", "checkbox"],
                    "core": {
                        "multiple": true,
                        'check_callback': true,
                        "data": Config.storeList
                    }
                });
            });

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