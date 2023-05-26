<?php
echo "<h1>TemplateObject tests</h1>";
$files = scandir('.');
foreach ($files as $file) {
    if (substr($file, -4) == '.php' && $file != basename(__FILE__))
    echo "<p><a href='$file'>$file</a> [<a href='$file?text'>text</a>]</p>";
}
