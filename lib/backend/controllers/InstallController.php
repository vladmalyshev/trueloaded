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

/**
 * default controller to handle user requests.
 */
class InstallController extends Sceleton {

    public $acl = ['BOX_HEADING_INSTALL'];

    public function actionIndex() {

        $this->selectedMenu = array('settings', 'logging');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('install/'), 'title' => BOX_HEADING_INSTALL);

        $this->view->headingTitle = BOX_HEADING_INSTALL;

        $messages = [];

        return $this->render('index', array('messages' => $messages));
    }

    public function actionUpload() {
        if (isset($_FILES['file']['tmp_name'])) {
            
            $xmlfile = file_get_contents($_FILES['file']['tmp_name']);
            $ob= simplexml_load_string($xmlfile);
            if (isset($ob->Menu)) {
                tep_db_query("TRUNCATE TABLE admin_boxes;");
                \common\helpers\MenuHelper::importAdminTree($ob->Menu);
            }
            if (isset($ob->Groups->item)) {
                foreach ($ob->Groups->item as $item) {
                    $al = \common\models\AccessLevels::find()->select(['access_levels_id'])->where(['access_levels_name' => (string)$item->Name])->one();
                    if (!is_object($al)) {
                        $al = new \common\models\AccessLevels();
                        $al->access_levels_name = (string)$item->Name;
                    }
                    if (is_object($al)) {
                        $selectedIds = [];
                        foreach ($item->Acl->item as $key) {
                            $acl = \common\models\AccessControlList::find()->where(['access_control_list_key' => (string) $key])->one();
                            if (is_object($acl)) {
                                $selectedIds[] = $acl->access_control_list_id;
                            }
                        }
                        if (count($selectedIds) > 0) {
                            $access_levels_persmissions = implode(",", $selectedIds);
                        } else {
                            $access_levels_persmissions = '';
                        }
                        $al->access_levels_persmissions = $access_levels_persmissions;
                        $al->save();
                    }
                }
            }
            if (isset($ob->Members->item)) {
                foreach ($ob->Members->item as $item) {
                    $admin = false;
                    if (isset($item->id)) {
                        $admin = \common\models\Admin::find()->where(['admin_id' => (int)$item->id])->one();
                    }
                    if (!is_object($admin)) {
                        $admin = new \common\models\Admin();
                    }
                    $admin->admin_username = (string)$item->username;
                    $admin->admin_firstname = (string)$item->firstname;
                    $admin->admin_lastname = (string)$item->lastname;
                    $admin->admin_email_address = (string)$item->email;
                    $admin->admin_phone_number = (string)$item->phone;
                    $admin->languages = (string)$item->languages;
                    $admin->access_levels_id = (int)$item->group;
                    $persmissions = [];
                    if (isset($item->persmissions->include)) {
                        foreach ($item->persmissions->include as $key) {
                            $aclItem = \common\models\AccessControlList::find()->select(['access_control_list_id'])->where(['access_control_list_key' => (string)$key])->asArray()->one();
                            if (isset($aclItem['access_control_list_id'])) {
                                $persmissions[] = $aclItem['access_control_list_id'];
                            }
                        }
                    }
                    if (isset($item->persmissions->exclude)) {
                        foreach ($item->persmissions->exclude as $key) {
                            $aclItem = \common\models\AccessControlList::find()->select(['access_control_list_id'])->where(['access_control_list_key' => (string)$key])->asArray()->one();
                            if (isset($aclItem['access_control_list_id'])) {
                                $persmissions[] = ($aclItem['access_control_list_id'] * -1);
                            }
                        }
                    }
                    $admin_persmissions = '';
                    if (count($persmissions) > 0) {
                        $admin_persmissions = implode(",", $persmissions);
                    }
                    $admin->admin_persmissions = $admin_persmissions;
                    $admin->save();
                }
            }
            unlink($_FILES['file']['tmp_name']);
        }
    }
    
    public function actionDownload() {
        $this->layout = false;
        $response = [];
        
        $xml = new \yii\web\XmlResponseFormatter;
        $xml->rootTag = 'Install';
        Yii::$app->response->format = 'custom_xml';
        Yii::$app->response->formatters['custom_xml'] = $xml;
        
        $headers = Yii::$app->response->headers;
        $headers->add('Content-Type', 'text/xml; charset=utf-8');
        $headers->add('Content-Disposition', 'attachment; filename="install.xml"');
        $headers->add('Pragma', 'no-cache');
        
        $menu = (int) Yii::$app->request->post('menu');
        $groups = (int) Yii::$app->request->post('groups');
        $members = (int) Yii::$app->request->post('members');
        
        if ($menu == 1) {
            $queryResponse = \common\models\AdminBoxes::find()
                ->orderBy(['sort_order' => SORT_ASC])
                ->asArray()
                ->all(); 
        
            $response['Menu'] = $this->buildXMLTree(0, $queryResponse, []);
        }
        
        if ($groups == 1) {
            $Groups = [];
            foreach (\common\models\AccessLevels::find()->all() as $acl) {
                $selectedIds = [];
                if (is_string($acl->access_levels_persmissions)) {
                    $selectedIds = explode(",", $acl->access_levels_persmissions);
                }
                if (!is_array($selectedIds)) {
                    $selectedIds = [];
                }
                $aclList = \common\models\AccessControlList::find()
                        ->select(['access_control_list_key'])
                        ->where(['IN', 'access_control_list_id', $selectedIds])
                        ->orderBy('sort_order')
                        ->asArray()
                        ->all();

                $aclRules = [];
                foreach ($aclList as $item) {
                    $aclRules[] = $item['access_control_list_key'];
                }
                
                $Groups[] = [
                    'Name' => $acl->access_levels_name,
                    'Acl' => $aclRules,
                ];
            }
            $response['Groups'] = $Groups;
        }
        
        if ($members == 1) {
             $membersList = \common\models\Admin::find()
                        ->asArray()
                        ->all();
            $Members = [];
            foreach ($membersList as $item) {
                $persmissions = [
                    'include' => [],
                    'exclude' => [],
                ];
                $adminPersmissions = explode(",", $item['admin_persmissions']);
                foreach ($adminPersmissions as $ap) {
                    if ($ap > 0) {
                        $aclItem = \common\models\AccessControlList::find()->select(['access_control_list_key'])->where(['access_control_list_id' => $ap])->asArray()->one();
                        if (isset($aclItem['access_control_list_key'])) {
                            $persmissions['include'][] = $aclItem['access_control_list_key'];
                        }
                    } elseif ($ap < 0) {
                        $aclItem = \common\models\AccessControlList::find()->select(['access_control_list_key'])->where(['access_control_list_id' => ($ap * -1)])->asArray()->one();
                        if (isset($aclItem['access_control_list_key'])) {
                            $persmissions['exclude'][] = $aclItem['access_control_list_key'];
                        }
                    }
                }
                
                $Members[] = [
                    'id' => $item['admin_id'],
                    'username' => $item['admin_username'],
                    'firstname' => $item['admin_firstname'],
                    'lastname' => $item['admin_lastname'],
                    'email' => $item['admin_email_address'],
                    'phone' => $item['admin_phone_number'],
                    'languages' => $item['languages'],
                    'group' => $item['access_levels_id'],
                    'persmissions' => $persmissions,
                ];
            }
            $response['Members'] = $Members;
        }
        
        return $response;
    }
    
    private function buildXMLTree($parent_id, $queryResponse) {
        $tree = [];
        foreach ($queryResponse as $response) {
            if ($response['parent_id'] == $parent_id) {
                if ($response['box_type'] == 1) {
                    $response['child'] = $this->buildXMLTree($response['box_id'], $queryResponse);
                }
                unset($response['box_id']);
                unset($response['parent_id']);
                $tree[] = $response;
            }
        }
        return $tree;
    }
}
