<?php

if (in_array($params['type'], ['email', 'invoice', 'packingslip', 'orders', 'creditnote']) ) {
  $widgets[] = array('name' => 'invoice\StructuredProducts', 'title' => 'Structured Products', 'description' => '', 'type' => $params['type'], 'class' => '');
}
