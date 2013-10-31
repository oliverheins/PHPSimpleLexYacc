<?php

class ParserRules
{
    private $rules;

    public function __construct()
    {
	$this->rules = array();
    }

    public function addRule($lhs, $rule, $function = null, $associativity = 0, $precedence = 0)
    {
	$this->rules[] = array($lhs, $rule, $function, $associativity, $precedence);
    }

    public function getRules()
    {
	return $this->rules;
    }

    public function generateCode($methods)
    {
	// Generate the anonymous functions
	$lambda = array();
	foreach ($methods as $method) {
	    assert($method instanceof MethodGenerator);
	    $lambda[$method->getName()] = $method->generateLambda();
	}

	// Generate the grammar
	$code = '$this->setGrammar(array(';
	foreach ($this->rules as $rule) {
	    $lhs           = $rule[0];
	    $rhs           = $rule[1];
	    $function      = $lambda[$rule[2]];
	    $precedence    = $rule[3];
	    $associativity = $rule[4];
	    $code .= 'new ParserRule(new ParserToken(array("type" => "' . $lhs . '", ' .
		'"reduction" => ' . $function . ')),' . "\n";
	    $code .= 'array(';
	    foreach ($rhs as $symbol) {
		$code .= 'new ParserToken(array("type" => "' . $symbol .'")),' . "\n";
	    }
	    $code .= '),' . "\n";
	    $code .= $precedence . ',' . "\n";
	    $code .= $associativity . '),' . "\n";
	}
	$code .= '));' . "\n";

	return $code;
    }

}