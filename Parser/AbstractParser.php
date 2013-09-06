<?php

class AbstractParser
{

// work_count = 0      # track one notion of "time taken"
// 
// def addtoset(theset,index,elt):
//   if not (elt in theset[index]):
//     theset[index] = [elt] + theset[index]
//     return True
//   return False

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

    private function closure($grammar, $i, $x, $ab, $cd)
    {
	// $x->$ab.$cd
	$next_states = array_map(function($rule) use ($i) { 
		return array($rule[0], [], $rule[1], $i); 
	    }, 
	    array_values(array_filter($grammar, function($rule) use ($cd) { 
			return count($cd) > 0 and $rule[0] == $cd[0]; 
		    }
		    )));
	return $next_states;
    }

    private function shift ($tokens, $i, $x, $ab, $cd, $j) 
    {
	// x->ab.cd from j tokens[i]==c?
	if (count($cd) > 0 and $tokens[$i] == $cd[0]) {
	    return array($x, array_merge($ab, array($cd[0])), array_slice($cd, 1), $j);
	} else {
	    return Null;
	}
    }

    private function reductions($chart, $i, $x, $ab, $cd, $j)
    {
	// ab. from j
	// chart[j] has y->... .x ....from k
	return array_map(function($jstate) use ($x) {
		return array($jstate[0], array_merge($jstate[1], array($x)), array_slice($jstate[2], 1), $jstate[3]);
	    },
	    array_values(array_filter($chart[$j], function($jstate) use ($cd, $x) {
			return count($cd) == 0 and count($jstate[2]) > 0 and $jstate[2][0] == $x;
		    }
		    )));
    }

    public function parse($tokens, $grammar)
    {
	$work_count = 0;
	$tokens[] = "end_of_input_marker";
	$chart = array();
	$start_rule = $grammar[0];
	for ($i = 0; $i >= count($tokens); $i++) {
	    $chart[$i] = array();
	}
	$start_state = array($start_rule[0], [], $start_rule[1], 0);
	$chart[0] = $start_state;
	for ($i = 0; $i < count($tokens); $i++) {
	    while (True) {
		$changes = False;
		foreach ($chart[$i] as $state) {
		    // State ===   x -> a b . c d , j
		    $x = $state[0];
		    $ab = $state[1];
		    $cd = $state[2];
		    $j = $state[3];

		    // Current State ==   x -> a b . c d , j
		    // Option 1: For each grammar rule c -> p q r
		    // (where the c's match)
		    // make a next state               c -> . p q r , i
		    // English: We're about to start parsing a "c", but
		    //  "c" may be something like "exp" with its own
		    //  production rules. We'll bring those production rules in.
		    $next_states = $this->closure($grammar, $i, $x, $ab, $cd);
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
		    $next_state = $this->shift($tokens, $i, $x, $ab, $cd, $j);
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
		    $next_states = $this->reductions($chart, $i, $x, $ab, $cd, $j);
		    foreach ($next_states as $next_state) {
			$changes = $this->addToSet($chart, $i, $next_state) || $changes;

		    }
		    // We're done if nothing changed!
		}
		if ($changes == False) {
		    break;
		}

	    }
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

$test = new AbstractParser();
$grammar = [ 
    ["exp", ["exp", "+", "exp"]],
    ["exp", ["exp", "-", "exp"]],
    ["exp", ["(", "exp", ")"]],
    ["exp", ["num"]],
    ["t",["I","like","t"]],
    ["t",[""]]
	     ];

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

