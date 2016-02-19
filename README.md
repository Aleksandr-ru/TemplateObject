# TemplateObject
Another simple template parser

### Features

* Blocks of markup as object
* Block repeat (setBlock appends new block and returns handle to it)
* Empty block placeholders
* Independent variables in blocks and main template
* Variable data escaping (filtering) in setVariable and template, see markup below
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
</body>
</html>
```
## Code example 
*in case of not usage the 'row' block, content of EMPTY will be shown*
```
$to = new TemplateObject();
$to->loadTemplate('page.html');
$to->setVariable('TITLE', 'this is a title');
for($i=1; $i<=3; $i++) {
	$row = $to->setBlock('row');
	$row->setVariable('COL1', $i);
	$row->setVariable('COL2', "test-$i");
}
$to->showOutput();
```
## More documentation
See function comments in the .php file