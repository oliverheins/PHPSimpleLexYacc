<?php

include_once("ParserRule.php");
include_once("ParserState.php");
include_once("ParserChart.php");

class AbstractParser
{

    private $grammar;
    private $chart;
    private $start_rule;
    private $complex;
    private $debuglevel;

    public function __construct(array $grammar, array $complexPoints, $debuglevel = 0)
    {
	assert(is_int($debuglevel) and $debuglevel >= 0 and $debuglevel <= 2);
	$this->setGrammar($grammar);
	$this->setComplexPoints($complexPoints);
	$this->debuglevel = $debuglevel;
    }

    public function getFinalStates()
    {
	if ($this->start_rule === Null) {
	    return Null;
	}

	$result = array();

	$chart = $this->chart->last();
	foreach ($chart as $state) {
	    if ($state->equal($this->start_rule)) {
		$result[] = $state;
	    }
	}

	return $result;
    }

    protected function setGrammar(array $grammar)
    {
	assert(count($grammar) > 0);
	foreach ($grammar as $rule) {
	    assert($rule instanceof ParserRule);
	}
	$this->grammar = $grammar;
	$this->start_rule = $grammar[0];
    }

    protected function setComplexPoints(array $complexPoints)
    {
	$this->complex = array();
	foreach ($complexPoints as $point) {
	    $rule = $point[0];
	    $pos = $point[1];
	    assert($rule instanceof ParserRule);
	    assert(is_int($pos));
	    $this->setComplexPoint($rule, $pos);
	}
    }

    protected function setComplexPoint(ParserRule $rule, $pos)
    {
	assert(is_int($pos));
	$str = $rule->__toString();
	if(!array_key_exists($str, $this->complex)) {
	    $this->complex[$str] = array();
	}
	$this->complex[$str][$pos] = true;
    }

    protected function higherRank(array $one, array $two)
    {
	for ($i = 0; $i < min(count($one), count($two)); $i++) {
	    $curr1 = $one[$i];
	    $curr2 = $two[$i];
	    assert(count($curr1 == 4) && count($curr2 == 4));
	    $prec1  = $curr1[0];
	    $assoc1 = $curr1[1];
	    $left1  = $curr1[2];
	    $right1 = $curr1[3];
	    $prec2  = $curr2[0];
	    $assoc2 = $curr2[1];
	    $left2  = $curr2[2];
	    $right2 = $curr2[3];
	    if ($prec1 > $prec2) return true;
	    if ($prec1 < $prec2) return false;
	    if ($assoc1 != $assoc2) {
		throw new Exception('Associativity Error.  These symbols should have the same associativity.  Check your grammar!');
	    }
	    switch ($assoc1) {
	    case 'left':
		if ($left1 > $left2) return true;
		if ($left1 < $left2) return false;
		break;
	    case 'right':
		if ($right1 > $right2) return true;
		if ($right1 < $right2) return false;
		break;
	    default:
		throw new Exception('Associativity Error: neither left nor right (' . $assoc1 .')');
	    }
	}
	return false;
    }

    protected function applyRanking(array &$states)
    {
	$ranking = array();
	$i = 0;
	foreach ($states as $state) {
	    assert($state instanceof ParserState);
	    $last = end($state->getAb());
	    $history = $last->getHistory();
	    $ranking[$i] = $history->getRank();
	    $i++;
	}
	
	$best = null;
	$i = 0;
	foreach ($ranking as $rank) {
	    if ($i == 0) {
		$best = $rank;
		$bestI = 0;
	    } else {
		if ($this->higherRank($rank, $best)) {
		    $best = $rank;
		    $bestI = $i;
		}
	    }
	    $i++;
	}

	$states = array($states[$bestI]);
    }

    protected function _removeAmbiguity(array &$states)
    {
	$table = array();
	$remaining = array();
	foreach ($states as $state) {
	    assert($state instanceof ParserState);
	    $rule = $state->getRule()->__toString();
	    $from = $state->getJ();
	    $pos = count($state->getAb());
	    if (!array_key_exists($rule, $table)) {
		$table[$rule] = array();
	    }
	    if (!array_key_exists($from, $table[$rule])) {
		$table[$rule][$from] = array();
	    }
	    if (!array_key_exists($pos, $table[$rule][$from])) {
		$table[$rule][$from][$pos] = array();
	    }
	    $table[$rule][$from][$pos][] = $state;
	}

	foreach ($table as $t1) {
	    foreach ($t1 as $t2) {
		foreach ($t2 as $t3) {
		    assert(count($t3) > 0);
		    if (count($t3) > 1) {
			$this->applyRanking($t3);
		    }
		    foreach ($t3 as $state) {
			$remaining[] = $state;
		    }
		}
	    }
	}
	$states = $remaining;
    }

