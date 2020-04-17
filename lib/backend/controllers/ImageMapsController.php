<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\controllers;

use Yii;
use yii\helpers\FileHelper;
use common\classes\design;
use common\models\ImageMaps;
use common\models\ImageMapsProperties;
use common\models\ImageMapsItems;
use common\models\ImageMapsItemsProperties;

/**
 *
 */
class ImageMapsController extends Sceleton {

    public $acl = ['BOX_HEADING_DESIGN_CONTROLS', 'BOX_HEADING_IMAGE_MAPS'];
    
    public static $propertiesNames = ['title'];

    public function actionIndex()
    {
        $this->selectedMenu = array('design_controls', 'image-maps');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('image-maps'), 'title' => BOX_HEADING_IMAGE_MAPS);
        $this->view->headingTitle = BOX_HEADING_IMAGE_MAPS;
        $this->topButtons[] = '<a href="' . Yii::$app->urlManager->createUrl('image-maps/edit') . '" class="create_item">' . ADD_MAP . '</a>';

        return $this->render('index.tpl', [
        ]);
    }

    public function actionList()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $draw = Yii::$app->request->get('draw', 1);
        $length = Yii::$app->request->get('length', 25);
        $search = Yii::$app->request->get('search');
        $start = Yii::$app->request->get('start', 0);
        $order = Yii::$app->request->get('order', 0);

        if ($length == -1) {
            $length = 10000;
        }
        if (!$search['value']) {
            $search['value'] = '';
        }

        $maps = ImageMaps::find()
            ->joinWith([
                'properties' => function ($query) {
                    $query->onCondition(['maps_properties_name' => 'title', 'languages_id' => \Yii::$app->settings->get('languages_id')]);
                },
            ])
            ->where([
                'and',
                ['maps_properties_name' => 'title', 'languages_id' => $languages_id],
                ['like', 'maps_properties_value', $search['value']]
            ])
            ->orWhere(['like', 'image', $search['value']])
            ->limit($length)
            ->offset($start)
            ->orderBy([
                'maps_properties_value' => $order[0]['dir'] == 'asc' ? SORT_ASC : SORT_DESC,
                'image' => $order[0]['dir'] == 'asc' ? SORT_ASC : SORT_DESC,
            ])
            ->all();

        $responseList = [];
        foreach ($maps as $map) {

            $title = $map->properties[0]->maps_properties_value;
            $responseList[] = array(
                '<div class="image-map" data-maps-id="'. $map->maps_id . '">'. ($title ? $title : $map->image) . '</div>',
            );
        }

        $countMaps = ImageMaps::find()->count();

        $response = array(
            'draw'            => $draw,
            'recordsTotal'    => $countMaps,
            'recordsFiltered' => $countMaps,
            'data'            => $responseList
        );
        echo json_encode( $response );
    }

    public function actionEdit()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $mapsId = (int)Yii::$app->request->get('maps_id', 0);

        $this->selectedMenu = array('design_controls', 'image-maps');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('image-maps'), 'title' => BOX_HEADING_IMAGE_MAPS);
        $this->view->headingTitle = BOX_HEADING_IMAGE_MAPS;
        $this->topButtons[] = '<span class="btn btn-confirm btn-save-boxes btn-elements">' . IMAGE_SAVE . '</span><a href="' . Yii::$app->urlManager->createUrl(['image-maps', 'maps_id' => $mapsId]) . '" class="btn btn-cancel">' . IMAGE_CANCEL . '</a>';

        $languages = \common\helpers\Language::get_languages();

        $properties = [];
        foreach (self::$propertiesNames as $propertiesName) {
            $mapsProperties = ImageMapsProperties::find()->where([
                'maps_id' => $mapsId,
                'maps_properties_name' => $propertiesName,
            ])->asArray()->all();
            foreach ($mapsProperties as $mapsProperty) {
                $properties[$propertiesName][$mapsProperty['languages_id']] = $mapsProperty['maps_properties_value'];
            }
        }

        $imageMap = ImageMaps::findOne($mapsId);

        return $this->render('edit.tpl', array_merge($properties, [
            'mapsId' => $mapsId,
            'languages' => $languages,
            'languages_id' => $languages_id,
            'items' => addslashes($imageMap['svg_data']),
            'image' => $imageMap['image'],
        ]));
    }

    public function actionBar()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $mapsId = Yii::$app->request->get('maps_id', 0);

        if (!$mapsId) {
            return '';
        }

        $title = ImageMaps::findOne($mapsId)->getTitle($languages_id);

        $this->layout = false;
        return $this->render('bar.tpl', [
            'mapsId' => $mapsId,
            'title' => $title
        ]);
    }

    public function actionUpload()
    {
        if (isset($_FILES['file'])) {
            $path = \Yii::getAlias('@webroot');
            $path .= DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'maps' . DIRECTORY_SEPARATOR;
            $response = [];

            $uploadfile = $path . self::basename($_FILES['file']['name']);

            if (!is_writeable(dirname($uploadfile))) {
                $response[] = [
                    'status' => 'error',
                    'text' => sprintf(ERROR_DATA_DIRECTORY_NOT_WRITEABLE, $uploadfile),
                    'file' => $_FILES['file']['name']
                ];
            } elseif (!is_uploaded_file($_FILES['file']['tmp_name']) || filesize($_FILES['file']['tmp_name']) == 0) {
                $response[] = [
                    'status' => 'error',
                    'text' => WARNING_NO_FILE_UPLOADED,
                    'file' => $_FILES['file']['name']
                ];
            } elseif (is_file($uploadfile)) {
                $response[] = [
                    'status' => 'choice',
                    'text' => FILE_ALREADY_EXIST . ' <span>' . DO_YOU_WANT_USE_UPLOADED_FILE . '</span>',
                    'file' => $_FILES['file']['name']
                ];
            } elseif (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {

                $response[] = [
                    'status' => 'ok',
                    'text' => TEXT_MESSEAGE_SUCCESS_ADDED,
                    'file' => $_FILES['file']['name']
                ];

            } else {
                $response[] = [
                    'status' => 'error',
                    'text'=> 'error',
                    'file' => $_FILES['file']['name']
                ];
            }

        }
        return json_encode($response);
    }

    public function actionSave()
    {
        $post = Yii::$app->request->post();
        $mapId = (int) $post['maps_id'];

        if ($mapId) {
            $imageMaps = ImageMaps::findOne($mapId);
            if (!$imageMaps) {
                $imageMaps = new ImageMaps();
            }
        } else {
            $imageMaps = new ImageMaps();
        }
        $imageMaps->attributes = [
            'image' => $post['image'],
            'svg_data' => $post['map'],
        ];
        $imageMaps->save();


        foreach (self::$propertiesNames as $propertiesName) {
            if (is_array($post[$propertiesName])) foreach ($post[$propertiesName] as $lng_id => $item) {

                $mapsProperties = ImageMapsProperties::find()->where([
                    'maps_id' => $mapId,
                    'languages_id' => (int)$lng_id,
                    'maps_properties_name' => $propertiesName,
                ])->one();

                if ($item) {
                    if (!$mapsProperties) {
                        $mapsProperties = new ImageMapsProperties();
                    }
                    $mapsProperties->attributes = [
                        'maps_id' => $mapId,
                        'languages_id' => (int)$lng_id,
                        'maps_properties_name' => $propertiesName,
                        'maps_properties_value' => $item
                    ];

                    $mapsProperties->save();
                } elseif ($mapsProperties) {
                    $mapsProperties->delete();
                }
            }
        }


        $response = [
            'status' => 'ok',
            'text'=> 'Saved',
            'maps_id' => $imageMaps->maps_id,
            'info' => $post['map']
        ];
        return json_encode($response);
    }

    public function actionDeleteConfirm()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $get = Yii::$app->request->get();
        $mapsId = (int) $get['maps_id'];

        $mapTitle = ImageMaps::findOne($mapsId)->getTitle($languages_id);


        $this->layout = false;
        return $this->render('delete-confirm.tpl', [
            'maps_id' => $mapsId,
            'name' => $mapTitle,
        ]);
    }

    public function actionDelete()
    {
        $mapsId = Yii::$app->request->get('maps_id', 0);

        if (!$mapsId) {
            return '';
        }

        ImageMaps::deleteAll(['maps_id' => $mapsId]);
        ImageMapsProperties::deleteAll(['maps_id' => $mapsId]);

        $response = [
            'status' => 'ok',
            'text'=> 'Removed',
            'maps_id' => $mapsId,
        ];

        return json_encode($response);
    }

    public function actionSearch()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $key = Yii::$app->request->get('key', '');

        $maps = ImageMaps::find()
            ->joinWith([
                'properties' => function ($query) {
                    $query->onCondition(['maps_properties_name' => 'title', 'languages_id' => \Yii::$app->settings->get('languages_id')]);
                },
            ])
            ->where([
                'and',
                ['maps_properties_name' => 'title', 'languages_id' => $languages_id],
                ['like', 'maps_properties_value', $key]
            ])
            ->orWhere(['like', 'image', $key])
            ->orWhere([ImageMaps::tableName().'.maps_id' => $key])
            ->all();
