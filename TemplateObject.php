<?php
/**
 * Another simple template parser.
 * Based on features of HTML_Template_IT by Ulf Wendel, Pierre-Alain Joye.
 *
 * @author Rebel
 * @copyright (c) 2017 Aleksandr.ru
 * @link https://github.com/Aleksandr-ru/TemplateObject Project page
 * @link https://pear.php.net/package/HTML_Template_IT Original HTML_Template_IT
 * @link http://aleksandr.ru Author's website
 *
 * @version 2.5
 */
class TemplateObject
{
	/**
	 * The default filter to apply if no filter provided with variable
	 */
	const DEFAULT_FILTER = 'html';

	/**
	 * Regexp to parse markup like
	 * 
	 * ```
	 * <!-- EXTEND ../relative/path/to/file.html -->
	 * ```
	 */
	const REGEXP_EXTEND = '@^\s*<!--\s*EXTEND\s+(\S+)\s*-->@imU';

	/**
	 * Regexp to parse markup like
	 *
	 * ```
	 * <!-- INCLUDE ../relative/path/to/file.html -->
	 * ```
	 */
	const REGEXP_INCLUDE = '@<!--\s*INCLUDE\s+(\S+)\s*-->@iU';

	/**
	 * Regexp to parse markup like
	 * 
	 * ```
	 * <!-- BEGIN block -->
	 * <a href="{{VAR}}">repeatable content</a>
	 * <!-- EMPTY block -->
	 * <img src="nothing.png" alt="In case of empty block" />
	 * <!-- END block -->
	 * ```
	 */
	const REGEXP_BLOCK = '@<!--\s*BEGIN\s+(?P<name>[a-z][a-z0-9_]*)(?P<options>(\s+[a-z0-9_]+)*)\s*-->(?P<data>.*)(<!--\s*EMPTY\s\g{name}\s*-->(?P<empty>.*))?<!--\s*END\s\g{name}\s*-->@ismU';

	/**
	 * Regexp to parse markup like
	 *
	 * ```
	 * <!-- RECURSION blockname -->
	 * ```
	 */
	const REGEXP_RECURSION = '@<!--\s*RECURSION\s+(?P<name>[a-z][a-z0-9_]*)\s*-->@iU';

	/**
	 * Regexp to parse markup like
	 *
	 * `{{VAR}}` `{{VAR|raw}}` `{{VAR|html}}` `{{VAR|js}}` `{{VAR|html|nl2br}}`
	 */
	const REGEXP_VAR = '@{{(?P<name>[a-z][a-z0-9_]*)(?P<filter>\|[a-z][a-z0-9\|]*)?}}@i';

	/**
	 * Check value expression for addFilter function
	 * @see TemplateObject::addFilter() addFilter function
	 */
	const REGEXP_FILTER = '@^[a-z][a-z0-9]*$@';
	
	/**
	 * Placeholder during parse time
	 */
	const PLACEHOLDER_BLOCK = '<!--__templateobjectblock[%s]__-->';

	/**
	 * Placeholder during parse time
	 */
	const PLACEHOLDER_VAR = '<!--__templateobjectvariable[%s|%s]__-->';

	/**
	 * Indicates that block with this option should be prepended to others in setBlock
	 * @see TemplateObject::setBlock() setBlock function
	 */
	const BLOCKOPTION_RSORT = 'rsort';

    /**
     * Debug mode to suppress user-level errors
     * @var bool
     */
	public $debug = false;
	
	/**
	 * @var $tmpl
	 * @var $out
	 * the teplate and output holder
	 */
	protected $tmpl, $out;

	/**
	 * Original template holder for recursion
	 * @var string
	 */
	protected $template;


	/**
	 * @var $includes
	 * @var $base_dir
	 * array of included files and base dir for includes
	 */
	protected $includes, $base_dir;
	
	/**
	 * @var $blocks
	 * @var $blockdata
	 * containers for block markup and block's data
	 */
	protected $blocks, $blockdata;
	
