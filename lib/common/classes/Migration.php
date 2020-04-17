<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\classes;

class Migration extends \yii\db\Migration
{

    /**
     * drop index with same name if exists and create it again
     * @inheritdoc
     */
    public function createIndex($name, $table, $columns, $unique = false)
    {
        $checkExists = $this->db->createCommand(
            "show indexes from " . $table .
            " WHERE Key_name like :indexName ",
            ['indexName' => $name]
        )->queryOne();
        if (is_array($checkExists)) {
            // drop old one
            $this->dropIndex($name, $table);
        }

        parent::createIndex($name, $table, $columns, $unique);
    }

    /**
     * @inheritdoc
     */
    public function isTableExists($tableName)
    {
        return $this->db->getTableSchema($tableName, true) !== null;
    }

    /**
     * @inheritdoc
     */
    public function isFieldExists($field, $table){
        $fields = $this->db->createCommand("show FIELDS from {$table}")->queryColumn();
        return in_array($field, $fields);
    }

    /**
     * @inheritdoc
     */
    public function createTable($table, $columns, $options = null)
    {
        if ($options === null && $this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            //$options = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
            $options = 'ENGINE=InnoDB CHARSET=utf8';
        }
        parent::createTable($table, $columns, $options);
    }

/**
 *
 * @staticvar boolean $language_map
 * @param string  $entity
 * @param array $keys [$key=>$value]
 */
    public function addTranslation($entity, $keys)
    {
        static $language_map = false;
        if ( $language_map===false ) {
            $language_map = \yii\helpers\ArrayHelper::map(
                \common\models\Languages::find()->select('languages_id, code')->asArray()->all(),
                'code', 'languages_id'
            );
        }
        foreach ($keys as $key=>$value)
        {
            if ( is_array($value) ) {
                // per language $value -- 'en' => 'english', 'fr' => 'French'
                foreach ($language_map as $languageCode=>$languageId ) {
                    if (isset($value[$languageCode])) {
                        $languageValue = $value[$languageCode];
                    }elseif ( isset($value[\common\helpers\Language::systemLanguageCode()]) ) {
                        $languageValue = $value[\common\helpers\Language::systemLanguageCode()];
                    }else{
                        $languageValue = reset($value);
                    }
                    $this->db->createCommand(
                        "INSERT IGNORE INTO `translation` ".
                        "  (language_id, translation_key, translation_entity, translation_value) ".
                        "  VALUES (:languages_id, :text_key, :entity, :text_value)",
                        [
                            'languages_id' => (int)$languageId,
                            'entity' => $entity,
                            'text_key' => $key,
                            'text_value' => $languageValue,
                        ]
                    )->execute();
                }
            }else{
                $this->db->createCommand(
                    "INSERT IGNORE INTO `translation` ".
                    "  (language_id, translation_key, translation_entity, translation_value) ".
                    "  SELECT languages_id, :text_key, :entity, :text_value FROM languages",
                    [
                        'entity' => $entity,
                        'text_key' => $key,
                        'text_value' => $value,
                    ]
                )->execute();
            }
        }

        \yii\caching\TagDependency::invalidate(\Yii::$app->getCache(),'translation');
    }

    /**
     *
     * @param string $entity
     * @param array $keys
     */
    public function removeTranslation($entity, $keys=null)
    {
        if ( !empty($keys) ){
            if ( !is_array($keys) ) $keys = array($keys);
            foreach( $keys as $key ) {
                $this->db->createCommand(
                    "DELETE FROM translation ".
                    "WHERE translation_entity=:entity AND translation_key=:translate_key ",
                    ['entity' => $entity, 'translate_key'=>$key])
                    ->execute();
            }
        }else {
            $this->db->createCommand("DELETE FROM translation WHERE translation_entity=:entity", ['entity' => $entity])->execute();
        }

        \yii\caching\TagDependency::invalidate(\Yii::$app->getCache(),'translation');
    }

    /**
     * @param $key_name
     * @param $type_to_data
     * @example addEmailTemplate('Test email', [ 'html'=>['subject'=>'email subject', 'body'=>'email body'], [ 'text'=>['subject'=>'email subject', 'body'=>'email body'] ])
     */
    public function addEmailTemplate($key_name, $type_to_data)
    {
        $data = [
            'html' => [
                'email_templates_subject' => (
                isset($type_to_data['html']['subject'])?
                    $type_to_data['html']['subject']:
                    (isset($type_to_data['text']['subject'])?$type_to_data['text']['subject']:'')
                ),
                'email_templates_body' => (
                isset($type_to_data['html']['body'])?
                    $type_to_data['html']['body']:
                    (isset($type_to_data['text']['body'])?$type_to_data['text']['body']:'')
                ),
            ],
            'plaintext' => [
                'email_templates_subject' => (
                isset($type_to_data['text']['subject'])?
                    $type_to_data['text']['subject']:
                    (isset($type_to_data['html']['subject'])?$type_to_data['html']['subject']:'')
                ),
                'email_templates_body' => (
                isset($type_to_data['text']['body'])?
                    $type_to_data['text']['body']:
                    (isset($type_to_data['html']['body'])?$type_to_data['html']['body']:'')
                ),
            ]
        ];

        foreach ( $data as $email_template_type=>$email_template_data ){
            $existing_email_templates_id = $this->db->createCommand(
                "SELECT email_templates_id ".
                "FROM email_templates ".
                "WHERE email_templates_key = :templates_key and email_template_type=:email_type ",
                [
                    'templates_key' => $key_name,
                    'email_type' => $email_template_type,
                ])->queryScalar();

            if ( !$existing_email_templates_id ){
                $this->insert('email_templates', [
                    'email_templates_key' => $key_name,
                    'email_template_type' => $email_template_type,
                ]);
                $email_template_id = $this->db->getLastInsertID();
                $insert_data_query = new \yii\db\Query();
                foreach ($insert_data_query->select([
                    'email_templates_id' => new \yii\db\Expression($email_template_id),
                    'platform_id' => 'p.platform_id',
                    'language_id' => 'l.languages_id',
                    'affiliate_id' => new \yii\db\Expression(0),
                ])->from(['p'=>'platforms','l'=> 'languages'])
                    ->where('p.is_virtual=0')->all() as $row){
                    $row['email_templates_subject'] = $email_template_data['email_templates_subject'];
                    $row['email_templates_body'] = $email_template_data['email_templates_body'];
                    $this->insert('email_templates_texts', $row);
                }
            }
        }
    }

