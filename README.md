# TemplateObject
Another simple template parser

### Features

* Blocks of markup as object
* Block repeat (setBlock appends new block and returns handle to it)
* Empty block placeholders
* Independent variables in blocks and main template
* Variable data escaping (filtering) in template, see markup below
* Variable filtering manipulation: add, replace, remove custom filters
* Includes
* Cycling includes protection

## Markup example 
*header.html*
```
<!DOCTYPE html>
<html>
<head>
	<title>{{TITLE|raw}}</title>
	<script>
		alert('{{TITLE|js}}');
	</script>
</head>
<body>
```
*page.html*
```
<!-- INCLUDE header.html -->
<table>
<caption>{{TITLE}}</caption>
<tr>
	<th>Column-1</th>
	<th>Column-2</th>
</tr>
<!-- BEGIN row -->
<tr>
	<td>{{COL1}}</td>
	<td>{{COL2}}</td>
</tr>
<!-- EMPTY row -->
<tr>
	<td colspan=2>No data</td>
</tr>
<!-- END row -->
</table>
<!-- INCLUDE footer.html -->
```
*footer.html*
```
<p>{{MULTILINE|html|nl2br}}</p>
</body>
</html>
```
## Code example 
*in case of not usage the 'row' block, content of EMPTY will be shown*
```
// WARNING! Since 2.0 the loadTemplate become static and return TemplateObject
// this syntax is not valid any more
// $to = new TemplateObject();
// $to->loadTemplate('page.html');
// Please use following
$to = TemplateObject::loadTemplate('page.html');
$to->setVariable('TITLE', 'this is a title');
for($i=1; $i<=3; $i++) {
	$row = $to->setBlock('row');
	$row->setVariable('COL1', $i);
	$row->setVariable('COL2', "test-$i");
}
$string = "String with \"quotes\" and several lines\n second line\n thitd line";
$to->setVariable('MULTILINE', $string);
$to->showOutput();
```
## Extending templates
Since 2.0 there is an abilty to extend templates. For example:

*yeild.html*
```
<!DOCTYPE html>
<html>
<head>
	<title>{{TITLE}}</title>	
</head>
<body>
	<header>
		<!-- BEGIN head -->
			This content will be yeilded
		<!-- END head -->
	</header>
	
	<section>
		<!-- BEGIN content -->
			This content will be yeilded
		<!-- END content -->
	</section>
	
	<footer>
		<!-- BEGIN foot -->
			This content will be yeilded
		<!-- END foot -->
	</footer>
</body>
</html>
```
*extend.html*
```
<!-- EXTEND yeild.html -->

<!-- BEGIN head -->
	<p>This is the header</p>
<!-- END head -->

<!-- BEGIN content -->
	<table border="1">
	<caption>This is the content</caption>
	<tr>
		<th>Column-1</th>
		<th>Column-2</th>
	</tr>
	<!-- BEGIN row -->
	<tr>
		<td>{{COL|html}}</td>
		<td>{{COL|raw}}</td>
	</tr>
	<!-- EMPTY row -->
	<tr>
		<td colspan=2>No data</td>
	</tr>
	<!-- END row -->
	</table>
<!-- END content -->

<!-- BEGIN foot -->
	<p>This is the footer</p>
<!-- END foot -->
```
The code is the same:
```
$to = TemplateObject::loadTemplate('extend.html');
$to->setVariable('TITLE', "this is a 'title'");
for($i=1; $i<=3; $i++) {
    $row = $to->setBlock('row');    
    $row->setVariable('COL', "test \"$i\"");
}
$to->showOutput();
```
## More documentation
See function comments in the .php file
