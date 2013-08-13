<?php

class Token
{
    public $type;
    public $rule;
    public $value;
    public $position;
    public $linenumber;

    public function __construct(array $t)
    {
	foreach ($t as $key => $value) {
	   switch ($key) {
	   case 'type':
	   case 'rule':
	   case 'value':
	   case 'position':
	   case 'linenumber':
	       $this->$key = $value;
	       break;
	   default:
	       throw new Exception('Token has no propety named ' . $key);
	   }
	}
    }

    public function setType($t) 
    {
	assert(is_string($t) and $t != '');
	$this->type = $t;
    }

    public function getType() 
    {
	$t = $this->type;
	assert(is_string($t) and $t != '');
	return $t;
    }

    public function setRule($r) 
    {
	assert(is_string($r) and $r != '');
	$this->rule = $r;
    }

    public function getRule() 
    {
	$r = $this->rule;
	assert(is_string($r) and $r != '');
	return $r;
    }

    public function setValue($v) 
    {
	assert(is_string($v) and $v != '');
	$this->value = $v;
    }

    public function getValue() 
    {
	$v = $this->value;
       	if (is_string($v) and $v != '') {
	    return $v;
	}
	return null;
    }

    public function setPosition($p) 
    {
	assert(is_int($p) and $p >= 0);
	$this->position = $p;
    }

    public function getPosition($p) 
    {
	$p = $this->position;
	assert(is_int($p) and $p >= 0);
	return $p;
    }

    public function setLinenumber($l)
    {
	assert(is_int($l) and $l >= 0);
	$this->linenumber = $l;
    }

    public function getLinenumber() {
	$l = $this->linenumber;
	assert(is_int($l) and $l >= 0);
	return $l;
    }
}