<?php
/*
 * Developed by Mario "Kuroir" Ricalde (http://kuroir.com/)
 */

// TODO: On global scope only parse variable assignments.
// TODO: Set a flag for catching variables asignment withiin the methods (class).

// PHP Wrap Within function

class Tm_Php_Parser 
{
	#region Variable Definition
	private $output = array();
	private $source;
	private $filename;
	private $tokens;

	// Global Switches
	private $scope = 'global';
	private $visibility	   = 'public';
	
	// Variables
	private $curly_variable = FALSE;
	private $temp_variable = FALSE; // We use this to store a variable name, temporarily.
	private $is_variable_definition = FALSE;
	
	// Class Switches
	private $is_class = FALSE;
	private $class_name = '';
	private $class_name_declaration = FALSE;
	private $class_bracket_balance = 0;
	private $class_extends = NULL;
	private $extends_declaration = FALSE;
	
	// Function Switches
	private $is_function = FALSE;
	private $function_name = '';
	private $function_name_declaration = FALSE;
	private $function_bracket_balance = 0;
	private $function_attribute_default = FALSE;
	private $function_arguments = FALSE;
	
	// Constant Switches
	private $is_define = FALSE;
	private $is_const  = FALSE;
	
	// Private Debug
	protected $parser_debug = TRUE;
	
	// Buffer
	private $buffer;
	#endregion
	public function process($filename)
	{
		if ( ! is_string($filename)) {
			die("Parser: Expecting a string on process().");
		}
		$this->source = file_get_contents($filename);
		$this->tokens = token_get_all($this->source);
		// We don't need source anymore.
		$this->source = null;
		// Parse and store in $this->buffer
		$this->parse();
		// Unset Tokens.
		$this->tokens = null;
		// Unsnet repeated global variables.
		if (isset($this->buffer['global']['variables'])) {
			$this->buffer['global']['variables'] = array_unique($this->buffer['global']['variables']);
		}
		return $this->buffer;
	}
	
	/**
	 * Main process of the Parsing, it'll loop around all the tokens obtained 
	 * through the process() method.
	 *
	 * If the Token is a string, it's probably a single character puncturation
	 *
	 * @return void
	 * @author Kuroir
	 */
	public function parse()
	{
		foreach ($this->tokens as $token) {
			if (is_string($token)) {
				// Set the switchs.
				$this->set_switchs($token);
			} else {
				 // Parse the file and search for elements				
				$this->parse_token($token);
			}
		}
	}

	public function set_switchs($token)
	{
		/*
		 * Storing Variables
		 */
		if ($this->temp_variable) {
			if ($token === "=") {
				$this->is_variable_definition = TRUE;
			}
			if ($this->is_variable_definition) {
				$this->is_variable_definition = FALSE;
				$this->store($this->temp_variable, 'variable');
			}
		}
	   /*
		   Classes.
		*/
		if ($this->is_class) {
			/*
			   Count the brackets.
			   When the count reaches zero it should mean that the class is over.
			*/
			if ($token === '{') {
				$this->class_name_declaration = FALSE;
				$this->extends_declaration    = FALSE;
				$this->class_bracket_balance++;
			}
			if ($token === '}') {
				// Somehow curly variables get passed as a normal curly, so we 
				// use this to check.
				if ($this->curly_variable) {
					$this->curly_variable = FALSE;
					return;
				}
				$this->class_bracket_balance--;
				// Unset the switch.
				if ($this->class_bracket_balance === 0) {
					$this->is_class = FALSE;
					$this->scope = 'global';
					$this->visibility = 'public';
				}
			}
		}

		/*
		   Functions
		*/
		if ($this->is_function) {
			// Methods:
			// Set the Method arguments, function_name to false because the declaration ended.
			// Set function_arguments to TRUE because the function argument declaration began.
			if ($token === '(') {
				// function_arguments is set on the store method, after the function name storage.
				$this->function_name_declaration = FALSE;
				return true;
			} elseif ($token === ')') {
				$this->function_arguments = FALSE;
				$this->function_attribute_default = FALSE;
				return true;
			}
			
			// Store method arguments
			if ($this->function_arguments
				AND $this->is_function
				AND ($token === '=' OR $token === ',')) {
				/*
				 * This switch is for capturing anything set as default within
				 * the function declaration. So anything after within = , is 
				 * captured.
				 */
				if ($token === '=') {
					$this->function_attribute_default = TRUE;
				}
				if ($token === ',' OR $token === ')') {
					$this->function_attribute_default = FALSE;
				}
				if ($token === ')') {
					echo "LOL";
					$this->function_arguments = FALSE;
				}
				// If on a class add the visibility key
				if ($this->is_class) {
					$this->buffer[$this->scope][$this->class_name]['methods'][$this->visibility][$this->function_name] .= $token;
				} else {
					$this->buffer[$this->scope]['methods'][$this->function_name] .= $token;
				}
			}
			/*
			   Count the brackets.
			*/
			if ($token === '{') {
				$this->function_bracket_balance++;
			}
			if ($token === '}') {
				// Somehow curly variables get passed as a normal curly, so we 
				// use this to check.
				if ($this->curly_variable) {
					$this->curly_variable = FALSE;
					return;
				}
				$this->function_bracket_balance--;
				// Unset the switch.
				if ($this->class_bracket_balance === 0) {
					$this->is_function = FALSE;
					$this->visibility = 'public';
				}
			}
		}

		/*
			Defines
		*/
		if ($this->is_define) {
			
			if ($token === ",") {
				$this->is_define = FALSE;
			}
		}
	
		if ($this->is_const) {
			if ($token === "=") {
				$this->is_const = FALSE;
			}
		}
	}
	
