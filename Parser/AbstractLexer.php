<?php
/** Lexer module of PhpSimpleLexCC
 *
 * @package Lexer
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */
namespace PHPSimpleLexYacc\Parser;

require_once("Token.php");

/** core lexer class
 * 
 * The core functions of the lexer.
 * 
 * @abstract
 */
abstract class AbstractLexer
{
    /** list of all tokens
     *
     * @var array
     */
    protected $tokenlist;
    
    /** list of states
     *
     * @var array
     */
    protected $statelist;
    
    /** the current state
     *
     * @var string
     */
    protected $currentstate;
    
    /** the data to lex
     *
     * @var string
     */
    protected $data;
    
    /** the list of rules
     *
     * @var array
     */
    protected $rulelist;
    
    /** the current linenumber
     *
     * @var int
     */
    protected $linenumber = 1;
    
    /** holds a link to to the ignore function
     *
     * @var object
     */
    protected $ignoreFunction;

    /** Constructor
     * 
     * Sets the tokenlist to an empty array, makes statelist contain INITIAL
     * state, which is the current state.
     * 
     * @return void
     */
    public function __construct()
    {
	$this->tokenlist = array();
	$this->statelist = array('INITIAL');
	$this->currentstate = 'INITIAL';
    }

    /** Starts lexing
     * 
     * As long as string is not fully processed, the internal method lex_()
     * is cälled on the string.
     * 
     * @return void
     * @see AbstractLexer::lex_()
     */
    public function lex()
    {
	$position = 0;
	$string = $this->data;
	assert(is_string($string));

	while ($string) {
	    $this->lex_($string, $position);
	}
	// EOD ('/\Z/') can't be matched within the while-loop, so we
	// call lex_() one last time.
	$this->lex_($string, $position);
    }

    /** the core lexing method
     * 
     * Processes the input.  Input starts at $string[0], and cuts off the first
     * token if the first character should not be ignored.  The identified
     * token is added to the token list.
     * 
     * @see AbstractLexer::ignore_function, AbstractLexer::tokenlist, Token::__construct(), AbstractLexer::rulelist, AbstractLexer::getCurrentState()
     * @param string $string
     * @param int $position
     * @return void
     * @throws \Exception
     */
    protected function lex_(&$string, &$position)
    {
        if (strlen($string) === 0) { return; }
	if ($this->ignoreFunction->__invoke($string[0])) {
	    $string = substr($string, 1);
	    return;
	}
	foreach ($this->rulelist[$this->getCurrentState()] as $rule => $a) {
	    $f = $a['function'];
	    $type = $a['type'];
	    //	    var_dump($f);
	    //	    var_dump($type);
	    if (preg_match($rule, $string, $matches)) {
		//		error_log("$rule:$string");
		$string = preg_replace($rule, '', $string);
		$value = $matches[0];
		$position += strlen($value);
		$token = new Token(array('rule'     => $rule,
					 'type'     => $type,
					 'value'    => $value,
					 'linenumber' => $this->linenumber,
					 'position' => $position));
		$token = $f($token);
		if (is_object($token)) {
		    $this->tokenlist[] = $token;
		}
		return;
	    }
	}
        throw new \Exception("Can't find rule for string:" . $string);
    }

    /** Sets the data
     * 
     * @param string $data
     * @return void
     */
    public function setData($data)
    {
	assert(is_string($data) and $data != '');
	$this->data = $data;
    }

    /** Returns the data
     * 
     * @return string
     */
    public function getData()
    {
	$data = $this->data;
	assert(is_string($data) and $data != '');
	return $data;
    }

    /** Returns the token at [position]
     * 
     * @param int $position
     * @return Token|null  Returns the token, or null if there is none.
     */
    public function getToken($position)
    {
	assert(is_int($position) !== false);
	if ($position < count($this->tokenlist)) {
	    return $this->tokenlist[$position];
	}
	return null;
    }

    /** Returns the token list
     * 
     * @return array
     */
    public function getTokens()
    {
	$tokenlist = $this->tokenlist;
	assert(is_array($tokenlist));
	return $tokenlist;
    }

    /** Sets the list of states
     * 
     * @param array $statelist
     * @return void
     */
    protected function setStatelist(array $statelist)
    {
	foreach ($statelist as $state) {
	    assert(is_string($state) and $state != '');
	    if (!array_search($state, $this->statelist)) {
		// avoid duplication
		$this->statelist[] = $state;
	    }
	}
    }

    /** Sets the current state
     * 
     * @param string $state
     * @throws \Exception
     * @return void
     */
    protected function setCurrentState($state)
    {
	if (is_string($state) and $state != '' and array_key_exists($state, $this->statelist)) {
	    $this->currentstate = $state;
	} else {
	    throw new \Exception('No state ' . $state . ' defined!');
	}
    }

    /** Returns the current state
     * 
     * @return string
     */
    protected function getCurrentState()
    {
	$state = $this->currentstate;
	assert(is_string($state) and $state != '');
	return $state;
    }

    /** Returns the list of rules
     * 
     * @return array
     */
    public function getRulelist()
    {
	return $this->rulelist;
    }

}