    protected function removeAmbiguity($i)
    {
	assert(is_int($i));
	$filtered = array();
	$interesting = array();
	foreach ($this->chart->get($i) as $state) {
	    assert($state instanceof ParserState);
	    if ($this->hasAmbiguity($state)) {
		$interesting[] = $state;
	    } else {
		// not possible to reduce any ambiguity here, just proceed.
		$filtered[] = $state;
	    }
	}
	if (count($interesting) == 0) {
	    // nothing to do
	    return;
	}
	$this->_removeAmbiguity($interesting);
	$this->chart->set($i, array_merge($filtered, $interesting));
    }

    protected function hasAmbiguity(ParserState $state)
    {
	$rule = $state->getRule();
	if (! $rule instanceof ParserRule) {
	    // pseudo state, not of interest.
	    return false;
	}
	$rule = $rule->__toString();
	// check if rule may be of any interest, return false if not
	if (!array_key_exists($rule, $this->complex)) {
	    return false;
	}
	$ab = $state->getAb();
	// pos of last reduced symbol
	$pos = count($ab) - 1; 
	return array_key_exists($pos, $this->complex[$rule]);
    }

    protected function doShifts($i)
    {
	assert(is_int($i));
	foreach ($this->chart->get($i) as $state) {
	    foreach ($state->getShifts() as $shift_state) {
		$this->chart->add($i+1, $shift_state);
	    }
	}
    }

    public function parse(array $tokens)
    {
	$work_count = 0;
	$tokens[] = "end_of_input_marker"; // TODO
	// Create chart as list of empty lists, length = no of tokens
	$this->chart = new ParserChart(count($tokens));
	// $start_state: StartSymbol -> [] . [StartRule] from 0
	$start_state = new ParserState($this->start_rule->getSymbol(), [], $this->start_rule->getRule(), 0);
	// Add $start_state to the chart
	$this->chart->set(0, $start_state);
	for ($i = 0; $i < count($tokens); $i++) {
	    $z = 0;
	    while (True) {
		$changes = false;
		foreach ($this->chart->get($i) as $state) {
		    assert ($state instanceof ParserState);
		    // If this state has already been processed, don't
		    // work on it again
		    if ($state->getProcessed() === true) {
			continue;
		    } else {
			$state->setProcessed();
		    }
		    // Current State ==   x -> ab . cd from j
		    // Option 1: For each grammar rule c -> p q r
		    // (where the c's match)
		    // make a next state               c -> . p q r , i
		    // English: We're about to start parsing a "c", but
		    //  "c" may be something like "exp" with its own
		    //  production rules. We'll bring those production rules in.
		    $next_states = $state->closure($this->grammar, $i);
		    foreach ($next_states as $next_state) {
			$changes = $this->chart->add($i, $next_state) || $changes;
		    }
		    // Current State ==   x -> a b . c d , j
		    // Option 2: If tokens[i] == c,
		    // make a next state               x -> a b c . d , j
		    // in chart[i+1]
		    // English: We're looking for to parse token c next
		    //  and the current token is exactly c! Aren't we lucky!
		    //  So we can parse over it and move to j+1.
		    //
		    // We postpone the creating of the state in
		    // chart[i+1] to later, when all closures and
		    // reductions are done.  Not all reductions will
		    // survive, so we add the shifts to its base state
		    // and add them later on to the chart -- but only,
		    // if the base state survived.
		    $state->shift($tokens, $i);
		    /* $next_state = $state->shift($tokens, $i);
		    if ($next_state != Null) {
			$changes = $this->chart->add($i+1, $next_state) || $changes;
			} */

		    // Current State ==   x -> a b . c d , j
		    // Option 3: If cd is [], the state is just x -> a b . , j
		    // for each p -> q . x r , l in chart[j]
		    // make a new state                p -> q x . r , l
		    // in chart[i]
		    // English: We just finished parsing an "x" with this token,
		    //  but that may have been a sub-step (like matching "exp -> 2"
		    //  in "2+3"). We should update the higher-level rules as well.
		    $next_states = $state->reductions($this->chart->get(-1), $i);
		    foreach ($next_states as $next_state) {
			$changes = $this->chart->add($i, $next_state) || $changes;
		    }
		    // We're done if nothing changed!
		}

		if ($changes == false) {
		    break;
		}
	    }

	    // apply precedence and associativity rules to remove as
	    // much ambiguity as possible
	    $this->removeAmbiguity($i);
	    // we postponed shifting, so we do it now.
	    $this->doShifts($i);

	    if ($this->getDebugLevel() == 2) {
		$this->printChart($tokens);
	    }

	}

	if ($this->getDebugLevel() == 1) {
	    $this->printChart($tokens);
	}

    }