	/**
	 *  This is for setting up flags.
	 */
	public function parse_token($token)
	{
		list($id, $word) = $token;
		switch ($id) {
			// Whitespace
			case T_COMMENT:
			case T_WHITESPACE:
				// We completely ignore whitespace.
				break;
			// Visibility of the methods or variables.
			case T_VAR:
			case T_PUBLIC:
			case T_PROTECTED :
			case T_PRIVATE:
			case T_STATIC:
				// We store the visibility declaration for later usage.
				$this->visibility = $word;
				break;
			// Variables
			case T_CURLY_OPEN:
				$this->curly_variable = TRUE;
				break;
			case T_VARIABLE:
				// Store the variables
				if ($this->function_arguments) {
					$this->store($word, 'function_attribute');
				} else {
					$this->temp_variable = $word;
				}
				break;
			// Classes
			case T_CLASS:
				// We're in class declaration, we set is_class to true and class_name_declaration
				// to store only the class name.
				$this->scope				  = 'class';
				$this->is_class				  = TRUE;
				$this->class_name_declaration = TRUE; 
				break;
			// Extends
			case T_EXTENDS:
				// There's a extends token, which means we're going to define the parent.
				$this->class_name_declaration = FALSE;
				$this->extends_declaration = TRUE;
				break;
			// Function
			case T_FUNCTION:
				// We're in a function declaration, we set is_function to true and function_nane_declaration
				// to store only the function name.
				$this->is_function				 = TRUE;
				$this->function_name_declaration = TRUE;
				break;
			case T_CONST:
				// TODO constants
				$this->is_const = TRUE;
				break;
			// Default Process.
			default:
				// Block for Define
				if ($word === 'define') {
					$this->is_define = TRUE;
					break;
				}
				$this->store($word);
				break;
		}
		return;

	}
	
	public function store($word, $type = 'default')
	{
		// Check that we're not using an empty word.
		// We don't use empty to prevent 0 from giving a false positive.
		 if ($word === '')
			 return;
	
		switch ($type) {
			/*
			 * Case to store the functions with their competent visibility if 
			 * applicable.
			 */
			case 'variable':
				if ( ! $this->is_function) {
					if ($this->is_class) {
						$this->buffer[$this->scope][$this->class_name]['variables'][$this->visibility][] = $word;
					} else {
						$this->buffer[$this->scope]['variables'][] = $word;
					}
				}
				break;
			/**
			 * For storing attributes and default values of a function; without
			 * including the "class implicit" as in my_function(MyClass $user)
			 */
			case 'function_attribute':
				if ($this->is_function) {
					if ($this->function_arguments) {
						// If on a class add the visibility key
						if ($this->is_class) {
							$this->buffer[$this->scope][$this->class_name]['methods'][$this->visibility][$this->function_name] .= $word;
						}
						else {
							$this->buffer[$this->scope]['methods'][$this->function_name] .= $word;
						}
					}
				}
				break;
			default:
				// If declaration of default attributes, just jump to storage..
				if ($this->function_attribute_default) {
					$this->store($word, 'function_attribute');
				}
				// If we found the class word, and we're inside the class_name_declaration (before extends),
				// store the name for later usage.
				if ($this->is_class) {
					// CLASSNAME STORAGE
					if ($this->class_name_declaration) {
						$this->class_name = $word;
						$this->buffer[$this->scope][$this->class_name] = '';
					}
					// EXTENDS STORAGE
					if ($this->extends_declaration) {
						$this->buffer[$this->scope][$this->class_name]['extends'] = $word;
					}
					// CONSTANT STORAGE
					if ($this->is_const) {
						$this->buffer[$this->scope][$this->class_name]['constants'][] = str_replace(array('"', "'"), '', $word);
					}
				}

				// Function(global) and Methods(class) Store.
				if ($this->is_function) {
	
					if ($this->function_name_declaration) {
						// Store the function name.
						// $this->function_name = $word;
						// If we're on a class, it means we probably need a visibility
						// key, if not specified, we just make it public.
						$this->function_name = $word;
						// After a Method declaration we'll find the function arguments for sure.
						$this->function_arguments = TRUE;
						if ($this->is_class) {
							$this->buffer[$this->scope][$this->class_name]['methods'][$this->visibility][$this->function_name] = '';
						} else {
							$this->buffer[$this->scope]['methods'][$this->function_name] = '';
							// $output[$this->current_scope]['functions'][$this->funcion_name] = '';
						}
					}
				}
				
				// Store the Constant name
				if ($this->is_define) {
					// Leave only the name
					$this->buffer[$this->scope]['constants'][] = str_replace(array('"', "'"), '', $word);
				}
				

				break;
		}

	}

}
