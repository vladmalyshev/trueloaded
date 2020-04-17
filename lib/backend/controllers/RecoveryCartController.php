<?php


namespace backend\controllers;

use backend\models\forms\RecoverCartConfigFormContainer;
use common\models\repositories\CouponRepository;
use Yii;
use yii\helpers\ArrayHelper;
use common\models\Coupons;


class RecoveryCartController extends Sceleton {
	public $acl = [ 'TEXT_SETTINGS', 'BOX_HEADING_RECOVERY_CART_COUPON_SETTINGS' ];

	private $_couponRepository;

	public function __construct( $id, $module, CouponRepository $couponRepository, array $config = [] ) {
		parent::__construct( $id, $module, $config );
		$this->_couponRepository = $couponRepository;
		\common\helpers\Translation::init( 'main' );
	}

	public function actionIndex() {
		$this->selectedMenu = array('settings', 'recovery-cart');
		$this->navigation[] = array('link' => \Yii::$app->urlManager->createUrl('recovery-cart/index'), 'title' => HEADING_TITLE);
		$this->view->headingTitle = HEADING_TITLE;

		$post = Yii::$app->request->post();

		$containerForm = new RecoverCartConfigFormContainer();

		if($containerForm->load($post)){
			$containerForm->save();
			Yii::$app->session->setFlash('success', TEXT_MESSEAGE_SUCCESS);
		}

		return $this->render( 'index', [
			'forms'                => $containerForm->forms,
			'isMultiPlatform'      => \common\classes\platform::isMulti(),
			'selected_platform_id' => \common\classes\platform::defaultId(),
			'coupons' =>['Select coupon'] + ArrayHelper::map(Coupons::find()->where(['coupon_active' => 'Y', 'coupon_for_recovery_email' => 1])->all(), 'coupon_id' , 'full_name'),
			'messages' => Yii::$app->session->getAllFlashes()
		] );
	}

	public function saveAction() {
		$post = Yii::$app->request->post();

		$containerForm = new RecoverCartConfigFormContainer();

		if($containerForm->load($post)){
			$containerForm->save();
		}
	}


}