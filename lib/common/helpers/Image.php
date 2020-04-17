<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;
use yii\helpers\ArrayHelper;
use common\models\ProductsImages;

class Image {

    public static function copyProductImages($fromProductId, $toProductId) {
      $imgs = ProductsImages::find()//->with(['description', 'imagesAttributes', 'externalUrl', 'inventory'])
          ->andWhere(['products_id' => $fromProductId])->asArray()->all();
      if (is_array($imgs)) {
        $copyModels = [
            '\common\models\ProductsImagesAttributes' => 'products_images_id',
            '\common\models\ProductsImagesDescription' => 'products_images_id',
            '\common\models\ProductsImagesExternalUrl' => 'products_images_id',
        ];

        $basePath = \common\classes\Images::getFSCatalogImagesPath() . 'products' . DIRECTORY_SEPARATOR;
        //$productsId.'/' .$imageId
        foreach ($imgs as $img) {
          $tmp = $img;
          foreach (['description', 'attributes', 'externalUrl', 'inventory', 'products_images_id'] as $key) {
            unset($tmp[$key]);
          }
          $tmp['products_id'] = $toProductId;
          try {
            $copyModel = new ProductsImages();
            $copyModel->setAttributes($tmp, false);
            $copyModel->loadDefaultValues(true);
            $copyModel->save(false);
            $newImageId = $copyModel->products_images_id;
            \yii\helpers\BaseFileHelper::copyDirectory($basePath . $fromProductId . DIRECTORY_SEPARATOR . $img['products_images_id'],
                $basePath . $toProductId . DIRECTORY_SEPARATOR . $newImageId);

            foreach ($copyModels as $copyModelClass=>$copyProductColumn) {
              if ( !class_exists($copyModelClass) ) {
                  continue;
              }

              call_user_func_array([$copyModelClass,'deleteAll'], [[$copyProductColumn => $newImageId]]);
              $sourceCollection = call_user_func_array([$copyModelClass,'findAll'], [[$copyProductColumn => $img['products_images_id']]]);
              foreach ($sourceCollection as $originModel) {
                $__data = $originModel->getAttributes();
                $__data[$copyProductColumn] = $newImageId;
                $copyModel = \Yii::createObject($copyModelClass);
                if ( $copyModel instanceof \yii\db\ActiveRecord ) {
                  $copyModel->setAttributes($__data, false);
                  $copyModel->loadDefaultValues(true);
                  $copyModel->save(false);
                }
              }
            }

          } catch (\Exception $ex) {
            \Yii::error($ex->getMessage());
          }
        }
      }
    }

    public static function getNewSize($pic, $reqW, $reqH) {
        $size = @GetImageSize($pic);

        if ($size[0] == 0 || $size[1] == 0) {
            $newsize[0] = $reqW;
            $newsize[1] = $reqH;
            return $newsize;
        }

        $scale = @min($reqW / $size[0], $reqH / $size[1]);
        $newsize[0] = $size[0] * $scale;
        $newsize[1] = $size[1] * $scale;
        return $newsize;
    }

    public static function info_image($image, $alt, $width = '', $height = '') {
        if (tep_not_null($image) && (file_exists(DIR_FS_CATALOG_IMAGES . $image))) {
            if ($width != '' && $height != '') {
                $size = @GetImageSize(DIR_FS_CATALOG_IMAGES . $image);

                if (!($size[0] <= $width && $size[1] <= $height)) {
                    $newsize = self::getNewSize(DIR_FS_CATALOG_IMAGES . $image, $width, $height);

                    $width = $newsize[0];
                    $height = $newsize[1];
                } else {
                    $width = $size[0];
                    $height = $size[1];
                }
            }
            $image = tep_image(DIR_WS_CATALOG_IMAGES . $image, $alt, $width, $height);
        } else {
            $image = TEXT_IMAGE_NONEXISTENT;
        }
        return $image;
    }

}
