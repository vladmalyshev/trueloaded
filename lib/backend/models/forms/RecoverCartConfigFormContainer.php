<?php

namespace backend\models\forms;

use common\models\Platforms;
use common\models\RecoverCartConfig;
use yii\base\Model;

class RecoverCartConfigFormContainer extends Model {

  /**
   * @var array | RecoverCartConfigForm
   */
  private $_forms = [];

  public function __construct( array $config = [] ) {
    $platforms = Platforms::find()->select('platform_id')
                          ->active()
                          ->andWhere('is_virtual=0')
                          ->andWhere('is_marketplace=0')
                          ->indexBy( 'platform_id' )
                          ->orderBy( "is_default DESC, sort_order, platform_name" )
                          ->asArray()->column();

    $recoverCartConfigs = RecoverCartConfig::find()
                                           ->indexBy( 'platform_id' )
                                           ->andWhere(['platform_id' => $platforms])
                                           ->all();

    foreach( $recoverCartConfigs as $recoverCartConfig ) {
      $configForm = new RecoverCartConfigForm();
      $configForm->setAttributes( $recoverCartConfig->attributes );
      $this->_forms[] = $configForm;
    }

    foreach( array_diff( $platforms, array_keys($recoverCartConfigs) ) as $platform ) {
      $this->_forms[] = new RecoverCartConfigForm( [ 'platform_id' => $platform ] );
    }


    parent::__construct( $config );
  }

  public function load($data, $formName = null){
    $loadForms = Model::loadMultiple($this->_forms, $data, $formName === null ? null : 'RecoverCartConfigForm' );
    return $loadForms;
  }


  public function save(){
    foreach($this->_forms as $form){
      if($row = RecoverCartConfig::findOne(['platform_id' => $form->platform_id])){
        $row->edit($form);
      } else{
        $row = RecoverCartConfig::create($form);
      }
      $row->save();
    }
  }


  public function getForms() {
    return $this->_forms;
  }
}
