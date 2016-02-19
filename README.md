# TemplateObject
Another simple template parser

## Markup example 
*page.html + header.html + foter.html with another markup*
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