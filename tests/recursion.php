<?php
require('../TemplateObject.php');
$to = TemplateObject::loadTemplate('recursion.html');

define('MAX_LEVEL', 5);
recursion($to);

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