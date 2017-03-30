<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 'on');
//header("content-type: text/plain");
require('../TemplateObject.php');

$to = TemplateObject::loadTemplate('extend.html');

$to->setVariable('TITLE', "this is a 'title'");

for($i=1; $i<=3; $i++) {
    $row = $to->setBlock('row');    
    $row->setVariable('COL', "test \"$i\"");
}

$to->showOutput();