    public function removeEmailTemplate($key_name)
    {
        $this->db->createCommand(
            "DELETE ett FROM email_templates_texts ett ".
            " INNER JOIN email_templates et ON ett.email_templates_id=et.email_templates_id ".
            "WHERE email_templates_key = :templates_key ",
            [
                'templates_key' => $key_name,
            ])->execute();
        $this->db->createCommand(
            "DELETE et FROM email_templates et ".
            "WHERE email_templates_key = :templates_key ",
            [
                'templates_key' => $key_name,
            ])->execute();
    }

    protected function appendAcl($aclChain, $assign_to_access_levels = 1)
    {
        $PARENT_ID = 0;
        $ACL_INSERTED_ID = false;

        foreach ( $aclChain as $assignBox ) {

            $checkOnLevel = $this->db->createCommand(
                "SELECT access_control_list_id AS id ".
                "FROM access_control_list ".
                "WHERE parent_id=:parent_id AND access_control_list_key=:box_name ",
                ['parent_id'=>(int)$PARENT_ID, 'box_name'=>$assignBox]
            )->queryOne();
            if ( is_array($checkOnLevel) ) {
                $PARENT_ID = $checkOnLevel['id'];
            }else{
                $getSO = $this->db->createCommand(
                    "SELECT MAX(sort_order) AS max_so ".
                    "FROM access_control_list ".
                    "WHERE parent_id='".(int)$PARENT_ID."'"
                )->queryOne();
                $SORT_ORDER = (int)$getSO['max_so']+1;

                $this->insert('access_control_list',[
                    'parent_id' => $PARENT_ID,
                    'access_control_list_key' => $aclChain[count($aclChain)-1],
                    'sort_order' => $SORT_ORDER,
                ]);
                $ACL_INSERTED_ID = $this->db->getLastInsertID();
                $PARENT_ID = $ACL_INSERTED_ID;

                if ( !is_array($assign_to_access_levels) ) $assign_to_access_levels = array($assign_to_access_levels);

                $this->db->createCommand(
                    "UPDATE access_levels ".
                    "SET access_levels_persmissions = CONCAT(access_levels_persmissions,',','".(int)$ACL_INSERTED_ID."') ".
                    "WHERE access_levels_id IN ('".implode("','",array_map('intval',$assign_to_access_levels))."')"
                )->execute();
            }
        }
        if ( $ACL_INSERTED_ID===false ){
            $ACL_INSERTED_ID = $PARENT_ID;
        }

        return $ACL_INSERTED_ID;
    }

    protected function addAdminMenuAfter( $menuData, $afterBoxTitle )
    {
        if (is_array($menuData) && !empty($menuData['title'])){
            $checkBox = $this->db->createCommand(
                "SELECT box_id ".
                "FROM admin_boxes ".
                "WHERE title=:box_title",
                ['box_title'=>$menuData['title']]
            )->queryOne();
            if ( is_array($checkBox) ) {
                return (int)$checkBox['box_id'];
            }
        }else{
            return false;
        }

        $getBox = $this->db->createCommand(
            "SELECT parent_id, box_id, sort_order ".
            "FROM admin_boxes ".
            "WHERE title=:box_title",
            ['box_title'=>$afterBoxTitle]
        )->queryOne();
        if ( is_array($getBox) ) {
            //$getBox['box_id'];
            $new_sort_order = $getBox['sort_order']+1;
            $this->db->createCommand(
                "UPDATE admin_boxes SET sort_order=sort_order+1 ".
                "WHERE parent_id=:parent_id AND sort_order>=:shift_sort_order",
                ['parent_id' => (int)$getBox['parent_id'], 'shift_sort_order'=>(int)$new_sort_order]
            )->execute();

            $defaultData = [
                'parent_id' => $getBox['parent_id'],
                'sort_order' => $new_sort_order,
                'acl_check' => '',
                'config_check' => '',
                'box_type' => 0,
                'path' => '',
                'title' => '',
                'filename' => '',
            ];
            $data = array_merge($defaultData, $menuData);
            $this->insert('admin_boxes',$data);
            return $this->db->getLastInsertID();
        }
        return false;
    }

}