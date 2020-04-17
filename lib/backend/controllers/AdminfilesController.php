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

class AdminfilesController extends Sceleton {

    public $acl = ['BOX_HEADING_ADMINISTRATOR', 'BOX_ADMINISTRATOR_BOXES'];
    
    public function actionIndex() {
        $this->selectedMenu = array('administrator', 'adminfiles');
        $this->navigation[] = array('link' => \Yii::$app->urlManager->createUrl('adminfiles/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;
        $this->topButtons[] = '<a href="'.Yii::$app->urlManager->createUrl('adminfiles/edit').'" class="create_item" onclick="return editItem(0)">'.IMAGE_INSERT.'</a>';
        $this->view->accessTable = [
            [
                'title' => TABLE_HEADING_NAME,
                'not_important' => 0
            ],
        ];

        $this->view->filters = new \stdClass();
        $this->view->filters->row = (int) $_GET['row'];

        return $this->render('index');
    }

    public function actionList() {
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

        $responseList = [];
        if ($length == -1)
            $length = 10000;
        $recordsTotal = 0;

        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search_condition = " where access_levels_name like '%" . $keywords . "%' ";
        } else {
            $search_condition = " where 1 ";
        }

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "access_levels_name " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                default:
                    $orderBy = "access_levels_name";
                    break;
            }
        } else {
            $orderBy = "access_levels_name";
        }

        $current_page_number = ( $start / $length ) + 1;
        $accessQueryRaw = "select * from " . TABLE_ACCESS_LEVELS . " $search_condition order by $orderBy";
        $_split = new \splitPageResults($current_page_number, $length, $accessQueryRaw, $recordsTotal, 'access_levels_id');
        $accessQuery = tep_db_query($accessQueryRaw);
        while ($access = tep_db_fetch_array($accessQuery)) {
            $responseList[] = array(
                    $access['access_levels_name'] . '<input class="cell_identify" type="hidden" value="' . $access['access_levels_id'] . '">',
                );
        }

        $response = [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $responseList
        ];
        echo json_encode($response);
    }

    public function actionPreview() {
        $this->layout = false;
        $item_id = (int) Yii::$app->request->post('item_id');
        
        $accessQuery = tep_db_query("select * from " . TABLE_ACCESS_LEVELS . " where access_levels_id = '" . $item_id . "'");
        $access = tep_db_fetch_array( $accessQuery );
        if (is_array($access)) {
            echo '<div class="or_box_head">' . $access['access_levels_name'] . '</div>';
            echo '<div class="btn-toolbar btn-toolbar-order">';
            echo '<a class="btn btn-edit btn-no-margin" href="' . Yii::$app->urlManager->createUrl(['adminfiles/edit', 'item_id' => $item_id]) . '">' . IMAGE_EDIT . '</a>';
            echo '<button class="btn btn-delete" onclick="accessDelete(\'' . $item_id . '\')">' . IMAGE_DELETE . '</button>';
            if ($ext = \common\helpers\Acl::checkExtension('Messages', 'adminActionPreEdit')) {
                $ext::adminActionPreEdit($access);
            }
            if ($ext = \common\helpers\Acl::checkExtension('Handlers', 'adminActionPreEdit')) {
                $ext::adminActionPreEdit($item_id);
            }
            echo '</div>';
        }
    }
    
    public function actionEdit() {
        \common\helpers\Translation::init('admin/adminfiles');
        \common\helpers\Translation::init('admin/categories');
        
        if (Yii::$app->request->isPost) {            
            $item_id = (int) Yii::$app->request->post('item_id');
            $this->layout = false;
        } else {
            $item_id = (int) Yii::$app->request->get('item_id');
        }
        
        $this->selectedMenu = array('administrator', 'adminfiles');
        $this->navigation[] = array('link' => \Yii::$app->urlManager->createUrl('adminfiles/index'), 'title' => HEADING_TITLE);
        if ($item_id > 0) {
            $this->topButtons[] = '<a href="'.Yii::$app->urlManager->createUrl(['adminfiles/export-acl', 'item_id' => $item_id]).'" class="create_item backup"><i class="icon-file-text"></i>' . TEXT_EXPORT . '</a>';
            $this->topButtons[] = '<a href="javascript:void(0)" class="btn-import create_item backup"><i class="icon-file-text"></i>' . TEXT_IMPORT . '</a>';
        }

        $this->view->headingTitle = HEADING_TITLE;
        
        if ($item_id > 0) {
            $actionName = IMAGE_EDIT;
        } else {
            $actionName = IMAGE_INSERT;
        }
        
        $accessQuery = tep_db_query("select * from " . TABLE_ACCESS_LEVELS . " where access_levels_id = '" . $item_id . "'");
        $access = tep_db_fetch_array( $accessQuery );
        $accessInfo = new \objectInfo( $access );

        
        $aclTree = \common\helpers\Acl::buildTree($accessInfo->access_levels_persmissions);
        
        return $this->render('edit', [
            'actionName' => $actionName,
            'accessInfo' => $accessInfo,
            'aclTree' => $aclTree,
            'item_id' => $item_id,
        ]);
    }
    
    public function actionSubmit() {
        \common\helpers\Translation::init('admin/adminfiles');
        
        $item_id = (int) Yii::$app->request->post('item_id');
        
        $access_levels_name = Yii::$app->request->post('access_levels_name');
        
        $persmissions = Yii::$app->request->post('persmissions');
        if (!is_array($persmissions)) {
            $persmissions = [];
        }
        $access_levels_persmissions = implode(",", $persmissions);
        
        $sql_data_array = [
            'access_levels_name' => $access_levels_name,
            'access_levels_persmissions' => $access_levels_persmissions,
        ];
        
        if( $item_id > 0 ) {
            tep_db_perform(TABLE_ACCESS_LEVELS, $sql_data_array, 'update', "access_levels_id = '" . (int) $item_id . "'");
        } else {
            tep_db_perform(TABLE_ACCESS_LEVELS, $sql_data_array);
            $item_id = tep_db_insert_id();
        }
                
        $messageType = 'success';
        $message = TEXT_MESSEAGE_SUCCESS;
?>
        <div class="popup-box-wrap pop-mess">
                <div class="around-pop-up"></div>
                <div class="popup-box">
                    <div class="pop-up-close pop-up-close-alert"></div>
                    <div class="pop-up-content">
                        <div class="popup-heading"><?php echo TEXT_NOTIFIC; ?></div>
                        <div class="popup-content pop-mess-cont pop-mess-cont-<?php echo $messageType; ?>">
                            <?php echo $message; ?>
                        </div>   
                    </div>  
                    <div class="noti-btn">
                    <div></div>
                    <div><span class="btn btn-primary"><?php echo TEXT_BTN_OK;?></span></div>
                </div>
                </div> 
                <script>
                $('body').scrollTop(0);
                $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function(){
                    $(this).parents('.pop-mess').remove();
                });
                
            </script>
            </div>
