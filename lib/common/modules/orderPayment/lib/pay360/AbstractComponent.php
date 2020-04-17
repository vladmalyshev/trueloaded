<?php
namespace common\modules\orderPayment\lib\pay360;
/**
 * Transaction component for pay360 payment flow
 * @author A.Kosheliev
 */
abstract class AbstractComponent {
    
    public function getData(){
        $obj = new \ReflectionClass($this);
        $data = [];
        foreach ($obj->getProperties() as $property){
            if (is_object($this->{$property->name})){
                $data[$property->name] = $this->{$property->name}->getData();
            } else if (!is_null($this->{$property->name})){
                if (is_array($this->{$property->name})){
                    foreach($this->{$property->name} as $key => $_data){
                        if (is_object($this->{$property->name}[$key])){
                            $data[$property->name][$key] = $this->{$property->name}[$key]->getData();
                        } else {
                            $data[$property->name][$key] = $this->{$property->name}[$key];
                        }
                    }
                } else {
                    $data[$property->name] = $this->{$property->name};
                }
            }
        }
        return $data;
    }
    
}