//echo $maps->createCommand()->getRawSql();
        $responseList = '';
        foreach ($maps as $map) {

            $title = $map->properties[0]->maps_properties_value;
            $responseList .= '
                    <a class="item" data-id="'. $map->maps_id . '" data-image="' . $map->image . '">
                        <span class="suggest_table"><span class="td_name">' . ($title ? $title : $map->image) . '</span></span>
                    </a>';
        }

        return $responseList;
    }


    public static function basename($param, $suffix=null,$charset = 'utf-8'){
        if ( $suffix ) {
            $tmpstr = ltrim(mb_substr($param, mb_strrpos($param, DIRECTORY_SEPARATOR, null, $charset), null, $charset), DIRECTORY_SEPARATOR);
            if ( (mb_strpos($param, $suffix, null, $charset)+mb_strlen($suffix, $charset) )  ==  mb_strlen($param, $charset) ) {
                return str_ireplace( $suffix, '', $tmpstr);
            } else {
                return ltrim(mb_substr($param, mb_strrpos($param, DIRECTORY_SEPARATOR, null, $charset), null, $charset), DIRECTORY_SEPARATOR);
            }
        } else {
            return ltrim(mb_substr($param, mb_strrpos($param, DIRECTORY_SEPARATOR, null, $charset), null, $charset), DIRECTORY_SEPARATOR);
        }
    }


}
