<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design;

use Yii;

class EditData
{

    public static function getJsData()
    {
        global $languages_id;

        $data = [
            'TEXT_INFORMATION' => \common\helpers\Translation::$translations['TEXT_INFORMATION']['value'],
            'DIR_WS_HTTP_ADMIN_CATALOG' => HTTP_SERVER . DIR_WS_HTTP_CATALOG . DIR_WS_HTTP_ADMIN_CATALOG,
            'setFrontendTranslationTimeUrl' => Yii::$app->urlManager->createUrl('index/set-frontend-translation-time'),
            'platformId' => \common\classes\platform::currentId(),
            'languageId' => $languages_id,
            'isGuest' => Yii::$app->user->isGuest,
        ];

        return json_encode($data);
    }

    public static function addOnFrontend()
    {
        if (!\common\helpers\Acl::isFrontendTranslation()) {
            return '';
        }

        return '
<script>
    tl("' . Info::themeFile('/js/edit-data.js') . '", function(){
        editData.loader("' . addslashes(self::getJsData()) . '")
    })
</script>
<link rel="stylesheet" href="' . Info::themeFile('/css/edit-data.css') . '"/>
';
    }

    public static function addEditDataTeg($content, $pageType, $fieldName, $id = 0, $split = 0)
    {
        if (!\common\helpers\Acl::isFrontendTranslation()) {
            return $content;
        }

        $accessLevels = explode(',', Yii::$app->request->cookies->get('frontend_translation'));
        if (!\common\helpers\Acl::rule(self::getAccessRule($pageType), 0, $accessLevels)) {
            return $content;
        }

        return '<span class="edit-data-element"
            data-edit-data-page="' . $pageType . '"
            data-edit-data-field="' . $fieldName . '"
            data-edit-data-id="' . $id . '"
            data-edit-data-split="' . $split . '">' . $content . '</span>';

    }

    public static function getAccessRule ($pageType){
        switch ($pageType) {
            case 'seo': return ['BOX_HEADING_SEO', 'BOX_META_TAGS'];
            case 'info': return ['BOX_HEADING_DESIGN_CONTROLS', 'BOX_INFORMATION_MANAGER'];
            case 'menu': return ['BOX_HEADING_DESIGN_CONTROLS', 'FILENAME_CMS_MENUS'];
        }
        return '';
    }

    public static function addEditDataTegTranslation($content, $key)
    {
        if (\common\helpers\Acl::isFrontendTranslation()) {
            $entity = \common\helpers\Translation::$translations[$key];

            return '<span
                class="translation-key"
                data-translation-key="' . $key . '"
                data-translation-entity="' . $entity['entity'] . '">' . $content . '</span>';
        } else {
            return $content;
        }
    }


}