	/**	
	 * @var $extended
	 * @var $extend_blocks
	 * array of extended files and container for block extenders 
	 */
	protected $extended, $extend_blocks;
	
	/**
	 * @var $variables
	 * @var $vardata
	 * containers for variable markup and variable's data
	 */
	protected $variables, $vardata;

	/**
	 * @var $vardata_global
	 * container for global variable's data
	 */
	protected $vardata_global;
	
	/**	
	* @var $filters
	* list of available filters
	* array(filter => callback, ...)
	*/
	protected $filters = array(
		'raw'   => '', // do nothing
		'html'  => 'htmlspecialchars',
		'nl2br' => 'nl2br',
		'js'    => 'addslashes',
	);
	
	/**
	 * Load template from file.
	 * 
	 * @param string $file Path to template to be opened
	 * 
	 * @return TemplateObject A newly created object for given file
	 */
	static function loadTemplate($file)
	{
		$dir = dirname(realpath($file));		
		$filename = basename($file); 
		
		$data = file_get_contents($dir . DIRECTORY_SEPARATOR . $filename);		
		if(!$data) {
			return FALSE;
		}
		else {
			return new self($data, $dir);
		}
	}
	
	/**
	 * Constructor.
	 * 
	 * @param string $data In case of template from variable or DB
	 * @param string $base_dir Working directory for template
	 */
	function __construct($data = '', $base_dir = '')
	{
		$this->__destruct();
		
		$this->template = $this->tmpl = $data;
		$this->base_dir = $base_dir ? $base_dir : getcwd();		
		
		$this->parseExtend();
		$this->parseIncludes();
		$this->parseBlocks();
		$this->parseRecursion();
		$this->parseVariables();
	}
	
	/**
	 * Free and reset resources.
	 */
	function __destruct()		
	{
		$this->template = '';
		$this->tmpl = $this->out = '';
		$this->includes = array();
		$this->base_dir = '';
		$this->blocks = $this->blockdata = array();
		$this->variables = $this->vardata = array();
		$this->vardata_global = array();
		$this->extended = $this->extend_blocks = array();
	}
		
	/**
	 * Returns all blocks found in the template.
	 * Only 1st level of blocks are returned, not recursive.
	 *
	 * @return array List of found block names
	 */
	function getBlocks()
	{
		return array_keys($this->blocks);
	}

	/**
	 * Checks the existence of a block.
	 * Only 1st level of blocks are checked, not recursive.
	 * 
	 * @param string $blockname name of the block
	 * @return bool whether block exists in template
	 */
	function hasBlock($blockname)
	{
		return array_key_exists($blockname, $this->blocks);
	}
	
	/**
	 * Returns all variables found in template.
	 * Only variables outside of blocks are returned.
	 *
	 * @return array List of found variable names
	 */
	function getVariables()
	{
		return array_keys($this->variables);
	}

	/**
	 * Checks the existence of a variable.
	 * Only variables outside of blocks are checked.
	 *
	 * @param string $varname name of the variable
	 * @return bool whether variable exists in template
	 */
	function hasVariable($varname)
	{
		return array_key_exists($varname, $this->variables);
	}

	/**
	 * Set block for usage (add a new block to markup and return handle).
	 * 
	 * @param string $blockname Name of block in markup
	 * 
	 * @return TemplateObject Block object
	 */
	function setBlock($blockname)
	{
		if(!isset($this->blocks[$blockname])) {
			$this->debug and trigger_error("Unknown block '$blockname'", E_USER_NOTICE);
			return FALSE;
		}
		$this->out = '';
		$block = new self($this->blocks[$blockname]['data'], $this->base_dir);
		$block->debug = $this->debug;
		foreach($this->filters as $filter => $callback) $block->addFilter($filter, $callback, TRUE);
		foreach($this->vardata_global as $var => $val) $block->setGlobalVariable($var, $val);
		
		if(isset($this->blockdata[$blockname]) && in_array(self::BLOCKOPTION_RSORT, $this->blocks[$blockname]['options'])) {
			array_unshift($this->blockdata[$blockname], $block);
			return $this->blockdata[$blockname][0];
		}
		else {
			return $this->blockdata[$blockname][] = $block;
		}
	}

