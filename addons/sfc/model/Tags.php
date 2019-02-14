<?php

namespace addons\sfc\model;

use think\Db;
use think\Model;

/**
 * 标签模型
 */
class Tags Extends Model
{

    protected $name = "sfc_tags";
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    // 追加属性
    protected $append = [
        'url',
        'fullurl'
    ];

    public function model()
    {
        return $this->belongsTo("Modelx");
    }

    public function getUrlAttr($value, $data)
    {
        $name = $data['name'] ? $data['name'] : $data['id'];
        return addon_url('sfc/tags/index', [':id' => $data['id'], ':name' => $name]);
    }

    public function getFullurlAttr($value, $data)
    {
        $name = $data['name'] ? $data['name'] : $data['id'];
        return addon_url('sfc/tags/index', [':id' => $data['id'], ':name' => $name], true, true);
    }

    /**
     * 获取标签列表
     * @param $tag
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getTagsList($tag)
    {
        $condition = empty($tag['condition']) ? '' : $tag['condition'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $row = empty($tag['row']) ? 10 : (int)$tag['row'];
        $orderby = empty($tag['orderby']) ? 'nums' : $tag['orderby'];
        $orderway = empty($tag['orderway']) ? 'desc' : strtolower($tag['orderway']);
        $limit = empty($tag['limit']) ? $row : $tag['limit'];
        $cache = !isset($tag['cache']) ? true : (int)$tag['cache'];
        $orderway = in_array($orderway, ['asc', 'desc']) ? $orderway : 'desc';

        $where = [];

        $order = $orderby == 'rand' ? Db::raw('rand()') : (in_array($orderby, ['name', 'nums', 'id', 'createtime', 'updatetime']) ? "{$orderby} {$orderway}" : "nums {$orderway}");

        $list = self::where($where)
            ->where($condition)
            ->field($field)
            ->order($order)
            ->limit($limit)
            ->cache($cache)
            ->select();
        foreach ($list as $k => $v) {
            $v['textlink'] = '<a href="' . $v['url'] . '">' . $v['name'] . '</a>';
        }
        return $list;
    }

}