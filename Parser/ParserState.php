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

    public function __construct($x, array $ab, array $cd, $j)
    {
	assert($x instanceof ParserToken);
	assert(is_array($ab));
	assert(is_array($cd));
	assert(is_int($j));
	$x = clone $x;
	$ab = $this->myClone($ab);
	$cd = $this->myClone($cd);
	$this->setX($x);
	$this->setAb($ab);
	$this->setCd($cd);
	$this->setJ($j);
	$this->setReduction($x->getReduction());
    }

    private function myClone(array $array)
    {
	$new = array();

	foreach ($array as $key => $val) {
	    $new[$key] = clone $val; 
	}

	return $new;
    }

    public function setValue($value)
    {
	$this->value = $value;
    }

    public function getValue()
    {
	return $this->value or "";
    }

    public function setX($x)
    {
	assert($x instanceof ParserToken);
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
	$next_states = array_map(function($rule) use ($i) {
		return new ParserState($rule->getSymbol(), [], $rule->getRule(), $i);
	    }, 
	    array_values(array_filter($grammar, function($rule) use ($cd) { 
			return count($cd) > 0 and $rule->getSymbol()->equal($cd[0]); 
		    }
		    )));
	return $next_states;
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
	if (count($cd) > 0 and $cd[0]->compare($tokens[$i])) {
	    $cd0 = new ParserToken(array("type"  => $tokens[$i]->getType(),
					 "value" => $tokens[$i]->getValue()));
	    return new ParserState($x, 
				   array_merge($ab, array($cd0)), 
				   array_slice($cd, 1), 
				   $j);
	} else {
	    return Null;
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
	if (count($cd) != 0) { 
	    // no possible reductions at this time
	    return array();
	}
	$value = $this->computeValue();
	$x->setValue($value);
	return array_map(function($jstate) use ($x, $value) {
		$newState = new ParserState($jstate->getX(), 
					    array_merge($jstate->getAb(), array($x)), 
					    array_slice($jstate->getCd(), 1), 
					    $jstate->getJ());
		return $newState;
	    },
	    array_values(array_filter($chart[$j], function($jstate) use ($x) {
			return count($jstate->getCd()) > 0 and $jstate->getCd()[0]->equal($x);
		    }
		    )));
	return $newState;
    }

    private function computeValue()
    {
	assert(count($this->getCd()) == 0);
	$p = array(null); // p[0] : Value of x (to be computed)
	foreach ($this->getAb() as $token) {
	    $p[] = $token->getValue();
	}
	$p = $this->doReduction($p);
	return $p[0];
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