	/**
	 * Set a variable in global scope.
	 *
	 * @param string $var Name of the variable
	 * @param string $val Value of the variable
	 *
	 * @return bool Variable exists in the template
	 */
	function setGlobalVariable($var, $val)
	{
		$this->vardata_global[$var] = $val;
		$this->out = '';
		return isset($this->variables[$var]);
	}
	
	/**
	 * Set the variable in markup.
	 *
	 * Triggers E_USER_NOTICE if variable was not found.
	 *
	 * @param string $var Name of the variable
	 * @param string $val Value of the variable
	 * 
	 * @return bool Whether variable was found
	 */
	function setVariable($var, $val)
	{					
		if(!isset($this->variables[$var])) {
            $this->debug and trigger_error("Unknown variable '$var'", E_USER_NOTICE);
			return FALSE;
		}		
		$this->vardata[$var] = $val;		
		$this->out = '';
		return TRUE;
	}
	
	/**
	 * Set variables from an array like
	 * 
	 * <pre>
	 * array(
	 *	'VAR1' => 'value',
	 *	'VAR2' => 'another value',
	 *	'singleblock' => array('BLOCKVAR1' => 'value1', 'BLOCKVAR2' => 'value2', ...),
	 *	'multiblock' => array(
	 *		[0] => array('VAR1' => 'val1', 'VAR2' => 'val2'),
	 *		[1] => array('VAR1' => 'val3', 'VAR2' => 'val4'),
	 *	),
	 *	'emptyblock' => NULL,
	 *	...)
	 * </pre>
	 *
	 * @param array $arr An array of variables and blocks data
	 */
	function setVarArray($arr)
	{
		foreach ($arr as $key => $value) {
			if(is_array($value) && self::array_has_string_keys($value)) { // singleblock
				$b = $this->setBlock($key) and $b->setVarArray($value);
			}
			elseif(is_array($value)) { // multiblock
				foreach($value as $vv) if(self::array_has_string_keys($vv)) {
					$b = $this->setBlock($key) and $b->setVarArray($vv);
				}
			}
			elseif(is_null($value)) { // emptyblock
				$this->setBlock($key);
			}
			else {
				$this->setVariable($key, $value);	
			}			
		}
	}
	
	/**
	 * Check whether the array has non-integer keys.
	 * @link http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
	 *
	 * @param array $array
	 *
	 * @return bool If the array has at leas one string key
	 */
    protected static function array_has_string_keys($array)
	{
		return count(array_filter(array_keys($array), 'is_string')) > 0;
	}
	
	/**
	 * Get parsed template with all data set.
	 * 
	 * @return string
	 */
	function getOutput()
	{
		if($this->out) return $this->out;
		
		$this->out = $this->tmpl;
		//$empty = TRUE;
		$vardata = array_merge($this->vardata_global, $this->vardata);
		
		if($this->variables) foreach ($this->variables as $var => $vv) {						
			foreach($vv as $filter) {
				$search = sprintf(self::PLACEHOLDER_VAR, $var, $filter);								
				if(isset($vardata[$var])) {
					//$empty = FALSE;
					$replace = $this->applyVarFilter($vardata[$var], $filter);
					$this->out = str_replace($search, $replace, $this->out);
				}
				else {					
					$this->out = str_replace($search, '', $this->out);
				}		
			}
		}
		
		if($this->blocks) foreach($this->blocks as $blockname => $block) {			
			$search = sprintf(self::PLACEHOLDER_BLOCK, $blockname);
			$replace = '';
			if(isset($this->blockdata[$blockname])) {				
				foreach ($this->blockdata[$blockname] as $b) {
					$replace .= $b->getOutput();
				}
				//if($replace) $empty = FALSE;
			}
			if(!$replace && isset($block['empty'])) {
				//$empty = FALSE;
				$replace = $block['empty'];				
			}
			$this->out = str_replace($search, $replace, $this->out);
		} 
		
		return $this->out;
	}
	
