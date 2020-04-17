<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\ProductEdit;


class TabAccess
{
    protected $subProduct = false;
    protected $supplierDataAllowed = true;

    public function setProduct($product)
    {
        if ( is_object($product) && $product->parent_products_id ) {
            $this->subProduct = true;
            $this->supplierDataAllowed = $product->parent_products_id!=$product->products_id_stock;
        }
    }

    public function checkSubProductTabs($tabCode)
    {
        $allowedForSubProducts = [
                'TEXT_NAME_DESCRIPTION',
                'TEXT_MAIN_DETAILS',
                'TAB_PROPERTIES',
                'TAB_IMAGES',
                'TEXT_VIDEO',
                'TEXT_SEO',
                'TEXT_MARKETING',
                'TAB_DOCUMENTS',
                'TAB_NOTES',
        ];
        if (true) {
            $allowedForSubProducts[] = 'TEXT_PRICE_COST_W';
            $allowedForSubProducts[] = 'TEXT_ATTR_INVENTORY';
        }
        return in_array($tabCode, $allowedForSubProducts);
    }

    public function isSubProduct()
    {
        return $this->subProduct;
    }

    public function allowSuppliersData()
    {
        return $this->supplierDataAllowed;
    }

    public function tabDataSave($tabCode)
    {
        if ( $this->subProduct && !$this->checkSubProductTabs($tabCode)) {
            return false;
        }
        return \common\helpers\Acl::rule(['TABLE_HEADING_PRODUCTS', 'IMAGE_EDIT', $tabCode]);
    }

    public function tabView($tabCode)
    {
        if ( $this->subProduct && !$this->checkSubProductTabs($tabCode)) {
            return false;
        }
        return \common\helpers\Acl::rule(['TABLE_HEADING_PRODUCTS', 'IMAGE_EDIT', $tabCode]);
    }


}
