<?php

namespace addons\sfc;

use addons\sfc\model\Archives;
use addons\sfc\model\Channel;
use app\common\library\Auth;
use app\common\library\Menu;
use fast\Tree;
use think\Addons;
use think\Request;
use think\View;

/**
 * sfc插件
 */
class Sfc extends Addons
{
    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        $menu = [
            [
                'name'    => 'sfc',
                'title'   => 'sfc管理',
                'sublist' => [
                    [
                        'name'    => 'sfc/archives',
                        'title'   => '内容管理',
                        'icon'    => 'fa fa-file-text-o',
                        'sublist' => [
                            ['name' => 'sfc/archives/index', 'title' => '查看'],
                            ['name' => 'sfc/archives/content', 'title' => '副表管理', 'remark' => '用于管理模型副表的数据列表,不建议在此进行删除操作'],
                            ['name' => 'sfc/archives/add', 'title' => '添加'],
                            ['name' => 'sfc/archives/edit', 'title' => '修改'],
                            ['name' => 'sfc/archives/del', 'title' => '删除'],
                            ['name' => 'sfc/archives/multi', 'title' => '批量更新'],
                        ]
                    ],
                    [
                        'name'    => 'sfc/channel',
                        'title'   => '栏目管理',
                        'icon'    => 'fa fa-list',
                        'sublist' => [
                            ['name' => 'sfc/channel/index', 'title' => '查看'],
                            ['name' => 'sfc/channel/add', 'title' => '添加'],
                            ['name' => 'sfc/channel/edit', 'title' => '修改'],
                            ['name' => 'sfc/channel/del', 'title' => '删除'],
                            ['name' => 'sfc/channel/multi', 'title' => '批量更新'],
                            ['name' => 'sfc/channel/admin', 'title' => '栏目授权'],
                        ],
                        'remark'  => '用于管理网站的分类，可进行无限级分类，注意只有类型为列表的才可以添加文章'
                    ],
                    [
                        'name'    => 'sfc/modelx',
                        'title'   => '模型管理',
                        'icon'    => 'fa fa-th',
                        'sublist' => [
                            ['name' => 'sfc/modelx/index', 'title' => '查看'],
                            ['name' => 'sfc/modelx/add', 'title' => '添加'],
                            ['name' => 'sfc/modelx/edit', 'title' => '修改'],
                            ['name' => 'sfc/modelx/del', 'title' => '删除'],
                            ['name' => 'sfc/modelx/multi', 'title' => '批量更新'],
                            [
                                'name'    => 'sfc/fields',
                                'title'   => '字段管理',
                                'icon'    => 'fa fa-fields',
                                'ismenu'  => 0,
                                'sublist' => [
                                    ['name' => 'sfc/fields/index', 'title' => '查看'],
                                    ['name' => 'sfc/fields/add', 'title' => '添加'],
                                    ['name' => 'sfc/fields/edit', 'title' => '修改'],
                                    ['name' => 'sfc/fields/del', 'title' => '删除'],
                                    ['name' => 'sfc/fields/multi', 'title' => '批量更新'],
                                ],
                                'remark'  => '用于管理模型或表单的字段，进行相关的增删改操作'
                            ]
                        ],
                        'remark'  => '在线添加修改删除模型，管理模型字段和相关模型数据'
                    ],
                    [
                        'name'    => 'sfc/tags',
                        'title'   => '标签管理',
                        'icon'    => 'fa fa-tags',
                        'sublist' => [
                            ['name' => 'sfc/tags/index', 'title' => '查看'],
                            ['name' => 'sfc/tags/add', 'title' => '添加'],
                            ['name' => 'sfc/tags/edit', 'title' => '修改'],
                            ['name' => 'sfc/tags/del', 'title' => '删除'],
                            ['name' => 'sfc/tags/multi', 'title' => '批量更新'],
                        ],
                        'remark'  => '用于管理文章关联的标签,标签的添加在添加文章时自动维护,无需手动添加标签'
                    ],
                    [
                        'name'    => 'sfc/block',
                        'title'   => '区块管理',
                        'icon'    => 'fa fa-th-large',
                        'sublist' => [
                            ['name' => 'sfc/block/index', 'title' => '查看'],
                            ['name' => 'sfc/block/add', 'title' => '添加'],
                            ['name' => 'sfc/block/edit', 'title' => '修改'],
                            ['name' => 'sfc/block/del', 'title' => '删除'],
                            ['name' => 'sfc/block/multi', 'title' => '批量更新'],
                        ],
                        'remark'  => '用于管理站点的自定义区块内容,常用于广告、JS脚本、焦点图、片段代码等'
                    ],
                    [
                        'name'    => 'sfc/page',
                        'title'   => '单页管理',
                        'icon'    => 'fa fa-file',
                        'sublist' => [
                            ['name' => 'sfc/page/index', 'title' => '查看'],
                            ['name' => 'sfc/page/add', 'title' => '添加'],
                            ['name' => 'sfc/page/edit', 'title' => '修改'],
                            ['name' => 'sfc/page/del', 'title' => '删除'],
                            ['name' => 'sfc/page/multi', 'title' => '批量更新'],
                        ],
                        'remark'  => '用于管理网站的单页面，可任意创建修改删除单页面'
                    ],
                    [
                        'name'    => 'sfc/comment',
                        'title'   => '评论管理',
                        'icon'    => 'fa fa-comment',
                        'sublist' => [
                            ['name' => 'sfc/comment/index', 'title' => '查看'],
                            ['name' => 'sfc/comment/add', 'title' => '添加'],
                            ['name' => 'sfc/comment/edit', 'title' => '修改'],
                            ['name' => 'sfc/comment/del', 'title' => '删除'],
                            ['name' => 'sfc/comment/multi', 'title' => '批量更新'],
                        ],
                        'remark'  => '用于管理用户在网站上发表的评论，可任意修改或隐藏评论'
                    ],
                    [
                        'name'    => 'sfc/diyform',
                        'title'   => '自定义表单管理',
                        'icon'    => 'fa fa-list',
                        'sublist' => [
                            ['name' => 'sfc/diyform/index', 'title' => '查看'],
                            ['name' => 'sfc/diyform/add', 'title' => '添加'],
                            ['name' => 'sfc/diyform/edit', 'title' => '修改'],
                            ['name' => 'sfc/diyform/del', 'title' => '删除'],
                            ['name' => 'sfc/diyform/multi', 'title' => '批量更新'],
                        ],
                        'remark'  => '可在线创建自定义表单，管理表单字段和数据列表'
                    ],
                    [
                        'name'    => 'sfc/diydata',
                        'title'   => '自定义表单数据管理',
                        'icon'    => 'fa fa-list',
                        'ismenu'  => 0,
                        'sublist' => [
                            ['name' => 'sfc/diydata/index', 'title' => '查看'],
                            ['name' => 'sfc/diydata/add', 'title' => '添加'],
                            ['name' => 'sfc/diydata/edit', 'title' => '修改'],
                            ['name' => 'sfc/diydata/del', 'title' => '删除'],
                            ['name' => 'sfc/diydata/multi', 'title' => '批量更新'],
                        ],
                        'remark'  => '可在线管理自定义表单的数据列表'
                    ],
                    [
                        'name'    => 'sfc/order',
                        'title'   => '订单管理',
                        'icon'    => 'fa fa-cny',
                        'ismenu'  => 1,
                        'sublist' => [
                            ['name' => 'sfc/order/index', 'title' => '查看'],
                            ['name' => 'sfc/order/add', 'title' => '添加'],
                            ['name' => 'sfc/order/edit', 'title' => '修改'],
                            ['name' => 'sfc/order/del', 'title' => '删除'],
                            ['name' => 'sfc/order/multi', 'title' => '批量更新'],
                        ],
                        'remark'  => '可在线管理付费查看所产生的订单'
                    ]
                ]
            ]
        ];
        Menu::create($menu);
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        Menu::delete('sfc');
        return true;
    }

    /**
     * 插件启用方法
     */
    public function enable()
    {
        Menu::enable('sfc');
    }

    /**
     * 插件禁用方法
     */
    public function disable()
    {
        Menu::disable('sfc');
    }

    /**
     * 会员中心边栏后
     * @return mixed
     * @throws \Exception
     */
    public function userSidenavAfter()
    {
        $request = Request::instance();
        $actionname = strtolower($request->action());
        $data = [
            'actionname' => $actionname
        ];
        return $this->fetch('view/hook/user_sidenav_after', $data);
    }

}
