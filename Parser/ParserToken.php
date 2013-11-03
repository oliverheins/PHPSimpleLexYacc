<?php

include_once("Token.php");

class ParserToken
{
    private $type = Null;
    private $value = Null;
    private $reduction = Null;
    private $history;

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
		throw new Exception('Token has no propety named ' . $key);
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
	if (! $this->value) return null;
	return $this->value;
    }

    public function setValue($value)
    {
	$this->value = $value;
    }

    public function getHistory()
    {
	return $this->history;
    }

    public function addToHistory(ParserState $state)
    {
	$this->history = $state;
    }

    public function __toString()
    {
	return $this->getType() . " (" . $this->value . ")";
    }

    public function __clone()
    {
	if (is_object($this->value)) $this->value = clone $this->value;
	if (is_object($this->type))  $this->type  = clone $this->type;
    }
}