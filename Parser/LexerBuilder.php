<?php
/** Lexing module of PhpSimpleLexCC
 *
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */

include_once("TokensBase.php");
include_once("Generators/MethodGenerator.php");
include_once("Generators/TokenGenerator.php");
include_once("Generators/ClassGenerator.php");
include_once("Generators/PropertyGenerator.php");

/** LexerBuilder class
 *
 * Builds the lexer from a definiton.
 */
abstract class LexerBuilder
{
    const INITIAL = 'INITIAL';

    /** Holds the different token types
     *
     * @type array
     */
    private $tokens = array();

    /** List of properties
     *
     * Holds all the properties of the lexer definition that don't
     * define any token
     *
     * @type array
     */
    private $properties = array();

    /** List of token related methods
     *
     * Holds all the methods of the lexer definition that are related
     * to the token definition.
     *
     * @type array
     */
    private $tokenMethods = array();

    /** List of methods not related to token definition
     *
     * Holds all the methods of the lexer definition that don't define
     * any token.
     *
     * @type array
     */
    private $extraMethods = array();

    /** List of tokens that can be ignored totally
     *
     * @type array
     */
    private $ignoreTokens = array();

    /** List of all states
     *
     * Stores all states and their mode (inclusive/exclusive) as key
     * (state name) => value (type) pairs.
     *
     * @type array
     */
    private $states = array();

    /** List of all inclusive states
     *
     * All states that are of type inclusive are stored here as a
     * simple list.
     *
     * @type array
     */
    private $inclusivestates = array();

    /** Table of class hierarchy
     *
     * Maps a filename to the class hierarchy: 
     * child -> parent =~ 1 -> 2
     *
     * @type array
     * @see LexerBuilder::addClasshierarchy()
     * @see LexerBuilder::getClasshierarchy()
     */
    private $classhierarchy = array();

    /** Constructor
     *
     * @abstract
     */
    abstract public function __construct();

    /** Sets the tokens array
     *
     * @access protected
     * @param array $tokens  An array of tokens.
     */
    protected function setTokens(array $tokens)
    {
	$this->tokens = $tokens;
    }

    /** Adds tokens to the tokens array
     *
     * @access protected
     * @param array $tokens  An array of tokens.
     */
    protected function addTokens(array $tokens) {
	$this->tokens = array_merge($this->tokens, $tokens);
    }

    /** Returns the tokens array
     *
     * @access protected
     * @return array
     */
    protected function getTokens()
    {
	return $this->tokens;
    }

    /** Sets the states array
     *
     * Asserts the array is formed well as a list of key=>value pairs,
     * where key is the name of the state and value the type: either
     * inclusive or exclusive.
     *
     * @access protected
     * @param array $states  A list of states as key => value pairs.
     */
    protected function setStates(array $states)
    {
	$this->states['INITIAL'] = 'inclusive';
	foreach ($states as $state => $type) {
	    assert($type == 'exclusive' or $type == 'inclusive');
	    $this->states[$state] = $type;
	    if ($type == 'inclusive') {
		$this->inclusivestates[] = $state;
	    }
	}
    }

    /** Returns the generated Lexer
     *
     * Checks if the lexer definition was changed recently. If so, it
     * generates a new one before returning the lexer.  Also builds
     * the class hierarchy.
     *
     * @access public
     * @param  string $lexername  The name of the lexer (must be a valid classname)
     * @return AbstractLexer      The generated lexer
     */
    public function getLexer($lexername)
    {
	assert(is_string($lexername) && $lexername != '');
	if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*\Z/', $lexername)) {
	    throw new Exception("lexername must be a valid PHP classname.");
	}

