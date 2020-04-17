<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\SoapServer;


class ServerSession
{
    /**
     * @var integer
     */
    protected $DepartmentId = 0;

    /**
     * @var integer
     */
    protected $PlatformId = 0;

    /**
     * @var ServerAcl
     */
    protected $serverAcl;

    protected function __construct()
    {
    }

    /**
     * @return ServerSession
     */
    public static function get()
    {
        static $instance;
        if ( !$instance ) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * @return ServerAcl
     */
    public function acl()
    {
        if ( !is_object($this->serverAcl) ){
            $this->serverAcl = new ServerAcl();
        }
        return $this->serverAcl;
    }

    /**
     * @return int
     */
    public function getDepartmentId()
    {
        return $this->DepartmentId;
    }

    /**
     * @param int $DepartmentId
     */
    public function setDepartmentId($DepartmentId)
    {
        $this->DepartmentId = max(0,(int)$DepartmentId);
    }

    /**
     * @return int
     */
    public function getPlatformId()
    {
        return $this->PlatformId;
    }

    /**
     * @param int $PlatformId
     */
    public function setPlatformId($PlatformId)
    {
        $this->PlatformId = max(0,(int)$PlatformId);
        if (\Yii::$app->has('platform')){
            \Yii::$app->get('platform')->config($this->PlatformId);
        }
    }


}