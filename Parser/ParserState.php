<?php

class ParserState
{
    // x -> ab . cd from j
    private $x;
    private $ab;
    private $cd;
    private $j;
    private $rule;
    private $reduction;
    private $value;
    private $shifts;
    private $processed;

    public function __construct($x, array $ab, array $cd, $j, $rule = null)
    {
	assert($x instanceof ParserToken);
	assert(is_array($ab));
	assert(is_array($cd));
	assert(is_int($j));
	$this->processed = false;
	$this->shifts = array();
	$x = clone $x;
	$ab = $this->copyArray($ab);
	$cd = $this->copyArray($cd);
	$this->setX($x);
	$this->setAb($ab);
	$this->setCd($cd);
	$this->setJ($j);
	$this->setReduction($x->getReduction());
	if ($rule !== null) {
	    $this->setRule($rule);
	}
    }

    public function getProcessed()
    {
	return $this->processed;
    }

    public function setProcessed()
    {
	$this->processed = true;
    }

    private function addShift(ParserState $state)
    {
	$this->shifts[] = $state;
    }

    public function getShifts()
    {
	$shifts = $this->shifts;
	$this->shifts = array();
	return $shifts;
    }

    public function __clone()
    {
	$this->x = clone $this->x;
	$this->ab = $this->copyArray($this->ab);
	$this->cd = $this->copyArray($this->cd);
    }

    private function copyArray(array $array)
    {
	if ($array === null) {
	    return null;
	}

	$new = array();

	return array_merge($new, $array);
    }

    public function setValue($value)
    {
	$this->value = $value;
    }

    public function getValue()
    {
	return $this->value or "";
    }

    public function setX(ParserToken $x)
    {
	$this->x = $x;
    }
    
    public function getX()
    {
	$x = $this->x;
	assert($x instanceof ParserToken);
	return $x;
    }

    public function setAb(array $ab)
    {
	foreach ($ab as $sym) {
	    assert($sym instanceof ParserToken);
	}
	$this->ab = $ab;
    }
    
    public function getAb()
    {
	return $this->ab;
    }

    public function setCd(array $cd)
    {
	foreach ($cd as $sym) {
	    assert($sym instanceof ParserToken);
	}
	$this->cd = $cd;
    }
    
    public function getCd()
    {
	return $this->cd;
    }

    public function setJ($j)
    {
	assert(is_int($j));
	$this->j = $j;
    }
    
    public function getJ()
    {
	$j = $this->j;
	assert(is_int($j));
	return $j;
    }
    
    public function setRule(ParserRule $rule)
    {
	$this->rule = $rule;
    }

    public function getRule()
    {
	return $this->rule;
    }

    public function setReduction($reduction)
    {
	$this->reduction = $reduction;
    }

    public function doReduction(array $tokens)
    {
	if ($this->reduction == Null) return;
	assert(is_callable($this->reduction));
	$result = $this->reduction->__invoke($tokens);
	return $result;
    }

    public function closure(array $grammar, $i)
    {
	assert(is_int($i));
	$cd = $this->getCd();
	// x->ab.cd
	return array_map(function($rule) use ($i) {
		return new ParserState($rule->getSymbol(), [], $rule->getRule(), $i, $rule);
	    }, 
	    array_values(array_filter($grammar, function($rule) use ($cd) { 
			return count($cd) > 0 and $rule->getSymbol()->equal($cd[0]); 
		    }
		    )));
    }

    public function shift (array $tokens, $i) 
    {
	assert(is_int($i));
	assert($tokens[$i] instanceof Token or $tokens[$i] == "end_of_input_marker");
	// x->ab.cd from j tokens[i]==c?
	$x = $this->getX();
	$ab = $this->getAb();
	$cd = $this->getCd();
	$j = $this->getJ();
	$rule = $this->getRule();
	if (getType($tokens[$i]) == "string") {
	    return null;
	}
	if (count($cd) > 0 and $cd[0]->compare($tokens[$i])) {
	    $cd0 = new ParserToken(array("type"  => $tokens[$i]->getType(),
					 "value" => $tokens[$i]->getValue()));
	    $this->addShift(new ParserState($x, 
					    array_merge($ab, array($cd0)), 
					    array_slice($cd, 1), 
					    $j,
					    $rule));
	    return true;
	} else {
	    return false;
	}
    }