	$dir = 'Parser/';
	$lexerfile = $dir . $lexername . '.php';
	$usecache = false;
	if (@file_exists($lexerfile)) {
	    $object = new ReflectionObject($this);
	    $classlevel = 1;
	    $newestfiletime = 0;
	    while (true) { // traverse all parents of the lexer
			   // definition and get the latest
			   // modification time
		$classfile = $object->getFileName();
		$classname = $object->getName(); // Build class hierarchy
		$this->addClasshierarchy($classname, $classlevel);
		$classlevel++;
		$filetime = @filemtime($classfile);
		$newestfiletime = $filetime > $newestfiletime ? $filetime : $newestfiletime;
		if ($ancestor = $object->getParentClass()) {
		    $object = $ancestor;
		} else {
		    break;
		}
	    }
	    // Use cache only if the cached file is newer than the
	    // last modification
	    if ($newestfiletime < @filemtime($lexerfile)) {
		$usecache = true;
	    }
	} 
	if ($usecache === false) {
	    // Build a new lexer
	    $lexer = $this->createLexer($lexername);
	    if (!file_put_contents($lexerfile, $lexer)) {
		throw new Exception("Can't write " . $lexerfile);
	    };
	}
	include_once($lexerfile);
	return new $lexername();
    }

    /** Builds a new lexer
     *
     * Called by getLexer().  Extracts the code, then builds a new
     * lexer class file.  Uses the information from
     * LexerBuilder::$tokenMethods to build up the rulelist.
     *
     * @access private
     * @param  string $lexername    The name of the lexer (must be a valid classname).
     * @see    LexerBuilder::extractCode() for the actual parsing of the lexer definition.
     * @see    LexerBuilder::$tokenMethods for the stored lexing methods.
     * @see    LexerBuilder::$extraMethods for the rest of the methods.
     * @return string $lexer        The code of the created lexer.
     */
    private function createLexer($lexername)
    {
	$this->extractCode();

	ob_start();
	echo '<?php' . "\n\n"
	    . 'include_once("AbstractLexer.php");' . "\n\n";

	$class = new ClassGenerator(array('name' => $lexername,
					  'extension' => 'AbstractLexer'));

	foreach ($this->properties as $prop) {
	    $class->addProperty($prop);
	}

	$constructcode = 'parent::__construct();' . "\n";

	// Generates the $states array;
	$c = array();
	foreach ($this->states as $state => $type) {
	    $c[] =  "'" . $state . "'" . ' => ' . "'" . $type . "'";
	}
	$code = implode(",\n", $c);
	$code = 'array(' . $code . ')'; 

	$constructcode .= '$this->statelist = ' . $code . ';' . "\n";

	$code = 'function($c) { return ' . $this->getIgnoreString() . '; }';
	$constructcode .= '$this->ignoreFunction = ' . $code . ';' . "\n";

	// Generates the rulelist
	$cc = array(); // outer code array
	foreach ($this->tokenMethods as $statename => $mode) {
	    $c = array(); // inner code array
	    foreach ($mode as $name => $m) {
		$regexp = $m->getRegexp();
		$regexp = substr($regexp, 0, 2) . '^' . substr($regexp, 2);
		if ($statename == self::INITIAL) {
		    $needle = '/t_([a-zA-Z]+)/';
		} else {
		    $needle = '/t_' . $statename . '_([a-zA-Z]+)/';
		}
		preg_match($needle, $name, $matches);
		$tokenname = $matches[1];
		$lambda = $m->generateLambda();
		$c[] = $regexp . ' => array("function" => ' . $lambda . ',' . "\n"
		    . '"type" => "' . $tokenname . '")';
	    }
	    $code = implode(",\n", $c);
	    $cc[] = "'" . $statename . "'" . ' => ' . 'array(' . $code . ')';
	}
	$code = implode(",\n", $cc);
	$code = 'array(' . $code . ')';

	$constructcode .= '$this->rulelist = ' . $code . ';';

	$c = new MethodGenerator(array('name' => '__construct',
				       'source' => $constructcode,
				       'body' => $constructcode));
	$class->addMethod($c);

	foreach ($this->extraMethods as $name => $m) {
	    $class->addMethod($m);
	}

	echo $class->generateCode();

	$lexer = ob_get_contents();
	ob_end_clean();
	
	return $lexer;
    }

    /** Parses the lexer definition
     *
     * and extracts the relevant code portions.  Builds up
     * LexerBuilder::$tokenMethods and LexerBuilder::$extraMethods.
     * Uses LexerBuilder::$classhierarchy to sort
     * LexerBuilder::$tokenMethods.
     *
     * @access private
     * @see    LexerBuilder::$classhierarchy for the class hierarchy
     * @see    LexerBuilder::$tokenMethods   for the stored lexing methods.
     * @see    LexerBuilder::$extraMethods   for the rest of the methods.
     */
    private function extractCode() 
    {
	$tokens = array();
	$tokensignore = array();
	$filename = '';

	// Constructing the tokenmethods array out of the different states
	$tokenmethods[self::INITIAL] = array();
	foreach ($this->states as $state => $type) {
	    $tokenmethods[$state] = array();
	}

	// Constructing the method names of the token functions
	$methods = array(); 
	foreach ($this->getTokens() as $token) {
	    $methods[] = "t_" . $token;
	    $methods[] = "t_ANY_" . $token;
	    foreach ($this->states as $state => $type) {
		$methods[] = "t_" . $state . "_" . $token;
	    }
	}

	// Setting up Reflecting Class and Base Class
	$object = new ReflectionObject($this);
	$parent = $object->getParentClass();
	while ($ancestor = $parent->getParentClass()) {
	    $parent = $ancestor;
	}
	$objectMethods = $object->getMethods();

	foreach ($objectMethods as $method) {
	    $methodName = $method->getName();
	    //	    echo "Methode " . $methodName ."<br>";
	    if ($parent->hasMethod($methodName)) { 
		// we are only interested in methods of the child
		// classes
		continue;
	    }
	    $found = false;
	    $state = null;
	    $tokenname = null;
	    if (array_search($methodName, $methods) !== false) {
		preg_match('/t_(?:([a-zA-Z]+)_)?([a-zA-Z]+)/', $methodName, $matches);
		$state = $matches[1];
		if ($state == '') $state = self::INITIAL;
		$tokenname = $matches[2];
		$tokens[$tokenname] = true;
		$found = true;
	    } // TODO: Same for t_ignore etc.

	    // Getting the source code of the method
	    $filename_ = $method->getFileName();
	    if ($filename_ != $filename) {
		$filename = $filename_;
		$file = file($filename);
	    }
	    $startLine = $method->getStartLine() - 1;
	    $endLine = $method->getEndLine();
	    $numLines = $endLine - $startLine + 1;
	    $methodSource = array_slice($file, $startLine, $numLines);
	    array_walk($methodSource, function(&$line) {
		    $line = trim($line);
		});
	    $methodSource = implode("\n", $methodSource);

	    $classname = $method->getDeclaringClass()->getName();
	    $classhierarchy = $this->getLevelForClass($classname);

	    if ($found === true) {
		$m = new TokenGenerator(array('name'           => $methodName,
					      'source'         => $methodSource,
					      'parameters'     => $method->getParameters(),
					      'reflection'     => $method,
					      'docstring'      => $method->getDocComment(),
					      'classhierarchy' => $classhierarchy,
					      'linenumber'     => $startLine));
		$m->extractBody();
		$m->extractRegexp();
		$this->tokenMethods[$state][$methodName] = $m;
		if ($state == self::INITIAL) {
		    // if state is initial, include this rule to all
		    // inclusive states.
		    foreach ($this->inclusivestates as $istate) {
			if (! array_key_exists($istate, $this->tokenMethods)) {
			    // Init the $istate table
			    $this->tokenMethods[$istate] = array();
			}
			$extMethodName = 't_' . $istate . '_' . $tokenname;
			if (! array_key_exists($extMethodName, $this->tokenMethods[$istate])) {
			    // check if there already an existing rule
			    // for this state
			    $this->tokenMethods[$istate][$extMethodName] = $m;
			}
		    }
		}
	    } else {
		$m = new MethodGenerator(array('name' => $methodName,
					       'source' => $methodSource,
					       'parameters' => $method->getParameters(),
					       'reflection' => $method,
					       'docstring' => $method->getDocComment()));
		$m->extractBody();
		$this->extraMethods[$methodName] = $m;
	    }
	}

	$objectProperties = $object->getProperties();
	$defaultProperties = $object->getDefaultProperties();

	$reflection = new ReflectionClass($this);
	$templateMethod = $reflection->getMethod('tokenMethodTemplate');

	foreach ($objectProperties as $prop) {
	    $propName = $prop->getName();
	    if ($parent->hasProperty($prop)) {
		continue;
	    }
	    $propValue = isset($defaultProperties[$propName]) ? $defaultProperties[$propName] : null;

	    $className = $prop->getDeclaringClass()->getName();
	    $classhierarchy = $this->getLevelForClass($className);
	    $classFile = new SplFileObject($prop->getDeclaringClass()->getFileName());

	    foreach ($classFile as $line => $content) {
		if (preg_match('/(private|protected|public|var)\s\$'.$propName.'/x', $content)) {
		    $startLine = $line + 1;
		    preg_match('/(private|protected|public|var)\s\$'.$propName.'\s*=\s*((?:\'.+\')|(?:".+"))\s*;/x', $content, $matches);
		    if (isset($matches[2])) {
			$propValue = $matches[2];
		    }
		    break;
		}
	    }

	    $found = false;
	    if (preg_match('/t_(?:([a-zA-Z]+)_)?([a-zA-Z]+)/', $propName, $matches)) {
		$state = $matches[1];
		if ($state == '') $state = self::INITIAL;
		$tokenname = $matches[2];
		if (array_search($tokenname, $this->tokens)) {
		    $tokens[$tokenname] = true;
		    $found = true;
		    $m = new TokenGenerator(array('name'           => $propName,
						  'body'           => 'return $token;',
						  'regexp'         => $propValue,
						  'parameters'     => $templateMethod->getParameters(),
						  'reflection'     => $templateMethod,
						  'docstring'      => $templateMethod->getDocComment(),
						  'classhierarchy' => $classhierarchy,
						  'linenumber'     => $startLine));
		    //		    $m->extractBody();
		    $this->tokenMethods[$state][$propName] = $m;
		    if ($state == self::INITIAL) {
			// if state is initial, include this rule to all
			// inclusive states.
			foreach ($this->inclusivestates as $istate) {
			    if (! array_key_exists($istate, $this->tokenMethods)) {
				// Init the $istate table
				$this->tokenMethods[$istate] = array();
			    }
			    $extMethodName = 't_' . $istate . '_' . $tokenname;
			    if (! array_key_exists($extMethodName, $this->tokenMethods[$istate])) {
				// check if there already an existing rule
				// for this state
				$this->tokenMethods[$istate][$extMethodName] = $m;
			    }
			}
		    }
		}
	    }

	    if ($found === false) {
		$this->properties[] = new PropertyGenerator(array('name' => $propName,
								  'reflection' => $prop,
								  'docstring' => $prop->getDocComment(),
								  'static' => $prop->isStatic(),
								  'public' => $prop->isPublic(),
								  'protected' => $prop->isProtected(),
								  'private' => $prop->isPrivate(),
								  'value' => $propValue));
	    }

	}

	if (count($tokens) != count($this->getTokens())) {
	    throw new Exception("Token not defined");
	}


	foreach($this->tokenMethods as $key => $val) {
	    uasort($this->tokenMethods[$key], array("TokenGenerator", "compare"));
	}
    }

    /** Returns a given Token
     *
     * This is the template function for simple tokens, which do not
     * change the default status of the token.
     *
     * @param  Token $token  The token to (not) manipulate
     * @return Token
     */
    private function tokenMethodTemplate($token)
    {
	return $token;
    }

    /** Adds a class to the class hierarchy
     * 
     * An integer value correspending to the nesting level is added.
     *
     * @param string $classname  The name of the class
     * @param int    $n          The nesting level
     * @see LexerBuilder::$classhierarchy
     */
    private function addClasshierarchy($classname, $n)
    {
	assert(is_string($classname));
	assert($classname != '');
	assert(is_int($n));
	assert($n >= 1);
	$this->classhierarchy[$classname] = $n;
    }

    /** Returns the whole class hierarchy table
     *
     * @return array
     * @see LexerBuilder::$classhierarchy
     */
    private function getClasshierarchy()
    {
	$c = $this->classhierarchy;
	assert(is_array($c));
	return $c;
    }

    /** Returns the level of a class in the class hierarchy
     *
     * @param  string $classname  The name of the class
     * @return int    The level of the class in the class hierarchy 
     * @return false  if class is not in the hierarchy
     */
    private function getLevelForClass($classname)
    {
	assert(is_string($classname));
	if (isset($this->classhierarchy[$classname])) { 
	    return $this->classhierarchy[$classname];
	}
	return false;
    }

    /** Returns the list of Tokens that can be fully ignored
     *
     * @return array 
     * @see LexerBuilder::$ignoreTokens
     */
    protected function getIgnoreTokens()
    {
	return $this->ignoreTokens;
    }

    /** Sets the list of tokens that can be fully ignored
     *
     * @param string $tokens  A string of chars that can be ignored
     * @see LexerBuilder::$ignoreTokens
     */
    protected function setIgnoreTokens($tokens)
    {
	assert(is_string($tokens));
	$this->ignoreTokens = array();
	$this->addIgnoreTokens($tokens);
    }

    /** Adds to the list of tokens that can be fully ignored
     *
     * @param string $tokens  A string of chars that can be ignored
     * @see LexerBuilder::$ignoreTokens
     */
    protected function addIgnoreTokens($tokens)
    {
	assert(is_string($tokens));
	$length = strlen($tokens);
	for ($i = 0; $i < $length; $i++) {
	    $this->ignoreTokens[] = $tokens[$i];
	}
    }

    /** Returns a string concatenating all ignore tokens to an or expression
     *
     * They are compared to a string named $c
     *
     * return string   The or-expression, if there are any ignore tokens or 'false'
     * @see LexerBuilder::$ignoreTokens
     */
    private function getIgnoreString()
    {
	$ignoreTokens = array();
	foreach ($this->ignoreTokens as $t) {
	    $ignoreTokens[] = $t == '"' ? "'\"'" : '"' . $t . '"';
	}
	if (count($ignoreTokens) > 0) {
	    return '$c == ' . implode(' or $c == ', $ignoreTokens);
	} else {
	    return 'false';
	}
    }
}
