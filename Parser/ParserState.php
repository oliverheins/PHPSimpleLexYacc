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

    public function __construct($x, array $ab, array $cd, $j)
    {
	assert(is_string($x));
	assert(is_array($ab));
	assert(is_array($cd));
	assert(is_int($j));
	$this->setX($x);
	$this->setAb($ab);
	$this->setCd($cd);
	$this->setJ($j);
    }

    public function setX($x)
    {
	assert(is_string($x));
	$this->x = $x;
    }
    
    public function getX()
    {
	$x = $this->x;
	assert(is_string($x));
	return $x;
    }

    public function setAb(array $ab)
    {
	foreach ($ab as $sym) {
	    assert(is_string($sym));
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
	    assert(is_string($sym));
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
	assert(is_callable($this->reduction));
	$result = $this->reduction($tokens);
	return $result;
    }

    public function closure(array $grammar, $i)
    {
	assert(is_int($i));
	$cd = $this->getCd();
	// x->ab.cd
	$next_states = array_map(function($rule) use ($i) {
		assert($rule instanceof ParserRule);
		return new ParserState($rule->getSymbol(), [], $rule->getRule(), $i); 
	    }, 
	    array_values(array_filter($grammar, function($rule) use ($cd) { 
			return count($cd) > 0 and $rule->getSymbol() == $cd[0]; 
		    }
		    )));
	return $next_states;
    }

    public function shift (array $tokens, $i) 
    {
	assert(is_int($i));
	// x->ab.cd from j tokens[i]==c?
	$x = $this->getX();
	$ab = $this->getAb();
	$cd = $this->getCd();
	$j = $this->getJ();
	if (count($cd) > 0 and $tokens[$i] == $cd[0]) {
	    return new ParserState($x, 
				   array_merge($ab, array($cd[0])), 
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
	$cd = $this->getCd();
	$j = $this->getJ();
	return array_map(function($jstate) use ($x) {
		return new ParserState($jstate->getX(), 
				       array_merge($jstate->getAb(), array($x)), 
				       array_slice($jstate->getCd(), 1), 
				       $jstate->getJ());
	    },
	    array_values(array_filter($chart[$j], function($jstate) use ($cd, $x) {
			return count($cd) == 0 and count($jstate->getCd()) > 0 and $jstate->getCd()[0] == $x;
		    }
		    )));
    }


}