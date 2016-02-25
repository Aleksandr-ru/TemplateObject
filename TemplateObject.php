<?php
/**
 * Another simple template parser
 * @author Rebel
 * @copyright (c) 2016 Aleksandr.ru
 * @version 1.1
 * @link https://github.com/Aleksandr-ru/TemplateObject
 * 
 * Based on features of HTML_Template_IT by Ulf Wendel, Pierre-Alain Joye
 * @link https://pear.php.net/package/HTML_Template_IT 
 * 
 * Version history
 * 1.0
 * 1.1 added filter support for variables {{VAR|raw}} {{VAR|html}} {{VAR|js}}
 */
class TemplateObject
{
	/**
	 * @const ESCAPE_*
	 * callback constatnts to escape variable data
	 */	
	const ESCAPE_HTML = 'htmlspecialchars';
	const ESCAPE_JS = 'addslashes';
	
	/**
	* @const FILTER_*
	* variable filters for  {{VAR|html}}
	*/
	const FILTER_NONE = 'raw';
	const FILTER_HTML = 'html';
	const FILTER_JS = 'js';
	
	/**
	 * @const REGEXP_INCLUDE
	 * <!-- INCLUDE ../relative/path/to/file.html -->
	 * 
	 * @const REGEXP_BLOCK
	 * <!-- BEGIN block -->
	 * <a href="{{VAR}}">repeatable content</a>
	 * <!-- EMPTY block -->
	 * <img src="{{SRC}}" alt="In case of empty block" />
	 * <!-- END block -->
	 * 
	 * @const REGEXP_VAR
	 * {{VAR}} {{VAR|raw}} {{VAR|html}} {{VAR|js}}
	 */
	const REGEXP_INCLUDE = '@<!--\s*INCLUDE\s(.+)\s*-->@iU';
	const REGEXP_BLOCK = '@<!--\s*BEGIN\s(?P<name>[a-z][a-z0-9_]+)\s*-->(?P<data>.*)(<!--\s*EMPTY\s\g{name}\s*-->(?P<empty>.*))?<!--\s*END\s\g{name}\s*-->@ismU';
	const REGEXP_VAR = '@{{(?P<name>[a-z][a-z0-9_]+)(\|(?P<filter>[a-z]+))?}}@i';
	
	/**
	 * @const PLACEHOLDER_BLOCK
	 * @const PLACEHOLDER_VAR
	 * placeholders during parse time
	 */
	const PLACEHOLDER_BLOCK = '<!--__templateobjectblock[%s]__-->';
	const PLACEHOLDER_VAR = '<!--__templateobjectvariable[%s|%s]__-->';
	
	/**
	 * @var $tmpl
	 * @var $out
	 * the teplate and output holder
	 */
	protected $tmpl, $out;
	
	/**
	 * @var $includes
	 * array of included files
	 */
	protected $includes;
	
	/**
	 * @var $blocks
	 * @var $blockdata
	 * containers for block markup and block's data
	 */
	protected $blocks, $blockdata;
	
	/**
	 * @var $variables
	 * @var $vardata
	 * containers for variable markup and variable's data
	 */
	protected $variables, $vardata;
	
	/**
	* @var 
	* container for default variable filters in setVariable
	* @see setVariable()
	*/
	protected $varfilter_default;
	
	/**
	 * constructor
	 * @param string $data in case of template from variable or DB
	 */
	function __construct($data = '')
	{
		$this->__destruct();
		
		if($data) {
			$this->tmpl = $data;
		
			$this->parseIncludes();
			$this->parseBlocks();
			$this->parseVariables();
		}
	}
	
	/**
	 * free and reset resources
	 * 
	 * @return void
	 */
	function __destruct()		
	{
		$this->tmpl = $this->out = '';
		$this->includes = array();
		$this->blocks = $this->blockdata = array();
		$this->vardata = $this->vardata = array();
		$this->varfilter_default = array();
	}
	
	/**
	 * load template from file
	 * @param string $file path to template to be opened
	 * 
	 * @return bool
	 */
	function loadTemplate($file)
	{
		$data = file_get_contents($file);
		if(!$data) return FALSE;
		$this->__construct($data);
		return TRUE;
	}
	
	/**
	 * Set block for usage (add a new block to markup and return handle)
	 * @param string $blockname
	 * 
	 * @return TemplateObject object
	 */
	function setBlock($blockname)
	{
		if(!isset($this->blocks[$blockname])) {
			trigger_error("Unknown block '$blockname'", E_USER_WARNING);
			return FALSE;
		}
		$this->out = '';
		return $this->blockdata[$blockname][] = new self($this->blocks[$blockname]['data']);
	}
	
