<?php

// change the following paths if necessary
define('WP_USE_THEMES', true);
define('WP_ADMIN', true);

$wp_did_header = true;
require_once( dirname(__FILE__).'/../wordpress/wp-load.php');
$yiic=dirname(__FILE__).'/../vendor/yiisoft/yii/framework/yiic.php';
$config=dirname(__FILE__).'/config/console.php';
require_once(dirname(__FILE__).'/../vendor/autoload.php');
require_once($yiic);
