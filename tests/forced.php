<?php
if(isset($_GET['text'])) header("content-type: text/plain");
require('../TemplateObject.php');
$to = TemplateObject::loadTemplate('forced.html');
$to->setVariable('TITLE', "Forced filter test");
$to->addFilter('color', 'color_fn');
$to->setForcedFilter('html|color');
for($i=1; $i<=2; $i++) {
    $row = $to->setBlock("row");
    $row->setVariable('COL', "test <b>bold</b> test");
}
//exit;
$to->showOutput();

function color_fn($str)
{
    return "<font color='#8b0000'>$str</font>";
}
