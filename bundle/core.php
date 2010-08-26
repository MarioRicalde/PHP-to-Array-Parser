<?php
/*
 * Developed by Mario "Kuroir" Ricalde (http://kuroir.com/)
 */
/**
 * Compatibility
 */
if (!defined('T_ML_COMMENT')) {
   define('T_ML_COMMENT', T_COMMENT);
} 
else {
   define('T_DOC_COMMENT', T_ML_COMMENT);
}

include "parser.php";
include "dBug.php";

class Tm_Intellisense extends Tm_Intellisense_Core
{
	
	public function __construct()
	{
		$this->debug = TRUE;
		parent::__construct();
		$this->check_requeriments();
		$this->process_directory($this->directory);
		$this->filesTotalTime();
		$this->parse_files();
	}
}

class Tm_Intellisense_Core
{
	#region Variable Definition
	protected $cache;
	protected $files;
	protected $files_stamp;
	protected $plist;
	public $dialog;
	public $directory;
	public $message;
	public $min_characters;
	public $selection;
	public $stamp;
	public $parsedMethods;
	public $match_word;
	#endregion
	public function __construct()
	{
		$this->dialog		  = getenv('DIALOG');
		$this->directory	  = getenv('TM_DIRECTORY');
		$this->current_line	  = getenv('TM_CURRENT_LINE');
		$this->current_word	  = getenv('TM_CURRENT_WORD');
		$this->current_word	  = str_replace("$", "\\\$", $this->current_word);
		$this->current_file	  = getenv('TM_SELECTED_FILE');
		$this->min_characters = 3;
		$this->stamp		  = time();
		$this->message		  = array(
			"ns"  => "No word selected.",
			"nec" => "Not enough characters selected.",
			"nf"  => "No Method found.",
			"modfiles" => "Modified FIles"
			);
		$this->parser		  = new Tm_Php_Parser();
		if($this->debug) {
			$this->prepare_debug();
		}
	}
	
	public function parse_files()
	{
		$total = count($this->files);
		for ($i=0; $i < $total; $i++) {
			$parser = new Tm_Php_Parser;
			$output[$this->files[$i]['path']] = $parser->process($this->files[$i]['path']);
		}
		dump($output);
	}
	
	
	public function prepare_debug()
	{
		if ($this->debug) {
			$this->current_word = '     ';
			$this->current_line = '';
			$this->current_file = '../ncludable/test.php';
			$this->directory	= '../includable/';
		}
	}
	
	public function process()
	{

		$this->getCache();
		
		if($this->cache['last_modified'] < $this->files_stamp ){
			$this->parseFiles()->generatePlist()->saveCache();
		}
		// And there we go.
		$this->showDialog();
	}

	/**
	 * Return the Sum of all File timestamps.
	 *
	 * @param string $array 
	 * @return void
	 * @author Kuroir
	 */
	public function filesTotalTime()
	{
		$total = 0;
		foreach($this->files as $value)
		{
			$total += $value['last_modified'];
		}
		$this->files_stamp = $total;
	}

	/**
	 * Iterates through all the project php files. Returning the file absolute paths
	 * and last modified timestamp.
	 *
	 * @param string $directory path
	 * @return array
	 * @author Kuroir
	 */
	public function process_directory($directory = '')
	{
		if (is_dir($directory)) {
		for ($list = array(),$handle = opendir($directory); (FALSE !== ($file = readdir($handle)));) {
			if (($file != '.' && $file != '..') && (file_exists($path = $directory . '/' . $file))) {
				if (is_dir($path)) {
					$list = array_merge($list, $this->process_directory($path));
				} else {
					if( ! preg_match('/.*\.php$/', $file) || preg_match('/(.svn|log)(.lol)/', $file))
						continue;

					$entry['path']			= $directory . '/' .  $file;
					$entry['last_modified'] = filemtime($entry['path']);

					do if (!is_dir($path)) {
						if (strstr(pathinfo($path,PATHINFO_BASENAME),'log')) {
							if (!$entry['handle'] = fopen($path,'r'))
								$entry['handle'] = "FAIL";
						}
						break;
					} while (FALSE);
					$list[] = $entry;
				}
			}
		}
		closedir($handle);
		$this->files = $list;
		return $list;
		} else return FALSE;
	}

	public function check_requeriments()
	{
		if(empty($this->current_word))
			$this->tooltip($this->message['ns']);
		if(strlen($this->current_word) < $this->min_characters)
			$this->tooltip($this->message['nec']);
		return true;
	}

	public function tooltip($message = '')
	{
		die($message);
	}
}

/**
 * Run the Code
 */
new Tm_Intellisense;