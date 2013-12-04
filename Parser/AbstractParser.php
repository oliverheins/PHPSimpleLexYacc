<?php
/** Parser module of PhpSimpleLexCC
 *
 * @package Parser
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */
namespace PHPSimpleLexYacc\Parser;

require_once("ParserRule.php");
require_once("ParserState.php");
require_once("ParserChart.php");
require_once("ParserToken.php");
require_once("ParserGrammar.php");

/** core parser class
 * 
 * The core functions of the parser.
 * 
 * @abstract
 */
abstract class AbstractParser
{
    /** the list of grammar rules
     *
     * @var array
     * @see AbstractParser::setGrammar()
     */
    private $grammar;
    
    /** the chart table
     *
     * @var PHPSimpleLexYacc\Parser\ParserChart 
     * @see AbstractParser::printChart()
     */
    private $chart;
    
    /** the length of the chart
     *
     * @var int
     */
    private $chartLength;
    
    /** the grammar rule with which parsing starts
     *
     * @var PHPSimpleLexYacc\Parser\ParserRule
     * @see AbstractParser::setGrammar()
     */
    private $start_rule;
    
    /** the list of complex points
     *
     * A complex point is a rule, at which a symbol occurs only on rhs, not on 
     * lhs.  At this point, not only reduction is possible, but also the removal
     * of abiguousity.
     * 
     * @var array
     * @see AbstractParser::setComplexPoints(), AbstractParser::setComplexPoint() 
     */
    private $complex;
    
    /** the debug level
     *
     * 0: no debug output, 1: some debug output, 2: verbose debug output
     * 
     * @var int
     * @see AbstractParser::getDebugLevel(), AbstractParser::setDebugLevel() 
     */
    protected $debuglevel = 0;

    /** Returns the list of states which are considered final
     * 
     * A state is considered final when it is a) in the last chart, and b) 
     * equals the start rule.  That means the parser ran successfully, and the
     * state represents that successful run.
     * 
     * @return array
     */
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

    /** Sets the grammar
     * 
     * @param array $grammar
     * @return void
     * @see AbstractParser::grammar, PHPSimpleLexYacc\Parser\ParserRule
     */
    protected function setGrammar(array $grammar)
    {
	assert(count($grammar) > 0);
	foreach ($grammar as $rule) {
	    assert($rule instanceof ParserRule);
	}
	$this->grammar = new ParserGrammar($grammar);
	$this->start_rule = $grammar[0];
    }

    /** Sets the complex points
     * 
     * A complex point is a rule, at which a symbol occurs only on rhs, not on 
     * lhs.  At this point, not only reduction is possible, but also the removal
     * of abiguousity.
     * 
     * @param array $complexPoints  list of complex points
     * @return void
     * @see AbstractParser::complex, AbstractParser::setComplexPoint()
     */
    protected function setComplexPoints(array $complexPoints)
    {
	$this->complex = array();
	foreach ($complexPoints as $symbol => $rules) {
	    foreach ($rules as $point) {
		$rule = $this->grammar[$point[0]];
		$pos = $point[1];
		assert($rule instanceof ParserRule);
		assert(is_int($pos));
		$this->setComplexPoint($rule, $pos);
	    }
	}
    }

    /** Sets a particular complex point
     * 
     * A complex point is a rule, at which a symbol occurs only on rhs, not on 
     * lhs.  At this point, not only reduction is possible, but also the removal
     * of abiguousity.
     * 
     * @param ParserRule $rule  A parser rule
     * @param int $pos          the position on the rhs
     * @return void
     * @see AbstractParser::complex, AbstractParser::setComplexPoints()
     */
    protected function setComplexPoint(ParserRule $rule, $pos)
    {
	assert(is_int($pos));
	$str = $rule->__toString();
	if(!isset($this->complex[$str])) {
	    $this->complex[$str] = array();
	}
	$this->complex[$str][$pos] = true;
    }

