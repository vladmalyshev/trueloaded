<?php

if ($params['type'] != 'email' && $params['type'] != 'invoice' && $params['type'] != 'packingslip' && $params['type'] != 'pdf' && $params['type'] != 'orders') {
  $widgets[] = array('name' => 'Properties', 'title' => TEXT_PROPERTIES_LIST, 'description' => '', 'type' => 'general', 'class' => '');
}
