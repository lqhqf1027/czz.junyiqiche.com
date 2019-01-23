define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'distribution/models/index',
                    add_url: 'distribution/models/add',
                    edit_url: 'distribution/models/edit',
                    del_url: 'distribution/models/del',
                    multi_url: 'distribution/models/multi',
                    table: 'models_info',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'licenseplatenumber', title: __('Licenseplatenumber')},
                        {field: 'models_name', title: __('Models_name')},
                        {field: 'kilometres', title: __('Kilometres'), operate:'BETWEEN'},
                        {field: 'store_id', title: __('Store_id')},
                        {field: 'newpayment', title: __('Newpayment')},
                        {field: 'monthlypaymen', title: __('Monthlypaymen')},
                        {field: 'periods', title: __('Periods')},
                        {field: 'totalprices', title: __('Totalprices')},
                        {field: 'bond', title: __('Bond'), operate:'BETWEEN'},
                        {field: 'tailmoney', title: __('Tailmoney'), operate:'BETWEEN'},
                        {field: 'drivinglicenseimages', title: __('Drivinglicenseimages'), formatter: Table.api.formatter.images},
                        {field: 'vin', title: __('Vin')},
                        {field: 'engine_number', title: __('Engine_number')},
                        {field: 'expirydate', title: __('Expirydate')},
                        {field: 'annualverificationdate', title: __('Annualverificationdate')},
                        {field: 'carcolor', title: __('Carcolor')},
                        {field: 'aeratedcard', title: __('Aeratedcard')},
                        {field: 'volumekeys', title: __('Volumekeys')},
                        {field: 'Parkingposition', title: __('Parkingposition')},
                        {field: 'car_images', title: __('Car_images'), formatter: Table.api.formatter.images},
                        {field: 'shelfismenu', title: __('Shelfismenu')},
                        {field: 'vehiclestate', title: __('Vehiclestate')},
                        {field: 'note', title: __('Note')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status_data', title: __('Status_data'), searchList: {"for_the_car":__('Status_data for_the_car'),"the_car":__('Status_data the_car'),"is_reviewing_pass":__('Status_data is_reviewing_pass'),"take_the_car":__('Status_data take_the_car'),"send_the_car":__('Status_data send_the_car')}, formatter: Table.api.formatter.normal},
                        {field: 'modelsimages', title: __('Modelsimages'), formatter: Table.api.formatter.images},
                        {field: 'guide_price', title: __('Guide_price'), operate:'BETWEEN'},
                        {field: 'models_main_images', title: __('Models_main_images'), formatter: Table.api.formatter.images},
                        {field: 'popularity', title: __('Popularity')},
                        {field: 'weigh', title: __('Weigh')},
                        {field: 'car_licensedate', title: __('Car_licensedate')},
                        {field: 'emission_standard', title: __('Emission_standard')},
                        {field: 'emission_load', title: __('Emission_load')},
                        {field: 'speed_changing_box', title: __('Speed_changing_box')},
                        {field: 'the_transfer_record', title: __('The_transfer_record')},
                        {field: 'license_plate', title: __('License_plate')},
                        {field: 'daypaymen', title: __('Daypaymen')},
                        {field: 'store.id', title: __('Store.id')},
                        {field: 'store.cities_id', title: __('Store.cities_id')},
                        {field: 'store.store_name', title: __('Store.store_name')},
                        {field: 'store.store_address', title: __('Store.store_address')},
                        {field: 'store.phone', title: __('Store.phone')},
                        {field: 'store.createtime', title: __('Store.createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'store.updatetime', title: __('Store.updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'store.statuss', title: __('Store.statuss')},
                        {field: 'store.store_img', title: __('Store.store_img')},
                        {field: 'store.store_qrcode', title: __('Store.store_qrcode')},
                        {field: 'store.longitude', title: __('Store.longitude')},
                        {field: 'store.latitude', title: __('Store.latitude')},
                        {field: 'store.mobile', title: __('Store.mobile')},
                        {field: 'store.invitation_code', title: __('Store.invitation_code')},
                        {field: 'store.level_id', title: __('Store.level_id')},
                        {field: 'store.store_description', title: __('Store.store_description')},
                        {field: 'store.store_user_id', title: __('Store.store_user_id')},
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
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});