<?php
header("content-type: text/plain");
require('../TemplateObject.php');
$to = TemplateObject::loadTemplate('escaping.html');
$to->setVariable('TITLE', "this is a 'title'");
//exit;
for($i=1; $i<=3; $i++) {
    $row = $to->setBlock('row');    
    $row->setVariable('COL', "test \"$i\"");
}
//exit;
$to->showOutput();