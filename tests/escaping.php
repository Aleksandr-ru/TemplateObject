<?php
if(isset($_GET['text'])) header("content-type: text/plain");
require('../TemplateObject.php');
$to = TemplateObject::loadTemplate('escaping.html');
$to->setVariable('TITLE', "this is a 'title'");
//exit;
for($i=1; $i<=2; $i++) {
    $row = $to->setBlock("row$i");
    $row->setVariable('COL', "test <b>\"$i\"</b>\n second-line");
}
//exit;
$to->showOutput();
