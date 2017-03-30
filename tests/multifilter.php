<?php
//header("content-type: text/plain");
require('../TemplateObject.php');
//$to = new TemplateObject();
//$to->loadTemplate('multifilter.html');
$to = TemplateObject::loadTemplate('multifilter.html');
$to->setVariable('TITLE', "Multiple filter test");

$string = "This a string with \"quotes\" and several lines\nsecond line\nthitd line";
$to->setVariable('VAR', $string);

$to->addFilter('wrongfilter', function($a){ return 'wrong! '.$a; });
$to->removeFilter('raw');
$to->addFilter('html', 'html_cb', TRUE);
$to->addFilter('html2', 'html2_cb');

for($i=1; $i<=3; $i++) {
	$b = $to->setBlock('block1');
	$b->setVariable('VAR', $string);
}

$to->showOutput();

function html_cb($a)
{
	return '<font color=red>' . htmlentities($a) . '</font>';
}

function html2_cb($a)
{
	return '<font color=green>' . htmlentities($a) . '</font>';
}