<?php

include_once("ParserRule.php");
include_once("ParserState.php");

class AbstractParser
{

    private $grammar;
    private $chart = array();

    public function __construct(array $grammar)
    {
	$this->setGrammar($grammar);
    }

    protected function setGrammar(array $grammar)
    {
	assert(count($grammar) > 0);
	foreach ($grammar as $rule) {
	    assert($rule instanceof ParserRule);
	}
	$this->grammar = $grammar;
    }

    protected function addToChart($index, $elt) 
    {
	if (!array_key_exists($index, $this->chart)) {
	    $this->chart[$index] = array();
	}
	if (!in_array($elt, $this->chart[$index])) {
	    array_unshift($this->chart[$index], $elt);
	    return True;
	}
	return False;
    }

    public function parse(array $tokens)
    {
	$work_count = 0;
	$tokens[] = "end_of_input_marker"; // TODO
	$start_rule = $this->grammar[0];
	// Create chart as list of empty lists, length = no of tokens
	for ($i = 0; $i <= count($tokens); $i++) {
	    $this->chart[$i] = array();
	}
	// $start_state: StartSymbol -> [] . [StartRule] from 0
	$start_state = new ParserState($start_rule->getSymbol(), [], $start_rule->getRule(), 0);
	// Add $start_state to the chart
	$this->chart[0] = array($start_state);
	for ($i = 0; $i < count($tokens); $i++) {
	  $z = 0;
	    while (True) {
		$z++; // WORKAROUND
		if ($z >= 1000) break; // WORKAROUND
		$changes = false;
		foreach ($this->chart[$i] as $state) {
		    assert ($state instanceof ParserState);
		    // Current State ==   x -> ab . cd from j
		    // Option 1: For each grammar rule c -> p q r
		    // (where the c's match)
		    // make a next state               c -> . p q r , i
		    // English: We're about to start parsing a "c", but
		    //  "c" may be something like "exp" with its own
		    //  production rules. We'll bring those production rules in.
		    $next_states = $state->closure($this->grammar, $i);
		    foreach ($next_states as $next_state) {
			$changes = $this->addToChart($i, $next_state) || $changes;
		    }
		   
		    // Current State ==   x -> a b . c d , j
		    // Option 2: If tokens[i] == c,
		    // make a next state               x -> a b c . d , j
		    // in chart[i+1]
		    // English: We're looking for to parse token c next
		    //  and the current token is exactly c! Aren't we lucky!
		    //  So we can parse over it and move to j+1.
		    $next_state = $state->shift($tokens, $i);
		    if ($next_state != Null) {
			$changes = $this->addToChart($i+1, $next_state) || $changes;
		    }

		    // Current State ==   x -> a b . c d , j
		    // Option 3: If cd is [], the state is just x -> a b . , j
		    // for each p -> q . x r , l in chart[j]
		    // make a new state                p -> q x . r , l
		    // in chart[i]
		    // English: We just finished parsing an "x" with this token,
		    //  but that may have been a sub-step (like matching "exp -> 2"
		    //  in "2+3"). We should update the higher-level rules as well.
		    $next_states = $state->reductions($this->chart, $i);
		    foreach ($next_states as $next_state) {
			$changes = $this->addToChart($i, $next_state) || $changes;

		    }
		    // We're done if nothing changed!
		}
		if ($changes == false) {
		    break;
		}
	    }

	    for ($zz = 0; $zz < count($tokens); $zz++) {
		echo "== chart " . $zz . "<br>\n";
		foreach ($this->chart[$zz] as $state) {
		    $x  = $state->getX();
		    $ab = $state->getAb();
		    $cd = $state->getCd();
		    $j  = $state->getJ();
		    echo "&nbsp;&nbsp;&nbsp;&nbsp;" . $x . " -> ";
		    foreach ($ab as $sym) {
			echo $sym . " ";
		    }
		    echo ". ";
		    foreach ($cd as $sym) {
			echo $sym . " ";
		    }
		    echo "from " . $j . "<br>\n";
		}
	    }
	}
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

$grammar = [
	    new ParserRule("S", ["P" ]),
	    new ParserRule("P", ["(" , "P", ")" ]),
	    new ParserRule("P", [ ]),
	    ];
$tokens = [ "(", "(", ")", ")"];
$test = new AbstractParser($grammar);
$result=$test->parse($tokens);
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

