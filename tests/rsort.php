<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
if(isset($_GET['text'])) header("content-type: text/plain");
require('../TemplateObject.php');

$to = TemplateObject::loadTemplate('rsort.html');

for($i=1; $i<=3; $i++) {
	$b = $to->setBlock('normal')->setVariable('I', $i);
	$b = $to->setBlock('reversed')->setVariable('I', $i);
}
$to->showOutput();
