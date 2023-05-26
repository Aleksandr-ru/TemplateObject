<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 'on');
if(isset($_GET['text'])) header("content-type: text/plain");
require('../TemplateObject.php');

$to = TemplateObject::loadTemplate('global.html');

$to->setBlock('head');

$to->setVariable('TITLE', "this is a 'title'");
$to->setGlobalVariable('GLOBAL', "this is a GLOBAL variable");

$to->setBlock('foot');

$b = $to->setBlock('content');
for($i = 1; $i <= 3; $i++) {
	$r = $b->setBlock('row');
	$r->setVariable('COL', $i);
}

$to->showOutput();