	/**
	 * Set the variable in markup
	 * @param string $var name of the variable
	 * @param string $val value of the variable
	 * @param string $filter default filter for variable
	 * 
	 * @return bool
	 */
	function setVariable($var, $val, $filter = self::FILTER_HTML)
	{					
		if(!isset($this->variables[$var])) {
			trigger_error("Unknown variable '$var'", E_USER_WARNING);			
			return FALSE;
		}		
		$this->vardata[$var] = $val;
		$this->varfilter_default[$var] = $filter;
		$this->out = '';
		return TRUE;
	}
	
	
	/**
	 * Set variables from array('VAR1' => 'value', 
	 * 							'VAR2' => 'another value', 
	 * 							'sigleblock' => array('BLOCKVAR1' => 'value1', 'BLOCKVAR2' => 'value2', ...),
	 * 							'multiblock' => array(
	 * 												[0] => array('VAR1' => 'val1', 'VAR2' => 'val2'),
	 * 												[1] => array('VAR1' => 'val3', 'VAR2' => 'val4'),
	 * 											),
	 * 							...
	 * 							)
	 * @param array $arr
	 * @param string $filter default filter for variable
	 * 
	 * @return bool
	 */
	function setVarArray($arr, $filter = self::FILTER_HTML)
	{
		foreach ($arr as $key => $value) {
			if(is_array($value) && $this->array_has_string_keys($value)) { // sigleblock
				$this->setBlock($key)->setVarArray($value, $filter);				
			}
			elseif(is_array($value)) { // multiblock
				foreach($value as $vv) {
					$this->setBlock($key)->setVarArray($vv, $filter);
				}
			}
			else {
				$this->setVariable($key, $value, $filter);	
			}			
		}
	}
	
	/**
	* check whether the array has non-integer keys
	* http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
	* @param array $array
	* 
	* @return bool
	*/
	static function array_has_string_keys($array)
	{
		return count(array_filter(array_keys($array), 'is_string')) > 0;
	}
	
	/**
	 * Get parsed template with all data set
	 * 
	 * @return string
	 */
	function getOutput()
	{
		if($this->out) return $this->out;
		
		$this->out = $this->tmpl;
		$empty = TRUE;
		
		if($this->variables) foreach ($this->variables as $var => $vv) {						
			foreach($vv as $filter) {
				$search = sprintf(self::PLACEHOLDER_VAR, $var, $filter);				
				if(isset($this->vardata[$var])) {
					$empty = FALSE;
					$replace = $this->applyVarFilter($this->vardata[$var], $filter, $this->varfilter_default[$var]);
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
				$blockdata = $this->blockdata[$blockname];				
				foreach ($this->blockdata[$blockname] as $b) {
					$replace .= $b->getOutput();
				}
				if($replace) $empty = FALSE;
			}
			if(!$replace && isset($block['empty'])) {
				$empty = FALSE;
				$replace = $block['empty'];				
			}
			$this->out = str_replace($search, $replace, $this->out);
		} 
		
		if($empty) trigger_error("Template is empty!", E_USER_NOTICE);
		return $this->out;
	}
	
	/**
	 * Print parsed template with all data set
	 * 
	 * @return void
	 */
	function showOutput()
	{
		echo $this->getOutput();	
	}
	
	/**
	* Apply given var filter parameters to a value
	* @param string $value
	* @param string $filter
	* @param string $default
	* 
	* @return string
	*/
	protected function applyVarFilter($value, $filter, $default)
	{
		$filter = $filter ? $filter : $default;
		switch($filter) {
			case self::FILTER_NONE:
				return $value;
			case self::FILTER_JS:
				return call_user_func(self::ESCAPE_JS, $value);
			case self::FILTER_HTML:
			default:
				return call_user_func(self::ESCAPE_HTML, $value);
		}		
	}
	
	/**
	 * Parse included templates recursievly and puts them to the main template
	 * @return void
	 */
	protected function parseIncludes()
	{		
		$this->tmpl = preg_replace_callback(self::REGEXP_INCLUDE, array($this, 'parseIncludeCallback'), $this->tmpl, -1, $count);
		if($count) $this->parseIncludes();
	}
	
	/**
	 * Callback for parseIncludes function
	 * Checks the included file for recursion and return its contents
	 * @param array $arr data from preg_replace_callback
	 * 
	 * @return string
	 * @see parseIncludes()
	 */
	protected function parseIncludeCallback($arr)
	{		
		$includefile = realpath(dirname($arr[1])).'/'.basename($arr[1]);
		if(in_array($includefile, $this->includes)) {
			throw new Exception("Recursive inclusion '$includefile'");			
		}
		$this->includes[] = $includefile;
		return file_get_contents($includefile);
	}
	
	/**
	 * Parse block markup and replace blocks with placeholders
	 * 
	 * @return void
	 */	
	protected function parseBlocks()
	{
		$this->tmpl = preg_replace_callback(self::REGEXP_BLOCK, array($this, 'parseBlockCallback'), $this->tmpl);		
	}
	
	/**
	 * Callback for parseBlocks function
	 * Adds a block data and replaces it with placeholder
	 * @param array $arr data from preg_replace_callback
	 * 
	 * @return string
	 * @see parseBlocks()
	 */
	protected function parseBlockCallback($arr)
	{		
		$this->blocks[$arr['name']] = array(
			'data' => $arr['data'],
			'empty' => @$arr['empty']
		);		
		return sprintf(self::PLACEHOLDER_BLOCK, $arr['name']);
	}
	
	/**
	 * Parse variable markup and replace it with placeholders
	 * 
	 * @return void
	 */
	protected function parseVariables()
	{		
		$this->tmpl = preg_replace_callback(self::REGEXP_VAR, array($this, 'parseVarCallback'), $this->tmpl);			
	}
	
	/**
	 * Callback for parseVariables function
	 * Adds a variable data and replaces it with placeholder
	 * @param array $arr data from preg_replace_callback
	 * 
	 * @return string
	 * @see parseVariables()
	 */
	protected function parseVarCallback($arr)
	{		
		$filter = isset($arr['filter']) ? strtolower($arr['filter']) : '';
		if(!@in_array($filter, $this->variables[$arr['name']])) $this->variables[$arr['name']][] = $filter;	
		return sprintf(self::PLACEHOLDER_VAR, $arr['name'], $filter);
	}
}
?>