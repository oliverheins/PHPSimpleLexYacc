<?php

require_once("Token.php");

abstract class AbstractLexer
{
    protected $tokenlist;
    protected $statelist;
    protected $currentstate;
    protected $data;
    protected $rulelist;
    protected $linenumber = 1;
    protected $ignoreFunction;

    public function __construct()
    {
	$this->tokenlist = array();
	$this->statelist = array('INITIAL');
	$this->currentstate = 'INITIAL';
    }

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
	echo "<hr>\n\nCan't find rule for string:<br>";
	echo "<pre>" . $string . "</pre>";
	exit();
    }

    public function setData($data)
    {
	assert(is_string($data) and $data != '');
	$this->data = $data;
    }

    public function getData()
    {
	$data = $this->data;
	assert(is_string($data) and $data != '');
	return $data;
    }

    public function getToken($position)
    {
	assert(is_int($position) !== false);
	if ($position < count($this->tokenlist)) {
	    return $this->tokenlist[$position];
	}
	return null;
    }

    public function getTokens()
    {
	$tokenlist = $this->tokenlist;
	assert(is_array($tokenlist));
	return $tokenlist;
    }

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

    protected function setCurrentState($state)
    {
	if (is_string($state) and $state != '' and array_key_exists($state, $this->statelist)) {
	    $this->currentstate = $state;
	} else {
	    throw new Exception('No state ' . $state . ' defined!');
	}
    }

    protected function getCurrentState()
    {
	$state = $this->currentstate;
	assert(is_string($state) and $state != '');
	return $state;
    }

    public function getRulelist()
    {
	return $this->rulelist;
    }

}