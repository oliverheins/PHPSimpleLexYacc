<?php

include_once("ParserState.php");

class ParserRule
{
    private $symbol;
    private $rule;

    public function __construct($symbol, array $rule)
    {
	$this->setSymbol($symbol);
	$this->setRule($rule);
    }

    public function setSymbol(ParserToken $symbol)
    {
	$this->symbol = $symbol;
    }

    public function getSymbol()
    {
	$symbol = $this->symbol;
	assert($symbol instanceof ParserToken);
	return $symbol;
    }

    public function setRule(array $rule)
    {
	foreach ($rule as $symbol) {
	    assert($symbol instanceof ParserToken);
	}
	$this->rule = $rule;
  }

    public function getRule()
    {
	$rule = $this->rule;
	assert(is_array($rule));
	return $rule;
    }

}