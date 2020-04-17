<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);


namespace common\extensions\PersonalCatalog\controllers;

use common\extensions\PersonalCatalog\Enums\PersonalCatalogFlag;
use common\services\InventoryService;
use common\services\ProductsService;
use common\extensions\PersonalCatalog\services\PersonalCatalogService;
use common\models\Customers;
use common\services\storages\StorageInterface;
use frontend\controllers\Sceleton;
use common\helpers\Translation;
use frontend\design\boxes\Listing;
use frontend\design\Info;
use frontend\design\SplitPageResults;
use common\classes\Currencies;
use yii\db\Expression;

class PersonalCatalogController extends Sceleton
{
    /** @var StorageInterface */
    private $storage;
    /** @var Currencies */
    private $currencies;
    /** @var string */
    private $currency;
    /** @var int */
    private $languageId;
    /** @var \yii\web\IdentityInterface|bool|Customers */
    private $customer;
    /** @var PersonalCatalogService */
    private $personalCatalogService;
    /** @var ProductsService */
    private $productsService;
    /** @var InventoryService */
    private $inventoryService;

    public function __construct(
        $id,
        $module = null,
        PersonalCatalogService $personalCatalogService,
        ProductsService $productsService,
        InventoryService $inventoryService,
        array $config = []
    )
    {
        parent::__construct($id, $module, $config);
        $this->initTranslations();
        $this->personalCatalogService = $personalCatalogService;
        $this->storage = \Yii::$app->get('storage');
        $this->currencies = \Yii::$container->get('currencies');
        $this->currency = \Yii::$app->settings->get('currency');
        $this->languageId = (int)\Yii::$app->settings->get('languages_id');
        try {
            $this->customer = \Yii::$app->user->isGuest ? false : \Yii::$app->user->getIdentity();
        } catch (\Throwable $t) {
            $this->customer = false;
        }

        $this->productsService = $productsService;
        $this->inventoryService = $inventoryService;
    }

    public function init()
    {
        parent::init();
    }