    /** Returns if a rank is higher than another
     * 
     * @param array $one
     * @param array $two
     * @return boolean
     * @throws \Exception
     */
    protected function higherRank(array $one, array $two)
    {
	for ($i = 0, $cone = count($one), $ctwo = count($two); $i < min($cone, $ctwo); $i++) {
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
            if ($prec1 > $prec2) { return true; }
            if ($prec1 < $prec2) { return false; }
	    if ($assoc1 != $assoc2) {
		throw new \Exception('Associativity Error.  These symbols should have the same associativity.  Check your grammar!');
	    }
	    switch ($assoc1) {
	    case 'left':
                if ($left1 > $left2) { return true; }
                if ($left1 < $left2) { return false; }
		break;
	    case 'right':
                if ($right1 > $right2) { return true; }
                if ($right1 < $right2) { return false; }
		break;
	    default:
		throw new \Exception('Associativity Error: neither left nor right (' . $assoc1 .')');
	    }
	}
	return false;
    }

    /** Sorts out the lower ranked states of a list of states
     * 
     * From a given list of states, only the highest ranked survives.  The rest
     * get sorted out.
     * 
     * @param array &$states
     * @see AbstractParser::higherRank(), ParserState::getRank()
     * @return void
     */
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

    /** Internal method for removing ambiguous states
     * 
     * @param array $states
     * @return void
     * @see AbstractParser::removeAmbiguity(), AbstractParser::applyRanking()
     */
    protected function _removeAmbiguity(array &$states)
    {
	$table = array();
	$remaining = array();
	foreach ($states as $state) {
	    assert($state instanceof ParserState);
	    $rule = $state->getRule()->__toString();
	    $from = $state->getJ();
	    $pos = count($state->getAb());
	    if (!isset($table[$rule])) {
		$table[$rule] = array();
	    }
	    if (!isset($table[$rule[$from]])) {
		$table[$rule][$from] = array();
	    }
	    if (!isset($table[$rule][$from][$pos])) {
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

    /** Removes ambiguous states from a chart
     * 
     * Scans for amiguous states in a chart.  If any, ranking is applied to sort
     * out the least interesting.
     * 
     * @param int $i
     * @return void
     * @see AbstractParser::_removeAmbiguity(), AbstractParser::hasAmbiguity()
     */
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

    /** Checks if the state is a potential ambiguous one
     * 
     * If the given state has a complex point at the last reduced symbol, it is
     * considered to be ambiguous.
     * 
     * @param \PHPSimpleLexYacc\Parser\ParserState $state
     * @return boolean
     * @see AbstractParser::removeAmbiguity(), AbstractParser::complex
     */
    protected function hasAmbiguity(ParserState $state)
    {
	$rule = $state->getRule();
	if (! $rule instanceof ParserRule) {
	    // pseudo state, not of interest.
	    return false;
	}
	$rule = $rule->__toString();
	// check if rule may be of any interest, return false if not
	if (!isset($this->complex[$rule])) {
	    return false;
	}
	$ab = $state->getAb();
	// pos of last reduced symbol
	$pos = count($ab) - 1; 
	return isset($this->complex[$rule][$pos]);
    }

    /** Does the postponed shifting.
     * 
     * @param int $i   the chart number
     * @see ParserState::getShifts(), ParserState::shift()
     */
    protected function doShifts($i)
    {
	assert(is_int($i));
	foreach ($this->chart->get($i) as $state) {
	    foreach ($state->getShifts() as $shift_state) {
		$this->chart->add($i+1, $shift_state);
	    }
	}
    }

    /** The main parse function
     * 
     * Basically a chart is build from a start state, and ongoing from 
     * subsequently doing the closure (i.e. generating potential next states),
     * shifting (actual seeing if a potential next state fits with the next 
     * input token) and reducing (i.e. putting the result of a finished rule 
     * back to its origin rule).  Further on, some optimizations are done, 
     * basically removing ambiguous states and garbage collection.  If 
     * $debuglevel is set, the chart is printed.
     * 
     * @param array $tokens   The list of tokens to be parsed
     * @return void
     * @see ParserChart, ParserState, ParserState::closure(), ParserState::shift(), ParserState::reductions(), ParserChart::garbageCollection(), AbstractParser::removeAmbiguity(), AbstractParser::doShifts(), AbstractParser::getDebugLevel(), AbstractParser::printChart()
     */
    public function parse(array $tokens)
    {
	$tokens[] = "end_of_input_marker"; // TODO
	// Create chart, length = no of tokens
        $this->chartLength = count($tokens);
	$this->chart = new ParserChart($this->chartLength);
	// $start_state: StartSymbol -> [] . [StartRule] from 0
	$start_state = new ParserState($this->start_rule->getSymbol(), [], $this->start_rule->getRule(), 0, 0, $this->start_rule);
	// Add $start_state to the chart
	$this->chart->set(0, $start_state);
	for ($i = 0; $i < $this->chartLength; $i++) {
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
//		    $next_states = $state->reductions($this->chart->get($state->getJ()), $i);
                    $reductions = $this->chart->getReductions($state->getJ(), $state->getX()->getType());
    		    $next_states = $state->reductions($reductions, $i);
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

	    // if ($this->getDebugLevel() == 2) {
	//	$this->printChart($tokens);
	    // }
            $this->chart->garbageCollection($i);
	    if ($this->getDebugLevel() == 2) {
		$this->printChart($tokens, $i+1);
	    }
	}

	if ($this->getDebugLevel() == 1) {
	    $this->printChart($tokens, $i+1);
	}

    }
    
    /** Sets the debug level
     * 
     * 0: no debug output, 1: some debug output, 2: verbose debug output
     * 
     * @param int $level
     * @return void
     * @see AbstractParser::debuglevel, AbstractParser::getDebugLevel()
     */
    protected function setDebugLevel($level)
    {
	assert(is_int($level) and $level >= 0 and $level <= 2);
	$this->debuglevel = $level;
    }

    /** Returns the debug level
     * 
     * 0: no debug output, 1: some debug output, 2: verbose debug output
     * 
     * @return int
     * @see AbstractParser::debuglevel, AbstractParser::setDebugLevel()
     */
    protected function getDebugLevel()
    {
	$debuglevel = $this->debuglevel;
	assert(is_int($debuglevel) and $debuglevel >= 0 and $debuglevel <= 2);
	return $debuglevel;
    }

    /** Prints the chart
     * 
     * Stops at chartnr, so no empty charts are printed.
     * 
     * @param array $tokens
     * @param int $chartnr
     * @return void
     */
    private function printChart($tokens, $chartnr)
    {
	for ($n = 0, $count = count($tokens); $n < min($chartnr, $count); $n++) {
	    echo "== chart " . $n . "<br>\n";
	    foreach ($this->chart->get($n) as $state) {
		$x  = $state->getX();
		$ab = $state->getAb();
		$cd = $state->getCd();
		$j  = $state->getJ();
		$val = $x->getValue();
                $alive = $state->getAliveInChart();
                if (is_array($val)) {
                    $val = "Array"; 
                }
		echo "&nbsp;&nbsp;&nbsp;&nbsp;" . $x->getType() . " -> ";
		foreach ($ab as $sym) {
                    $sval = $sym->getValue();
                    if (is_array($sval)) {
                        $sval = "Array";
                    }
		    echo $sym->getType() . " (" . $sval . ") ";
		}
		echo ". ";
		foreach ($cd as $sym) {
		    echo $sym->getType() . " ";
		}
		echo "from " . $j . " (" . $val . ") alive in " . $alive . "<br>\n";
		if (count($state->getContainer()) != 0) {
//		    var_dump($state->getContainer());
		}
	    }
	}
	echo "<hr>\n";
    }

}

