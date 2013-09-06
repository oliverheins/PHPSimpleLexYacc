<?php

include_once("ParserRule.php");
include_once("ParserState.php");

class AbstractParser
{

// work_count = 0      # track one notion of "time taken"
// 
// def addtoset(theset,index,elt):
//   if not (elt in theset[index]):
//     theset[index] = [elt] + theset[index]
//     return True
//   return False

    private $grammar;

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

    protected function addToSet($set, $index, $elt) 
    {
	if (!array_key_exists($index, $set)) {
	    $set[$index] = array();
	}
	if (!in_array($elt, $set[$index])) {
	    array_unshift($set[$index], $elt);
	    return True;
	}
	return False;
    }

    public function parse(array $tokens)
    {
	$work_count = 0;
	$tokens[] = "end_of_input_marker"; // TODO
	$chart = array();
	$start_rule = $this->grammar[0];
	// Create chart as list of empty lists, length = no of tokens
	for ($i = 0; $i >= count($tokens); $i++) {
	    $chart[$i] = array();
	}
	// $start_state: StartSymbol -> [] . [StartRule] from 0
	$start_state = new ParserState($start_rule->getSymbol(), [], $start_rule->getRule(), 0);
	// Add $start_state to the chart
	$chart[0] = array($start_state);
	for ($i = 0; $i < count($tokens); $i++) {
	  $z = 0;
	    while (True) {
		$z++; // WORKAROUND
		if ($z >= 1000) break; // WORKAROUND
		$changes = false;
		foreach ($chart[$i] as $state) {
		    // Current State ==   x -> ab . cd from j
		    // Option 1: For each grammar rule c -> p q r
		    // (where the c's match)
		    // make a next state               c -> . p q r , i
		    // English: We're about to start parsing a "c", but
		    //  "c" may be something like "exp" with its own
		    //  production rules. We'll bring those production rules in.
		    $next_states = $state->closure($this->grammar, $i);
		    foreach ($next_states as $next_state) {
			$changes = $this->addToSet($chart, $i, $next_state) || $changes;
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
			$changes = $this->addToSet($chart, $i+1, $next_state) || $changes;
		    }

		    // Current State ==   x -> a b . c d , j
		    // Option 3: If cd is [], the state is just x -> a b . , j
		    // for each p -> q . x r , l in chart[j]
		    // make a new state                p -> q x . r , l
		    // in chart[i]
		    // English: We just finished parsing an "x" with this token,
		    //  but that may have been a sub-step (like matching "exp -> 2"
		    //  in "2+3"). We should update the higher-level rules as well.
		    $next_states = $state->reductions($chart, $i);
		    foreach ($next_states as $next_state) {
			$changes = $this->addToSet($chart, $i, $next_state) || $changes;

		    }
		    // We're done if nothing changed!
		}
		if ($changes == false) {
		    break;
		}
	    }

//	    for ($i = 0; $i < count($tokens); $i++) {
//		echo "== chart " . $i . "<br>\n";
//		foreach ($chart[$i] as $state) {
//		    $x  = $state->getX();
//		    $ab = $state->getAb();
//		    $cd = $state->getCd();
//		    $j  = $state->getJ();
//		    echo "&nbsp;&nbsp;&nbsp;&nbsp;" . $x . " -> ";
//		    foreach ($ab as $sym) {
//			echo $sym . " ";
//		    }
//		    echo ". ";
//		    foreach ($cd as $sym) {
//			echo $sym . " ";
//		    }
//		    echo "from " . $j . "<br>\n";
//		}
//	    }
	}
    }


// 
// def parse(tokens,grammar):
//   global work_count
//   work_count = 0
//   tokens = tokens + [ "end_of_input_marker" ]
//   chart = {}
//   start_rule = grammar[0]
//   for i in range(len(tokens)+1):
//     chart[i] = [ ]
//   start_state = (start_rule[0], [], start_rule[1], 0)
//   chart[0] = [ start_state ]
//   for i in range(len(tokens)):
//     while True:
//       changes = False
//       for state in chart[i]:
//         # State ===   x -> a b . c d , j
//         x = state[0]
//         ab = state[1]
//         cd = state[2]
//         j = state[3]
// 
//         # Current State ==   x -> a b . c d , j
//         # Option 1: For each grammar rule c -> p q r
//         # (where the c's match)
//         # make a next state               c -> . p q r , i
//         # English: We're about to start parsing a "c", but
//         #  "c" may be something like "exp" with its own
//         #  production rules. We'll bring those production rules in.
//         next_states = [ (rule[0],[],rule[1],i)
//           for rule in grammar if cd <> [] and cd[0] == rule[0] ]
//         work_count = work_count + len(grammar)
//         for next_state in next_states:
//           changes = addtoset(chart,i,next_state) or changes
// 
//         # Current State ==   x -> a b . c d , j
//         # Option 2: If tokens[i] == c,
//         # make a next state               x -> a b c . d , j
//         # in chart[i+1]
//         # English: We're looking for to parse token c next
//         #  and the current token is exactly c! Aren't we lucky!
//         #  So we can parse over it and move to j+1.
//         if cd <> [] and tokens[i] == cd[0]:
//           next_state = (x, ab + [cd[0]], cd[1:], j)
//           changes = addtoset(chart,i+1,next_state) or changes
// 
//         # Current State ==   x -> a b . c d , j
//         # Option 3: If cd is [], the state is just x -> a b . , j
//         # for each p -> q . x r , l in chart[j]
//         # make a new state                p -> q x . r , l
//         # in chart[i]
//         # English: We just finished parsing an "x" with this token,
//         #  but that may have been a sub-step (like matching "exp -> 2"
//         #  in "2+3"). We should update the higher-level rules as well.
//         next_states = [ (jstate[0], jstate[1] + [x], (jstate[2])[1:],
//                          jstate[3] )
//           for jstate in chart[j]
//           if cd == [] and jstate[2] <> [] and (jstate[2])[0] == x ]
//         work_count = work_count + len(chart[j])
//         for next_state in next_states:
//           changes = addtoset(chart,i,next_state) or changes
// 
//       # We're done if nothing changed!
//       if not changes:
//         break
// 
// ## Uncomment this block if you'd like to see the chart printed.
// #
// #  for i in range(len(tokens)):
// #   print "== chart " + str(i)
// #   for state in chart[i]:
// #     x = state[0]
// #     ab = state[1]
// #     cd = state[2]
// #     j = state[3]
// #     print "    " + x + " ->",
// #     for sym in ab:
// #       print " " + sym,
// #     print " .",
// #     for sym in cd:
// #       print " " + sym,
// #     print "  from " + str(j)
// 
// # Uncomment this block if you'd like to see the chart printed
// # in cases where it's important to see quotes in the grammar
// # for i in range(len(tokens)):
// #   print "== chart " + str(i)
// #   for state in chart[i]:
// #     x = state[0]
// #     ab = state[1]
// #     cd = state[2]
// #     j = state[3]
// #     print "    " + x.__repr__() + " ->",
// #     for sym in ab:
// #       print " " + sym.__repr__(),
// #     print " .",
// #     for sym in cd:
// #       print " " + sym.__repr__(),
// #     print "  from " + str(j)
// 
// accepting_state = (start_rule[0], start_rule[1], [], 0)
//   return accepting_state in chart[len(tokens)-1]
// 
// grammar = [
//   ("S", ["P" ]) ,
//   ("P", ["(" , "P", ")" ]),
//   ("P", [ ]) ,
// ]
// tokens = [ "(", "(", ")", ")"]
// result=parse(tokens, grammar)
// print result

}

//$grammar = [ 
//    ["exp", ["exp", "+", "exp"]],
//    ["exp", ["exp", "-", "exp"]],
//    ["exp", ["(", "exp", ")"]],
//    ["exp", ["num"]],
//    ["t",["I","like","t"]],
//    ["t",[""]]
//];

//$accepting_state = [$start_rule[0], $start_rule[1], [], 0]
// accepting_state = (start_rule[0], start_rule[1], [], 0)
//   return accepting_state in chart[len(tokens)-1]
//
$grammar = [
	    new ParserRule("S", ["P" ]),
	    new ParserRule("P", ["(" , "P", ")" ]),
	    new ParserRule("P", [ ]),
	    ];
$tokens = [ "(", "(", ")", ")"];
$test = new AbstractParser($grammar);
$result=$test->parse($tokens);
//echo $result;


echo "Jupp!";

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

