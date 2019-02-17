<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/29
 * Time: 11:13
 */

namespace addons\cms\model;


use think\Model;

class QuotedPrice extends Model
{
    protected $name = 'quoted_price';

    /**
     * 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id')->setEagerlyType(0);
    }

    /**
     * 关联车型
     * @return \think\model\relation\BelongsTo
     */
     public function ModelsInfo()
     {
         return $this->belongsTo('ModelsInfo', 'models_id', 'id')->setEagerlyType(0);
     }

     /**
     * 关联车型
     * @return \think\model\relation\BelongsTo
     */
     public function BuycarModel()
     {
         return $this->belongsTo('BuycarModel', 'models_id', 'id')->setEagerlyType(0);
     }

}