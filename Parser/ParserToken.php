<?php
/** Parser module of PhpSimpleLexCC
 *
 * @package Parser
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */

namespace PHPSimpleLexYacc\Parser;

require_once("Token.php");

/** The Parser Token class
 * 
 * can be compared to (Lexer) Token.
 */
class ParserToken
{
    /** the type of the token
     *
     * @var string
     * @see ParserToken::getType()
     */
    private $type = Null;
    
    /** the tokens value
     *
     * @var mixed
     * @see ParserToken::getValue(), ParserToken::setValue()
     */
    private $value = Null;
    
    /** the reduction function
     *
     * @var callable|string
     * @see ParserToken::getReduction()
     */
    private $reduction = Null;
    
    /** the state this token is derived from
     *
     * @var ParserState
     * @see ParserToken::addToHistory(), ParserToken::getHistory()
     */
    private $history;
    
    /** the string representation: "type (value)"
     *
     * @var string
     * @see ParserToken::__toString()
     */
    private $cache;

    /** Constructor
     * 
     * Constructs the token.  $args is array with key=>value pairs.  Possible
     * keys: type, value, reduction
     * 
     * @param array $args
     * @throws \Exception
     * @return void
     * @see ParserToken::type, ParserToken::value, ParserToken::reduction
     */
    public function __construct(array $args = array()) 
    {
	foreach ($args as $key => $value) {
	    switch ($key) {
	    case "type":
	    case "value":
	    case "reduction":
		$this->$key = $value;
	        break;
	    default:
		throw new \Exception('Token has no property named ' . $key);
	    }
	}
    }

    /** Compares with a (lexer) Token
     * 
     * Matches type and value of the token.
     * 
     * @param \PHPSimpleLexYacc\Parser\Token $token
     * @return boolean
     * @see ParserToken::type, ParserToken::value
     */
    public function compare(Token $token)
    {
	if (($this->type !== Null and $this->type != $token->getType()) or
	    ($this->value !== Null and $this->value != $token->getValue())) {
	    return false;
	}
	return true;
    }

    /** Returns true if the type of token equals the type of another ParserToken
     * 
     * @param \PHPSimpleLexYacc\Parser\ParserToken $token
     * @return boolean
     * @see ParserToken::type, ParserToken::getType()
     */
    public function equal(ParserToken $token)
    {
	return $this->type == $token->getType();
    }

    /** Returns the type of the token
     * 
     * @return string
     * @see ParserToken::type
     */
    public function getType()
    {
	return $this->type;
    }

    /** Returns the reduction method if set, null otherwise
     * 
     * @return string|callable|null
     * @see ParserToken::reduction
     */
    public function getReduction()
    {
	return (is_callable($this->reduction) or is_string($this->reduction)) ? $this->reduction : null;
    }

    /** Returns the value of the token
     * 
     * @return mixed
     * @see ParserToken::value, ParserToken::setValue()
     */
    public function getValue()
    {
        if (! $this->value) { return null; }
	return $this->value;
    }

    /** Sets the value of the token
     * 
     * @param mixed $value
     * @return void
     * @see ParserToken::value, ParserToken::getValue()
     */
    public function setValue($value)
    {
	$this->value = $value;
        unset($this->cache);
    }

    /** Returns the history (i.e. the state the token is derived from)
     * 
     * @return ParserState
     * @see ParserToken::history, ParserToken::addToHistory()
     */
    public function getHistory()
    {
	return $this->history;
    }

    /** Sets the history (i.e. the state the token is derived from)
     * 
     * @param \PHPSimpleLexYacc\Parser\ParserState $state
     * @return void
     * @see ParserToken::history, ParserToken::getHistory(), ParserState::reductions()
     */
    public function addToHistory(ParserState $state)
    {
	$this->history = $state;
    }
    
    /** Returns the value of a token as string representation
     * 
     * Works recursively on arrays.
     * 
     * @param mixed $value
     * @return string
     * @see ParserToken::__toString()
     */
    private function valToString($value)
    {
        if (is_string($value) or is_numeric($value) or is_bool($value)) {
            return (string) $value;
        }
        if (is_array($value)) {
            return "Array";
            $result = array();
            foreach ($value as $val) {
                $result .= $this->valToString($value);
            }
            return implode(",", $result);
        }
        if (is_null($value)) {
            return "NULL";
        }
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return $value.__toString();
            } else {
                return get_class($value);
            }
        }
        if (is_resource($value)) {
            return "Resource";
        }
        return "Unknown";
    }

    /** Returns a string representation of the token
     * 
     * The string representation is cached.
     * 
     * @return string
     * @see ParserToken::cache, ParserToken::valToString(), ParserToken::getType()
     */
    public function __toString()
    {
        if (!isset($this->cache)) {
            $this->cache = $this->getType() . " (" . $this->valToString($this->value) . ")";
        }
	return $this->cache;
    }

    /** clones the token
     * 
     * If value or type are objects, they get cloned.  Most likely, only values
     * will be objects.
     * 
     * @return void
     */
    public function __clone()
    {
	if (is_object($this->value)) { 
            $this->value = clone $this->value;
        }
        if (is_object($this->type)) {
            $this->type  = clone $this->type;
        }
    }
}