<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Reader;

use backend\models\EP\Exception;

class XML implements ReaderInterface
{
    protected $file_header;
    private $file_start_pointer = 0;
    private $file_data_start_pointer;

    public $filename;

    protected $file_handle;

    public $rootTag = 'data';
    public $rowsTag = 'records';
    public $rowTag = 'record';

    var $parser;
    protected $currentTagStack = [];
    protected $detectedIndexed = [];
    protected $lastClosedTag = '';
    protected $cdataCollect = false;

    protected $collectedPathData = false;
    protected $cutCountFromCollectPath = 0;

    protected $readOutQueue = [];

    protected function openFile()
    {
        $this->file_header = null;
        $this->file_start_pointer = 0;
        $this->file_handle = @fopen($this->filename,'r');
        if ( !$this->file_handle ) {
            throw new Exception('Can\'t open file', 20);
        }

        $this->currentTagStack = [];
        $this->parser = xml_parser_create('utf-8');
        xml_set_object($this->parser, $this);
        xml_parser_set_option($this->parser,XML_OPTION_CASE_FOLDING,0);

        xml_set_element_handler($this->parser, "tag_open", "tag_close");
        xml_set_character_data_handler($this->parser, "cdata");

        $this->collectPath[$this->rootTag.'.'.$this->rowsTag.'.'.$this->rowTag] = true;

        $this->readColumns();

    }

    public function readColumns()
    {
        return [];
    }

    public function read()
    {
        if (!$this->file_handle) {
            $this->openFile();
        }

        if ( count($this->readOutQueue)>0 ) {
            $data = array_shift($this->readOutQueue);
            if ( count($this->readOutQueue)==0 ) {
                unset($this->readOutQueue);
                $this->readOutQueue= [];
            }
            return $data;
        }else{
            unset($this->readOutQueue);
            $this->readOutQueue = [];
        }

        while ( $data = fread($this->file_handle,4096*1) ){
            if ( !xml_parse($this->parser, $data, feof($this->file_handle)) ){
                $fmt = new \yii\i18n\Formatter();
                $memusage = $fmt->asShortSize(memory_get_usage(true),3);
                $mempeakusage = $fmt->asShortSize(memory_get_peak_usage(true),3);
                throw new Exception("XML Error: memusage {$memusage} mempeakusage {$mempeakusage} ".xml_error_string(xml_get_error_code($this->parser))." at line ".xml_get_current_line_number($this->parser)."");
            }
            if ( count($this->readOutQueue)>0 ) {
                $data = array_shift($this->readOutQueue);
                if ( count($this->readOutQueue)==0 ) {
                    unset($this->readOutQueue);
                    $this->readOutQueue= [];
                }
                return $data;
            }
        }
        return false;
    }

    public function currentPosition()
    {
        if ($this->file_handle) {
            return ftell($this->file_handle);
        }
        return 0;
    }

    public function setDataPosition($position)
    {
        // TODO: Implement setDataPosition() method.
    }

    public function getProgress()
    {
        $filePosition = $this->currentPosition();
        $percentDone = min(100,($filePosition/filesize($this->filename))*100);
        return number_format(  $percentDone,1,'.','');
    }

    function tag_open($parser, $tag, $attributes)
    {
        // {{ indexed arrays
        if ( $this->lastClosedTag==$tag ) {
            $indexedPath = substr(implode('.', $this->currentTagStack).'.'.$tag, $this->cutCountFromCollectPath);
            if ( !isset($this->detectedIndexed[$indexedPath]) ) {
                $this->detectedIndexed[$indexedPath] = 0;
                foreach (preg_grep('/^'.preg_quote($indexedPath).'/',array_keys($this->collectedPathData)) as $renameKey){
                    $this->collectedPathData[str_replace($indexedPath,$indexedPath.'.'.$this->detectedIndexed[$indexedPath],$renameKey)] = $this->collectedPathData[$renameKey];
                    unset($this->collectedPathData[$renameKey]);
                }
            }
            $this->detectedIndexed[$indexedPath]++;
        }
        // }} indexed arrays
        $this->currentTagStack[] = $tag;
        $startedPath = implode('.', $this->currentTagStack);
        if (isset($this->collectPath[$startedPath])){
            unset($this->collectedPathData);
            $this->collectedPathData = [];
            $this->cutCountFromCollectPath = strlen($startedPath)+1;
        }
        $this->cdataCollect = '';
        if ( count($attributes)>0 && is_array($this->collectedPathData) ) {
            foreach( $attributes as $attributeName=>$attributeValue ) {
                $this->collectedPathData[substr($startedPath.'.@'.$attributeName,$this->cutCountFromCollectPath)] = $attributeValue;
            }
        }
    }
    function cdata($parser, $cdata)
    {
        if ( !empty($cdata) && $this->cdataCollect!==false ) {
            $this->cdataCollect .= $cdata;
        }
    }
    function tag_close($parser, $tag)
    {
        $this->lastClosedTag = $tag;
        $closePath = implode('.', $this->currentTagStack);
        if ( $this->cdataCollect!==false ) {
            if ( is_array($this->collectedPathData) ) {
                $dataKey = substr($closePath,$this->cutCountFromCollectPath);
                foreach( $this->detectedIndexed as $indexedKey=>$indexCounter ){
                    if ( strpos($dataKey,$indexedKey)!==0 ) continue;
                    $dataKey = $indexedKey.'.'.$indexCounter.substr($dataKey,strlen($indexedKey));
                }
                $this->collectedPathData[$dataKey] = $this->cdataCollect;
            }
        }
        if ( isset($this->collectPath[$closePath]) ) {
            $this->lastClosedTag = '';
            $this->detectedIndexed = [];
            $this->readOutQueue[] = \backend\models\EP\ArrayTransform::convertFlatToMultiDimensional($this->collectedPathData);
            unset($this->collectedPathData);
            $this->collectedPathData = false;
        }
        unset($closePath);
        array_pop($this->currentTagStack);
        $this->cdataCollect = false;
    }
}