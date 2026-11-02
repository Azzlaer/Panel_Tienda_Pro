<?php
$settingsFile=__DIR__.'/site.json';
$settings=is_file($settingsFile)?json_decode(file_get_contents($settingsFile),true):[];
$active=$settings['template']??'modern';
if(!is_string($active)||!preg_match('/^[a-z0-9_-]+$/',$active)||!is_file(dirname(__DIR__)."/templates/$active/index.php")){$active='modern';}
define('TEMPLATE_ACTIVO',$active);
