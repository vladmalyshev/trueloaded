<?php

if ($type != 'email' && $type != 'invoice' && $type != 'packingslip' && $type != 'pdf' && $type != 'orders') {
	$widgets[] = array('name' => 'Chat', 'title' => BOX_CATALOG_CHAT, 'description' => '', 'type' => 'general', 'class' => '');
}