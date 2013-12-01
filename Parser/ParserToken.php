<?php
namespace PHPSimpleLexYacc\Parser;

require_once("Token.php");

class ParserToken
{
    private $type = Null;
    private $value = Null;
    private $reduction = Null;
    private $history;
    private $cache;

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
		throw new \Exception('Token has no propety named ' . $key);
	    }
	}
    }

    public function compare(Token $token)
    {
	if (($this->type !== Null and $this->type != $token->getType()) or
	    ($this->value !== Null and $this->value != $token->getValue())) {
	    return false;
	}
	return true;
    }

    public function equal(ParserToken $token)
    {
	return $this->type == $token->getType();
    }

    public function getType()
    {
	return $this->type;
    }

    public function getReduction()
    {
	return (is_callable($this->reduction) or is_string($this->reduction)) ? $this->reduction : null;
    }

    public function getValue()
    {
        if (! $this->value) { return null; }
	return $this->value;
    }

    public function setValue($value)
    {
	$this->value = $value;
        unset($this->cache);
    }

    public function getHistory()
    {
	return $this->history;
    }

    public function addToHistory(ParserState $state)
    {
	$this->history = $state;
    }
    
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

    public function __toString()
    {
        if (!isset($this->cache)) {
            $this->cache = $this->getType() . " (" . $this->valToString($this->value) . ")";
        }
	return $this->cache;
    }

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