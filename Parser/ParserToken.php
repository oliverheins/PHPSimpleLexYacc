<?php

include_once("Token.php");

class ParserToken
{
    private $type = Null;
    private $value = Null;

    public function __construct(array $args = array()) 
    {
	foreach ($args as $key => $value) {
	    switch ($key) {
	    case "type":
	    case "value":
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

    public function getType()
    {
	return $this->type;
    }
}