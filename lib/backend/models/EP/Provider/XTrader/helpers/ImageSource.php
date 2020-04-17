<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace backend\models\EP\Provider\XTrader\helpers;

use Yii;
use common\api\models\AR\Categories;
use common\api\models\AR\Products;

class ImageSource {
    
    private $remore_url;
    private static $_resource;
    private $dir;
    private $dir_import;

    private function __construct($config) {
        $this->remore_url = $config['media_location'];
        $this->dir = \common\classes\Images::getFSCatalogImagesPath();
        $this->dir_import = $this->dir . 'import' . DIRECTORY_SEPARATOR;
        if (!(is_dir($this->dir_import && !is_writable($this->dir_import) ))) $this->dir_import = $this->dir;
    }
    
    public static function getInstance($config){
        
      if (!(self::$_resource instanceof self)){
          self::$_resource = new self($config);
      }
      
      return self::$_resource;
    }
    
    public function loadResource($source, $owner = 'product'){
        if (empty($source)) return false;
        $remoteSource = $source;
        $localSource = pathinfo($remoteSource, PATHINFO_BASENAME);
        if ($owner == 'category'){
            if (!$this->sourceExist($localSource)){
                try{
                    $image = file_get_contents($this->remore_url . $remoteSource);
                } catch (\Exception $e){
                    return false;
                }
                try{
                    file_put_contents($this->dir . $localSource, $image);
                } catch (\Exception $e){
                    throw new \Exception('Error saving category media file');
                }
            }
            if (!$this->sourceExist($localSource)){
                return false;
            }
            return $localSource;
        } elseif ($owner == 'product'){
            if (!$this->sourceExist($localSource)){
                try{
                    $image = file_get_contents($this->remore_url . $remoteSource);
                } catch (\Exception $e){
                    return false;
                }                
                try{
                    file_put_contents($this->dir_import . $localSource, $image);
                } catch (\Exception $e){
                    throw new \Exception('Error saving product media file');
                }
            }
            if (!$this->sourceExist($localSource)){
                return false;
            }
            return $this->dir_import . $localSource;
        }
        return false;
    }
    
    public function sourceExist($source){        
        if (file_exists($this->dir . $source) || file_exists($this->dir_import . $source)){
            return true;
        }
        return false;
    }


    public function saveResource(){
        
    }
}