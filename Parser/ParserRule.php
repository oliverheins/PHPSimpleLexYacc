<?php

include_once("ParserState.php");

class ParserRule
{
    private $symbol;
    private $rule;
    private $reduction;

    public function __construct($symbol, array $rule)
    {
	$this->setSymbol($symbol);
	$this->setRule($rule);
	$this->setReduction(is_callable($symbol->getReduction()) ? $symbol->getReduction() : null );
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

    private function setReduction($reduction)
    {
	assert(is_callable($reduction) or $reduction === null);
	$this->reduction = $reduction;
    }

    public function __toString()
    {
	$rule = '';
	foreach ($this->getRule() as $sym) {
	    $rule .= $sym->__toString() . " ";
	}
	return $this->getSymbol()->__toString() . ": " . $rule;
    }

}