<?php
if(isset($_GET['text'])) header("content-type: text/plain");
require('../TemplateObject.php');
$to = TemplateObject::loadTemplate('recursion.html');

define('MAX_LEVEL', 5);
recursion($to);

$to->setBlock('block2')->setBlock('block2')->setBlock('block2');

$a = array(
	'block3' => array(
		'LEVEL' => 1,
		'block3' => array(
			'LEVEL' => 2,
			'block3' => array(
				'LEVEL' => 3,
			),
		),
	),	
);
$to->setVarArray($a);

$to->showOutput();

function recursion(&$tmpl, $level = 0)
{
	$rows = rand(1, 3);
	for($i=1; $i<=$rows; $i++) {
		$b = $tmpl->setBlock('block');
		$b->setVariable('LEVEL', $level);
		$go_deep = !$level || rand(0, 1);
		if($go_deep && $level < MAX_LEVEL) recursion($b, ++$level);
	}
}
