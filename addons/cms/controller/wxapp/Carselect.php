<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/30
 * Time: 17:22
 */

namespace addons\cms\controller\wxapp;


class Carselect extends Base
{
    protected $noNeedLogin = '*';
      public function index()
      {
          //buy,sell,clue
          $screening_conditions = $this->request->post('screening_conditions');

          $type = 0;
          switch ($screening_conditions){
              case 'sell':
                  $type = 1;
                  break;
              case 'buy':
                  $type = 2;
                  break;
              case 'clue':
                  $type = 3;
                  break;
          }

          $this->success(Index::typeCar($type));

      }
}