<?php
        echo '<script> window.location.replace("'. Yii::$app->urlManager->createUrl(['adminfiles/edit', 'item_id' => $item_id]) . '");</script>';
        //return $this->actionEdit();
    }
    
    public function actionDelete() {
        $item_id = (int) Yii::$app->request->post('item_id');
        tep_db_query("delete from " . TABLE_ACCESS_LEVELS . " where access_levels_id = '" . $item_id . "'");
    }
    
    public function actionRecalcAcl() {
        $this->layout = false;
        $persmissions = Yii::$app->request->post('persmissions');
        
        $aclTree = \common\helpers\Acl::buildTree($persmissions);
        
        return $this->render('recalc-acl', [
            'aclTree' => $aclTree,
        ]);
    }
    
    public function actionExportAcl() {
        $access_levels_id = Yii::$app->request->get('item_id');
        $this->layout = false;
        
        $xml = new \yii\web\XmlResponseFormatter;
        $xml->rootTag = 'Acl';
        Yii::$app->response->format = 'custom_xml';
        Yii::$app->response->formatters['custom_xml'] = $xml;
        

        $headers = Yii::$app->response->headers;
        $headers->add('Content-Type', 'text/xml; charset=utf-8');
        $headers->add('Content-Disposition', 'attachment; filename="admin-acl.xml"');
        $headers->add('Pragma', 'no-cache');

        $acl = \common\models\AccessLevels::find()->where(['access_levels_id' => $access_levels_id])->one(); 
        if (is_string($acl->access_levels_persmissions)) {
            $selectedIds = explode(",", $acl->access_levels_persmissions);
        }
        if (!is_array($selectedIds)) {
            $selectedIds = [];
        }
        unset($acl);
        
        $acl = \common\models\AccessControlList::find()
                ->select(['access_control_list_key'])
                ->where(['IN', 'access_control_list_id', $selectedIds])
                ->orderBy('sort_order')
                ->asArray()
                ->all();
        
        $response = [];
        foreach ($acl as $item) {
            $response[] = $item['access_control_list_key'];
        }
        return $response;
    }
    
    public function actionImportAcl() {
        if (isset($_FILES['file']['tmp_name'])) {
            $xmlfile = file_get_contents($_FILES['file']['tmp_name']);
            $ob = simplexml_load_string($xmlfile);
            if (isset($ob->item)) {
                
                $access_levels_id = (int) Yii::$app->request->get('item_id');
                $selectedIds = [];
                foreach ($ob->item as $key) {
                     $acl = \common\models\AccessControlList::find()->where(['access_control_list_key' => (string)$key])->one();
                     if (is_object($acl)) {
                         $selectedIds[] = $acl->access_control_list_id;
                     }
                }
                if (count($selectedIds) > 0) {
                    $access_levels_persmissions = implode(",", $selectedIds);
                } else {
                    $access_levels_persmissions = '';
                }
                $al = \common\models\AccessLevels::find()->where(['access_levels_id' => $access_levels_id])->one(); 
                if (is_object($al)) {
                    $al->access_levels_persmissions = $access_levels_persmissions;
                    $al->save();
                }
            }
            unlink($_FILES['file']['tmp_name']);
        }
    }

}