    public function reductions(array $chart, $i)
    {
	assert(is_int($i));
	// ab. from j
	// chart[j] has y->... .x ....from k
	$x = $this->getX();
	$ab = $this->getAb();
	$cd = $this->getCd();
	$j = $this->getJ();
	$clone = clone $this;
	if (count($cd) != 0) { 
	    // no possible reductions at this time
	    return array();
	}
	$value = $this->computeValue();
	$x->setValue($value);
	$x->addToHistory($clone);
	return array_map(function($jstate) use ($x) {
		return new ParserState($jstate->getX(), 
					    array_merge($jstate->getAb(), array($x)), 
					    array_slice($jstate->getCd(), 1), 
					    $jstate->getJ(),
					    $jstate->getRule());
	    },
	    array_values(array_filter($chart[$j], function($jstate) use ($x) {
			return count($jstate->getCd()) > 0 and $jstate->getCd()[0]->equal($x);
		    }
		    )));
    }

    public function getHistory()
    {
	$result = "";
	$tokens = $this->getAb();
	foreach ($tokens as $t) {
	    $result .= " " . $t->getType();
	    $history = $t->getHistory();
	    if ($history instanceof ParserState) {
		$result .= " (" . $history->getHistory() . ")";
	    }
	    $result .= " ";
	}
	return $result;
    }

    public function getRank()
    {
	$assoc = $this->getRule()->getAssociativity();
	$prec = $this->getRule()->getPrecedence();
	$val = array($prec, $assoc);
	foreach ($this->getAb() as $sym) {
	    $history = $sym->getHistory();
	    if ($history instanceof ParserState) {
		if (!isset($first)) {
		    $first = $history;
		} else {
		    $last = $history;
		}
		$val[] = $history->countParserTokens();
	    }
	}
	$result = array($val);
	if (isset($first)) {
	    //assert(isset($first) && isset($last));
	    switch ($assoc) {
	    case 'left':
		$child = $first->getRank();
		break;
	    case 'right':
		$child = $last->getRank();
		break;
	    default:
		throw new Exception('Associativity Error: neither left nor right (' . $assoc .')');
	    }
	    foreach ($child as $val) {
		$result[] = $val;
	    }
	} else {
	    $result = array();
	}

	return $result;
    }

    protected function countParserTokens()
    {
	$result = 0;
	foreach ($this->getAb() as $sym) {
	    $history = $sym->getHistory();
	    if ($history instanceof ParserState) {
		$result += $history->countParserTokens();
	    } else {
		$result++;
	    }
	}
	return $result;
    }

    private function computeValue()
    {
	//assert(count($this->getCd()) == 0);
	$p = array(null); // p[0] : Value of x (to be computed)
	foreach ($this->getAb() as $token) {
	    $p[] = $token->getValue();
	}
	$p = $this->doReduction($p);
	return $p[0];
    }

    public function equal(ParserRule $rule)
    {
	return $this->toString() == $rule->toString() . " .  from 0";
    }

    public function __toString()
    {
	$ab = '';
	foreach ($this->getAb() as $sym) {
	    if ($sym instanceof ParserToken) {
		$ab .= $sym->__toString() . " ";
	    }
	}

	$cd = '';
	foreach ($this->getCd() as $sym) {
	    if ($sym instanceof ParserToken) {
		$cd .= $sym->__toString() . " ";
	    }
	}

	return $this->getX()->__toString() . ": "
	    . $ab . " . "
	    . $cd . " from "
	    . $this->getJ();
    }

    public function toString()
    {
	$ab = '';
	foreach ($this->getAb() as $sym) {
	    if ($sym instanceof ParserToken) {
		$ab .= $sym->getType() . " ";
	    }
	}

	$cd = '';
	foreach ($this->getCd() as $sym) {
	    if ($sym instanceof ParserToken) {
		$cd .= $sym->getType() . " ";
	    }
	}

	return $this->getX()->getType() . ": "
	    . $ab . " . "
	    . $cd . " from "
	    . $this->getJ();
    }

}