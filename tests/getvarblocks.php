<?php
header("content-type: text/plain");
require('../TemplateObject.php');
$to = new TemplateObject();
$to->loadTemplate('getvarblocks.html');

print_r($to->getVariables());
print_r($to->getBlocks());

$to = $to->setBlock('row');

print_r($to->getVariables());
print_r($to->getBlocks());
?>