    public function actionIndex()
    {
        global $breadcrumb;

        $dataFromGet = \Yii::$app->request->get();

        if (\Yii::$app->user->isGuest) {
            return $this->redirect(\Yii::$app->urlManager->createUrl(['account/login']));
        }
        $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_PERSONAL_CATALOG));
        $search_results = Info::widgetSettings('Listing', 'items_on_page', 'products');
        if (!$search_results) {
            $search_results = SEARCH_RESULTS_1;
        }
        $this->storage->set('gl', 'b2b');
        $q = (new \common\components\ProductsQuery([
            'get' => $dataFromGet,
            'page' => FILENAME_PERSONAL_CATALOG
        ]));

        $q->buildQuery()->getQuery()
            ->select(new Expression('pcc.uprid as products_id'))
            ->innerJoin('personal_catalog pcc', "pcc.products_id=p.products_id AND pcc.customers_id ={$this->customer->customers_id}")//->orderBy('pcc.created_at')
        ;
        $cnt = $q->count();
        \Yii::$app->set('productsFilterQuery', $q);
        $params = [
            'listing_split' => SplitPageResults::make($q->getQuery(),
                ($this->storage->get('max_items') ?? $search_results), '*', 'page', $cnt)->withSeoRelLink(),
            'sorting_id' => Info::sortingId(),
            'this_filename' => 'personal-catalog',
            'page_block' => 'products',
            'page_name' => 'products',
            'currency' => $this->currencies->currencies[$this->currency],
            'listing_type' => 'type-1_3',
            'list_type' => 'type-1_3',
            'listing_type_rows' => 'type-1_3',
            'listing_type_b2b' => true,
            'gl' => 'b2b',
            'settingsAdditional' => [
                \common\models\queries\ProductsQuery::class => [
                    'addSelect' => new Expression('if(pcc.add_flag is null,2,pcc.add_flag) AS add_flag, pcc.qty AS add_qty '),
                    'innerJoin' => [
                        'personal_catalog pcc',
                        "pcc.products_id=p.products_id AND pcc.customers_id ={$this->customer->customers_id}"
                    ]
                ],
            ]
        ];
        if (isset($dataFromGet['fbl']) && $dataFromGet['fbl']) {
            $this->layout = 'ajax.tpl';
            return Listing::widget(['params' => $params, 'settings' => Info::widgetSettings('Listing')]);
        }
        return $this->render('personal-catalog.tpl', ['params' => [
            'params' => $params,
            'type' => 'catalog',
        ]]);
    }

    public function actionAdd()
    {
        $productId = \Yii::$app->request->post('products_id') ?? \Yii::$app->request->get('products_id', 0);
        $saveButtonId = \Yii::$app->request->post('personalCatalogButtonWrapId') ?? \Yii::$app->request->get('personalCatalogButtonWrapId', '');
        if (is_array($productId)) {
            $productId = $productId[0];
        }
        $check_attribute = \Yii::$app->request->post('check_attribute', true)
            ? \Yii::$app->request->get('check_attribute', true)
            : false;
        $pcQty = \Yii::$app->request->post('pc_qty')
            ?? \Yii::$app->request->get('pc_qty')
            ?? \Yii::$app->request->post('qty')
            ?? \Yii::$app->request->get('qty', 1);

        if (is_array($pcQty)) {
            $pcQty = $pcQty[0];
        }
        $pcQty = (int)$pcQty;
        if ($pcQty < 1) {
            $pcQty = 1;
        }
        $attributesIds = \Yii::$app->request->post('id') ?? \Yii::$app->request->get('id', []);
        if ($attributesIds && is_array($attributesIds)) {
            $keys = array_keys($attributesIds);
            if (is_array($attributesIds[$keys[0]])) {
                $attributesIds = $attributesIds[$keys[0]];
            }
        }

        if (!is_numeric($productId) || $attributesIds) {
            $productId = $productId = $this->inventoryService->getUId((int)$productId, $attributesIds);
        } else {
            $productId = (int)$productId;
        }

        $nameProduct = \common\helpers\Product::get_products_name($productId);
        $message = [];

        if (
            (int)$productId < 1 ||
            !$this->personalCatalogService->isAllowed($this->customer) ||
            !\common\helpers\Product::check_product((int)$productId)
        ) {
            $message[] = sprintf(ADD_TO_PERSONAL_CATALOG_ERROR, $nameProduct);
        }

        if ($check_attribute && \common\helpers\Attributes::has_product_attributes((int)$productId)) {
            if (is_array($attributesIds) && count($attributesIds) > 0) {
                $noErrors = true;
                foreach ($attributesIds as $optionId => $attributeId) {
                    if (!$attributeId) {
                        if ($noErrors) {
                            $message[] = PLEASE_CHOOSE_ATTRIBUTES;
                            $noErrors = false;
                        }
                        $option = $this->productsService->findOptionByPrimary($optionId, $this->languageId, true);
                        if ($option) {
                            $message[] = $option['products_options_name'];
                        }
                    }
                }
                // $productId = \common\helpers\Inventory::get_uprid((int)$productId, $attributesIds);
                // $productId = \common\helpers\Inventory::normalize_id($productId);

            } else {
                $message[] = PLEASE_CHOOSE_ATTRIBUTES;
            }
        }
        if ($message) {
            return $this->asJson([
                'message' => $this->renderPartial('message.tpl', [
                    'message' => $message,
                ])
            ]);
        }

        $inCatalog = $this->personalCatalogService->isInPersonalCatalog($this->customer->getId(), $productId);
        $message[] = sprintf(ADD_TO_PERSONAL_CATALOG_SUCCESS, $nameProduct);
        $urlText = TEXT_GO_PERSONAL_CATALOG;
        $urlLink = \Yii::$app->urlManager->createUrl(['personal-catalog']);
        if ($inCatalog === false) {
            try {
                $this->personalCatalogService->createAndSave(
                    $this->customer->getId(),
                    $productId,
                    PersonalCatalogFlag::ADDED_FROM_PRODUCT,
                    $pcQty
                );
            } catch (\Exception $e) {
                $message = sprintf(ADD_TO_PERSONAL_CATALOG_ERROR, $nameProduct);
            }
        }
        return $this->asJson([
            'message' => $this->renderPartial('message.tpl', [
                'message' => $message,
                'url_text' => $urlText,
                'url_link' => $urlLink,
            ]),
            'button' => \frontend\design\boxes\product\PersonalCatalogButton::widget(['params' => [
                'products_id' => $productId,
                'saveButtonId' => $saveButtonId,
                'get_button' => true,
            ]]),
        ]);
    }

    public function actionConfirmDelete()
    {
        $productId = \Yii::$app->request->post('products_id') ?? \Yii::$app->request->get('products_id', 0);
        $reload = \Yii::$app->request->post('reload') ?? \Yii::$app->request->get('reload', 0);
        $reload = (int)$reload;
        if (is_array($productId)) {
            $productId = $productId[0];
        }
        $personalCatalogButtonWrapId = \Yii::$app->request->post('personalCatalogButtonWrapId') ?? \Yii::$app->request->get('personalCatalogButtonWrapId', '');
        if (is_numeric($productId)) {
            $productId = (int)$productId;
            $attributesIds = \Yii::$app->request->post('id', []);
            if ($attributesIds) {
                $productId = $this->inventoryService->getUId($productId, $attributesIds);
            }
        }
        $uPrId = $productId;
        $nameProduct = \common\helpers\Product::get_products_name($productId);
        return $this->asJson([
            'message' => $this->renderPartial('confirm-delete.tpl', [
                'productId' => $uPrId,
                'reload' => $reload,
                'personalCatalogButtonWrapId' => $personalCatalogButtonWrapId,
                'message' => sprintf(REMOVE_FROM_PERSONAL_CATALOG_CONFIRM_MESSAGE, $nameProduct),
            ]),
        ]);
    }

    public function actionDelete()
    {
        $message = [];
        $reload = \Yii::$app->request->post('reload') ?? \Yii::$app->request->get('reload', 0);
        $productId = \Yii::$app->request->post('products_id') ?? \Yii::$app->request->get('products_id', 0);
        $saveButtonId = \Yii::$app->request->post('personalCatalogButtonWrapId') ?? \Yii::$app->request->get('personalCatalogButtonWrapId', '');
        $nameProduct = \common\helpers\Product::get_products_name($productId);
        try {
            $product = $this->personalCatalogService->getByPrimary($productId, $this->customer->getId());
            $this->personalCatalogService->remove($product);
            $message[] = sprintf(DEL_TO_PERSONAL_CATALOG_SUCCESS, $nameProduct);
        } catch (\Exception $e) {
            $message[] = sprintf(DEL_TO_PERSONAL_CATALOG_ERROR, $nameProduct);
        } catch (\Throwable $e) {
            $message[] = sprintf(DEL_TO_PERSONAL_CATALOG_ERROR, $nameProduct);
        }
        return $this->asJson([
            'message' => $this->renderPartial('message.tpl', [
                'message' => $message,
            ]),
            'button' => \frontend\design\boxes\product\PersonalCatalogButton::widget(['params' => [
                'products_id' => $productId,
                'saveButtonId' => $saveButtonId,
                'get_button' => true,
            ]]),
            'reload' => $reload,
        ]);
    }

    public function actionAddToCart()
    {
        global $cart, $quote;
        $message = [];
        $reload = true;
        $productId = \Yii::$app->request->post('products_id') ?? \Yii::$app->request->get('products_id', 0);
        $pc_qty = \Yii::$app->request->post('pc_qty') ?? \Yii::$app->request->get('pc_qty', 1);
        $attributes = [];
        if (!is_numeric($productId)) {
            $idData =  $this->inventoryService->uPrIdToParams((string)$productId);
            $productId = $idData['prId'];
            $attributes = $idData['attributes'];
        } else {
            $productId = (int)$productId;
        }
        $nameProduct = \common\helpers\Product::get_products_name($productId);
        $in_cart = \Yii::$app->request->post('pc_in_cart') ?? \Yii::$app->request->get('pc_in_cart', 'cart');
        if (is_object(${$in_cart}) && method_exists(${$in_cart}, 'add_cart') && method_exists(${$in_cart}, 'get_quantity')) {
            ${$in_cart}->add_cart((int)$productId, ${$in_cart}->get_quantity(
                    \common\helpers\Inventory::get_uprid((int)$productId, $attributes
                    )
                ) + $pc_qty,
                $attributes
            );
            $message[] = TEXT_IN_YOUR_CART;
            $reload = true;
        } else {
            $message[] = sprintf(PERSONAL_CATALOG_ERROR_ACTION, $nameProduct);
        }
        return $this->asJson([
            'message' => $this->renderPartial('message.tpl', [
                'message' => $message,
            ]),
            'reload' => $reload,
        ]);
    }

    public function actionRecalculate()
    {
        /** @var shopping_cart $cart */
        global $cart;
        try {
            $qtyArray = \Yii::$app->request->post('qty', []);
            $idArray = \Yii::$app->request->post('products_id', []);
            $invArray = \Yii::$app->request->post('id', []);
            $totalArray = [
                'selected' => $this->currencies->format(0.00),
                'current' => $cart->show_total(),
                'total' => $cart->show_total(),
            ];
            if (!is_array($qtyArray)) {
                return $this->asJson($totalArray);
            }
            $cartNew = new \common\classes\shopping_cart();
            foreach ($qtyArray as $key => $qty) {
                if ($qty > 0 && isset($idArray[$key])) {
                    $prId = $idArray[$key];
                    if (is_numeric($prId)) {
                        $prId = (int)$prId;
                        $attrib = $invArray[$prId] ?? [];
                        $uPrId = $this->inventoryService->getUId($prId, $attrib);
                    } else {
                        $uPrIdData = $this->inventoryService->uPrIdToParams($prId);
                        $prId = $uPrIdData['prId'];
                        $uPrId = $uPrIdData['uPrId'];
                        $attrib = $uPrIdData['attributes'];
                    }
                    $cartNew->add_cart($prId, $cartNew->get_quantity($uPrId) + $qty, $attrib, false);
                }
            }
            $totalArray['selected'] = $cartNew->show_total();
            $totalArray['total'] = $totalArray['selected'] + $totalArray['current'];
            foreach ($totalArray as $key => $value) {
                $totalArray[$key] = $this->currencies->format($value);
            }
            unset($cartNew);
            return $this->asJson($totalArray);
        } catch (\Exception $ex) {
            unset($cartNew);
            throw new \RuntimeException($ex->getMessage(), 0, $ex);
        }
    }

    public function getViewPath()
    {
        return \Yii::getAlias('@personal-catalog/views');
    }

    public function actionBanner()
    {
        if (!$this->customer) {
            return $this->redirect(\Yii::$app->urlManager->createUrl(['info', 'info_id' => 41]));
        }
        return $this->redirect(\Yii::$app->urlManager->createUrl(['personal-catalog']));
    }

    private function initTranslations()
    {
        Translation::init('account/login');
    }

}
