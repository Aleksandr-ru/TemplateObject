# TemplateObject
Another simple template parser. Also available via composer:

```
composer require aleksandr.ru/template-object
```

### Features

* Blocks of markup as object
* Block repeat (setBlock appends new block and returns handle to it)
* Recursive blocks (see example below)
* Empty block placeholders
* Independent variables in blocks and main template
* Variable data escaping (filtering) in template, see markup below
* Variable filtering manipulation: add, replace, remove custom filters
* Includes and protection against recursive includes
* Extending templates and protection against recursive extending

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

## Recursive blocks
Since 2.4 there is an abilty to make your blocks recursive. For example:

*recursive.html*

```
<body>
	<!-- BEGIN blockname -->
	[a recursive block]
	<!-- RECURSION blockname -->
	<!-- END blockname -->
</body>
```

The code:

```
$to = TemplateObject::loadTemplate('recursive.html');
$to->setBlock('blockname')->setBlock('blockname')->setBlock('blockname');
$to->showOutput();
```

Output:

```
[a recursive block] [a recursive block] [a recursive block]
```


## Function quick reference

### *static* loadTemplate(string  $file) : TemplateObject

Load template from file.

### __construct(string  $data = '', string  $base_dir = '')

Constructor.

### __destruct()

Free and reset resources.

### getBlocks() : array

Returns all blocks found in the template.
Only 1st level of blocks are returned, not recursive.

### getVariables() : array

Returns all variables found in template.
Only variables outside of blocks are returned.

### setBlock(string  $blockname) : TemplateObject

Set block for usage (add a new block to markup and return handle).

### setGlobalVariable(string  $var, string  $val) : boolean

Set a variable in global scope.

### setVariable(string  $var, string  $val) : boolean

Set the variable in markup.
Triggers E_USER_NOTICE if variable was not found.

### setVarArray(array  $arr)

Set variables from an array like

```
array(
'VAR1' => 'value',
'VAR2' => 'another value',
'singleblock' => array('BLOCKVAR1' => 'value1', 'BLOCKVAR2' => 'value2', ...),
'multiblock' => array(
    [0] => array('VAR1' => 'val1', 'VAR2' => 'val2'),
    [1] => array('VAR1' => 'val3', 'VAR2' => 'val4'),
),
'emptyblock' => NULL,
...)
```

### getOutput() : string

Get parsed template with all data set.

### showOutput()

Print parsed template with all data set.

### addFilter(string  $filter, callable  $callback, boolean  $overwrite = FALSE) : boolean

Add (or replace) a filer for variables.
Triggers E_USER_NOTICE if filter already exists and no $overwrite. Triggers E_USER_NOTICE when given $callback is not callable.

### removeFilter(string  $filter) : boolean

Remove an existing filter.
Triggers E_USER_NOTICE if filter does not exists.

### getForcedFilter() : string

Since 2.7. Get current forced filter.

### setForcedFilter(string  $filter) : boolean

Since 2.7. Set new forced filer. By default, is set to "html".
Triggers E_USER_WARNING if filter does not exist.

Forced filter is filter to be applied first if there is no "raw" filter and no same filter is set for variable.
Forced filter can contain several elements like `html|nl2br`, each will be prepended to variable's filters, if is not set.

This mechanism may cause some backwards compatibility issues:
before 2.7 `{{VAR}}` means `{{VAR|html}}` and `{{VAR|js}}` means only `{{VAR|js}}` (no html applied),
since 2.7 `{{VAR}}` means `{{VAR|html}}` and `{{VAR|js}}` means `{{VAR|html|js}}` (html is forcefully prepended).
To get back old behaviour of `{{VAR|js}}` you need to add "raw" filter like `{{VAR|raw|js}}`.

## More documentation
See PhpDoc in code.

## Version history
 * 2.7 Forced filter instead of default, applies until raw is set
 * 2.6 Removed side effect of preserving blocks by setting it to a non-assoc array
 * 2.5 Template processing boost and new option `public $debug = false`
 * 2.4 Recursive blocks via new markup `<!-- RECURSION blockname -->`
 * 2.3 Preserve empty blocks in setVarArray via `'emptyblock' => NULL`
 * 2.2 Block options: `<!-- BEGIN myblock rsort -->` this blocks will be outputted in reversed order, see BLOCKOPTION_* constants
 * 2.1 Global variables: inherited to all child blocks
 * 2.0 Now one template can extend another template by replacing it's blocks with own content
 * 1.3 Ability to get variables and blocks from loaded template
 * 1.2 Multiple filter support like `{{VAR|html|nl2br}}`
 * 1.1 Added filter support for variables `{{VAR|raw}}` `{{VAR|html}}` `{{VAR|js}}`
 * 1.0 Initial
