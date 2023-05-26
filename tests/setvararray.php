<?php
if(isset($_GET['text'])) header("content-type: text/plain");
require('../TemplateObject.php');
$to = TemplateObject::loadTemplate('setvararray.html');
$array = array(
	'VAR1' => 'value',
	'VAR2' => 'another value',
	 'singleblock' => array('BLOCKVAR1' => 'value1', 'BLOCKVAR2' => 'value2'),
	 'multiblock' => array(
	 	0 => array('VAR1' => 'val1', 'VAR2' => 'val2'),
	 	1 => array('VAR1' => 'val3', 'VAR2' => 'val4'),
	 ),
	 'emptyblock' => NULL,
);
$to->setVarArray($array);
$to->showOutput();
