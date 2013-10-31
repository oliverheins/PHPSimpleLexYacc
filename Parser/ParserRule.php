<?php

include_once("ParserState.php");

class ParserRule
{
    private $symbol;
    private $rule;
    private $reduction;
    private $precedence;
    private $assoc;  // left = 0, right = 1

    // $assoc: 0 = left, 1 = right
    public function __construct($symbol, array $rule, $prec = 0, $assoc = 0)
    {
	assert(is_int($prec));
	assert(is_int($assoc));
	$this->setSymbol($symbol);
	$this->setPrecedence($prec);
	$this->setAssociativity($assoc);
	$this->setRule($rule);
	$this->setReduction((is_callable($symbol->getReduction()) or is_string($symbol->getReduction())) ? $symbol->getReduction() : null );
    }

    protected function setPrecedence($prec)
    {
	assert(is_int($prec));
	$this->precedence = $prec;
    }

    public function getPrecedence()
    {
	$prec = $this->precedence;
	assert(is_int($prec));
	return $prec;
    }

    protected function setAssociativity($assoc = 0)
    {
	assert($assoc === 0 or $assoc === 1);
	$this->assoc = $assoc;
    }

    public function getAssociativity()
    {
	$assoc = $this->assoc;
	assert($assoc === 0 or $assoc === 1);
	return $assoc === 0 ? 'left' : 'right';
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
	assert(is_callable($reduction) or is_string($reduction) or $reduction === null);
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

    public function toString()
    {
	$rule = '';
	foreach ($this->getRule() as $sym) {
	    if ($sym instanceof ParserToken) {
		$rule .= $sym->getType() . " ";
	    }
	}


	return $this->getSymbol()->getType() . ": "
	    . $rule;
    }

}