	/**
	 * Print parsed template with all data set.
	 */
	function showOutput()
	{
		echo $this->getOutput();	
	}
	
	/**
	 * Apply given var filter parameters to a value
	 * 
	 * @param string $value
	 * @param string $filter
	 *
	 * @return string
	 */
	protected function applyVarFilter($value, $filter)
	{		
		$filter = explode('|', $filter ? $filter : self::DEFAULT_FILTER);
				
		while($f = array_shift($filter)) {						
			if(isset($this->filters[$f])) {
				if(!$this->filters[$f]) {
					// raw, do nothing
				}
				elseif(is_callable($this->filters[$f])) {
					$value = call_user_func($this->filters[$f], $value);	
				}
				else {
                    $this->debug and trigger_error("Filter function for '$f' is not callable!", E_USER_WARNING);
					return FALSE;
				}
			}
			else {
                $this->debug and trigger_error("Unknown filter '$f'", E_USER_NOTICE);
				return FALSE;
			}
		}
		return $value;
	}
	
	/**
	 * Parse the EXTEND directive and convert template
	 */
	protected function parseExtend()
	{
		$matches = array();
		while(preg_match(self::REGEXP_EXTEND, $this->tmpl, $matches)) {						
			$file = realpath($this->base_dir . DIRECTORY_SEPARATOR . $matches[1]);			
			if(in_array($file, $this->extended)) {
				throw new Exception("Recursive extending of '$file'");			
			}
			$this->extended[] = $file;
			
			$matches = array();
			preg_match_all(self::REGEXP_BLOCK, $this->tmpl, $matches, PREG_SET_ORDER);
			while($m = array_shift($matches)) {
				$this->extend_blocks[$m['name']] = $m['data'];				
			}
			$this->tmpl = file_get_contents($file);
			$this->base_dir = dirname($file);
			$this->extendBlocks();
		}		
	}
	
	/**
	 * Parse markup and replace blocks with extenders
	 */	
	protected function extendBlocks()
	{
		$this->tmpl = preg_replace_callback(self::REGEXP_BLOCK, array($this, 'extendBlocksCallback'), $this->tmpl);
	}
	
	/**
	 * Callback for extendBlocks function
	 * Replaces a block with its extender if present
	 * 
	 * @param array $arr data from preg_replace_callback
	 * 
	 * @return string
	 * @see extendBlocks()
	 */
	protected function extendBlocksCallback($arr)
	{		
		if(isset($this->extend_blocks[$arr['name']])) {
			return $this->extend_blocks[$arr['name']];
		}
		else {
			return $arr[0];
		}
	}

	/**
	 * Parse included templates recursievly and puts them to the main template
	 */
	protected function parseIncludes()
	{
		$count = 0;
		$this->tmpl = preg_replace_callback(self::REGEXP_INCLUDE, array($this, 'parseIncludeCallback'), $this->tmpl, -1, $count);
		if($count) {
			$this->parseExtend();
			$this->parseIncludes();
		}
	}
	
	/**
	 * Callback for parseIncludes function
	 * Checks the included file for recursion and return its contents
	 * 
	 * @param array $arr data from preg_replace_callback
	 * 
	 * @return string
     * @throws Exception
	 * @see parseIncludes()
	 */
	protected function parseIncludeCallback($arr)
	{
		$includefile = realpath($this->base_dir . DIRECTORY_SEPARATOR . $arr[1]);
        if($includefile === FALSE) {
            $this->debug and trigger_error("Failed to locate include '{$arr[1]}'", E_USER_NOTICE);
            return '';
        }
		elseif(in_array($includefile, $this->includes)) {
			throw new Exception("Recursive inclusion of '$includefile'");			
		}
		$this->includes[] = $includefile;
		return file_get_contents($includefile);
	}
	
	/**
	 * Parse block markup and replace blocks with placeholders
	 */	
	protected function parseBlocks()
	{
		$this->tmpl = preg_replace_callback(self::REGEXP_BLOCK, array($this, 'parseBlockCallback'), $this->tmpl);
	}
	
