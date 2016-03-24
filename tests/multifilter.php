<?php
//header("content-type: text/plain");
require('../TemplateObject.php');
$to = new TemplateObject();
$to->loadTemplate('multifilter.html');
$to->setVariable('TITLE', "Multiple filter test");

$string = "This a string with \"quoutes\" and several lines\nsecond line\nthitd line";
$to->setVariable('VAR', $string);

$to->addFilter('wrongfilter', function($a){ return 'wrong! '.$a; });
$to->removeFilter('raw');
$to->addFilter('html', 'htmlentities', TRUE);

$to->showOutput();

?>