    protected function getDebugLevel()
    {
	$debuglevel = $this->debuglevel;
	assert(is_int($debuglevel) and $debuglevel >= 0 and $debuglevel <= 2);
	return $debuglevel;
    }

    private function printChart($tokens)
    {
	for ($zz = 0; $zz < count($tokens); $zz++) {
	    echo "== chart " . $zz . "<br>\n";
	    foreach ($this->chart->get($zz) as $state) {
		$x  = $state->getX();
		$ab = $state->getAb();
		$cd = $state->getCd();
		$j  = $state->getJ();
		$val = $x->getValue();
		echo "&nbsp;&nbsp;&nbsp;&nbsp;" . $x->getType() . " -> ";
		foreach ($ab as $sym) {
		    echo $sym->getType() . " (" . $sym->getValue() . ") ";
		}
		echo ". ";
		foreach ($cd as $sym) {
		    echo $sym->getType() . " ";
		}
		echo "from " . $j . " (" . $val . ")<br>\n";
	    }
	}
	echo "<hr>\n";
    }

}

//$grammar = [ 
//    ["exp", ["exp", "+", "exp"]],
//    ["exp", ["exp", "-", "exp"]],
//    ["exp", ["(", "exp", ")"]],
//    ["exp", ["num"]],
//    ["t",["I","like","t"]],
//    ["t",[""]]
//];

//$grammar = [
//	    new ParserRule("S", ["P" ]),
//	    new ParserRule("P", ["(" , "P", ")" ]),
//	    new ParserRule("P", [ ]),
//	    ];
//$tokens = [ "(", "(", ")", ")"];
//$test = new AbstractParser($grammar);
//$result=$test->parse($tokens);
//echo $result;

//$chart = [[['exp', ['exp'], ['+', 'exp'], 0], ['exp', [], ['num'], 0], ['exp', [], ['[', 'exp', ']'], 0], ['exp', [], ['exp', '-', 'exp'], 0], ['exp', [], ['exp', '+', 'exp'], 0]], [['exp', ['exp', '+'], ['exp'], 0]], [['exp', ['exp', '+', 'exp'], [], 0]]];
//
//echo "\n\n\n";
//
//var_dump($test->reductions($chart,2,'exp',['exp','+','exp'],[],0));
//
//echo "\n\n\n";
//
//var_dump( [['exp', ['exp'], ['-', 'exp'], 0], ['exp', ['exp'], ['+', 'exp'], 0]]);
//
//echo "\n<br>\n\n";
//
//echo $test->reductions($chart,2,'exp',['exp','+','exp'],[],0) == [['exp', ['exp'], ['-', 'exp'], 0], ['exp', ['exp'], ['+', 'exp'], 0]] ? "True" : "False";

// echo $test->shift(["exp","+","exp"],2,"exp",["exp","+"],["exp"],0) == array('exp', ['exp', '+', 'exp'], [], 0) ? "True" : "False";
// echo $test->shift(["exp","+","exp"],0,"exp",[],["exp","+","exp"],0) == array('exp', ['exp'], ['+', 'exp'], 0) ? "True" : "False";
// echo $test->shift(["exp","+","exp"],3,"exp",["exp","+","exp"],[],0) == Null ? "True" : "False";
// echo $test->shift(["exp","+","ANDY LOVES COOKIES"],2,"exp",["exp","+"],["exp"],0) == Null ? "True" : "False";