	/**
	 * Callback for parseBlocks function
	 * Adds a block data and replaces markup with placeholder
	 *
	 * @param array $arr data from preg_replace_callback
	 * 
	 * @return string
	 * @see parseBlocks()
	 */
	protected function parseBlockCallback($arr)
	{	            
		$this->blocks[$arr['name']] = array(
			'data' => $arr['data'],
			'empty' => isset($arr['empty']) ? $arr['empty'] : '',
			'options' => preg_split("@\s+@", strtolower($arr['options']), -1, PREG_SPLIT_NO_EMPTY),
		);		
		return sprintf(self::PLACEHOLDER_BLOCK, $arr['name']);
	}

	/**
	 * Parse recursion markup and replace it with block placeholdes
	 */
	protected function parseRecursion()
	{
		$this->tmpl = preg_replace_callback(self::REGEXP_RECURSION, array($this, 'parseRecursionCallback'), $this->tmpl);
	}

	/**
	 * Callback for parseRecursion function
	 * Adds a recursive block and replaces markup with placeholder
	 *
	 * @param array $arr data from preg_replace_callback
	 *
	 * @return string
	 * @see parseRecursion()
	 */
	protected function parseRecursionCallback($arr)
	{
		$this->blocks[$arr['name']] = array(
			'data' => $this->template,
			'empty' => '',
			'options' => array(),
		);
		return sprintf(self::PLACEHOLDER_BLOCK, $arr['name']);
	}
	
	/**
	 * Parse variable markup and replace it with placeholders
	 */
	protected function parseVariables()
	{		
		$this->tmpl = preg_replace_callback(self::REGEXP_VAR, array($this, 'parseVarCallback'), $this->tmpl);
	}
	
	/**
	 * Callback for parseVariables function
	 * Adds a variable data and replaces it with placeholder
	 * 
	 * @param array $arr data from preg_replace_callback
	 * 
	 * @return string
	 * @see parseVariables()
	 */
	protected function parseVarCallback($arr)
	{
		$filter = isset($arr['filter']) ? strtolower(trim($arr['filter'], '|')) : '';
		if(!isset($this->variables[$arr['name']]) || !in_array($filter, $this->variables[$arr['name']])) {
			$this->variables[$arr['name']][] = $filter;
		}
		return sprintf(self::PLACEHOLDER_VAR, $arr['name'], $filter);
	}
	
	/**
	 * Add (or replace) a filer for variables.
	 * 
	 * Triggers E_USER_NOTICE if filter already exists and no $overwrite.
	 * Triggers E_USER_NOTICE when given $callback is not callable.
	 * 
	 * @param string $filter Name of filter
	 * @param callable $callback A callback function
	 * @param bool $overwrite Whether to overwrite an existing filter
	 *
	 * @return bool Result of adding filter
	 */
	function addFilter($filter, $callback, $overwrite = FALSE)
	{
		if(!preg_match(self::REGEXP_FILTER, $filter)) {
            $this->debug and trigger_error("Wrong filter '$filter'", E_USER_NOTICE);
			return FALSE;
		}
		elseif(!$overwrite && isset($this->filters[$filter])) {
            $this->debug and trigger_error("Filter '$filter' already exists, use overwrite to force", E_USER_NOTICE);
			return FALSE;
		}
		if($callback && !is_callable($callback)) {
            $this->debug and trigger_error("Callback is not callable for filter '$filter'", E_USER_NOTICE);
			return FALSE;
		}
		$this->out = '';
		$this->filters[$filter] = $callback;
		return TRUE;
	}
	
	/**
	 * Remove an existing filter.
	 * Triggers E_USER_NOTICE if filter does not exists.
	 * 
	 * @param string $filter Name of filter
	 *
	 * @return bool Result of removing filter
	 */
	function removeFilter($filter)
	{
		if(!isset($this->filters[$filter])) {
            $this->debug and trigger_error("Filter '$filter' does not exists", E_USER_NOTICE);
			return FALSE;
		}
		unset($this->filters[$filter]);
		return TRUE;
	}
}
