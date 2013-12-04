<?php
/** Parser module of PhpSimpleLexCC
 *
 * @package Parser
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */
namespace PHPSimpleLexYacc\Parser;

include_once('ParserGrammar.php');
include_once('Helpers/ContainerArray.php');
use PHPSimpleLexYacc\Parser\Helpers\ContainerArray;

/** holds a state of the parser
 * 
 */
class ParserState
{
    /** x -> ab . cd from j
     *
     * @var PHPSimpleLexYacc\Parser\ParserToken
     * @see ParserState::getX(), ParserState::setX()
     */
    private $x;

    /** x -> ab . cd from j
     *
     * @var array
     * @see ParserState::getAb(), ParserState::setAb()
     */
    private $ab;

    /** x -> ab . cd from j
     *
     * @var array
     * @see ParserState::getCd(), ParserState::setCd()
     */
    private $cd;
    
    /** x -> ab . cd from j
     *
     * @var int
     * @see ParserState::getJ(), ParserState::setJ()
     */
    private $j;

    /** holds the rule
     *
     * @var PHPSimpleLexYacc\Parser\ParserRule
     * @see ParserState::getRule(), ParserState::setRule()
     */
    private $rule;
    
    /** the chart number the state lives in
     *
     * @var int
     * @see ParserState::getChartNr(), ParserState::setChartNr()
     */
    private $chartNr;
    
    /** the symbol name and index key of the charts reduction table
     * 
     * @var array   array(symbolname, key)
     * @see ParserState::setReductionTableKey(), ParserState::getReductionTableKey()
     */
    public $reductionTableKey;
    
    /** holds a link to the reduction method
     * 
     * Either a closure or a string referencing a method.
     *
     * @var callable|string
     * @see ParserState::setReduction()
     */
    private $reduction;
    
    /** holds the value of the state
     *
     * @var mixed
     * @see ParserState::getValue(), ParserState::setValue()
     */
    private $value;

    /** holds the list of shifts associated with this state
     *
     * @var array
     * @see ParserState::, getShifts(), ParserState::addShift(), ParserState::shifts()
     */
    private $shifts;
    
    /** already processed?
     *
     * @var boolean
     * @see ParserState::getProcessed(), ParserState::setProcessed()
     */
    private $processed;
    
    /** holds the container of the state
     * 
     * the container is user accessible on a per state base, i.e. can be
     * used to simulate states.
     *
     * @var array
     * @see ParserState::getContainer(), ParserState::setContainer(), ParserState::cloneContainer()
     */
    private $container;
    
    /** the cache , used for the history of the state
     *
     * @var array
     * @see ParserState::getHistory()
     */
    private $cache = array();
    
    /** the predecessors of the state
     *
     * @var array   list of ParserStates
     * @see ParserState::addPredecessor(), ParserState::getPredecessors()
     */
    private $predecessors = array();
    
    /** the latest chart the state is alive in
     *
     * @var int
     * @see ParserState::setAliveInChart(), ParserState::getAliveInChart()
     */
    public $aliveInChart = 0;
    
    /** Constructor
     * 
     * @param \PHPSimpleLexYacc\Parser\ParserToken $x x -> ab . cd from j
     * @param array $ab x -> ab . cd from j
     * @param cd $cd x -> ab . cd from j
     * @param int $j x -> ab . cd from j
     * @param \PHPSimpleLexYacc\Parser\ParserRule $rule the associated rule
     * @param array $container the container
     * @param \PHPSimpleLexYacc\Parser\ParserState $predecessor the states predecessor, if any
     * @param int $chartNr  the chart no this state lives in
     */
    public function __construct($x, array $ab, array $cd, $j, $chartNr = 0, $rule = null, $container = array(), $predecessor = null)
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
        $this->x = $x;
        $this->ab = $ab;
        $this->cd = $cd;
        $this->j = $j;
        $this->reduction = $x->getReduction();
        $this->chartNr = $chartNr;
	if ($rule !== null) {
            $this->rule = $rule;
	}
        $this->container = $container;
        if ($predecessor !== null) {
            $this->predecessors[] = $predecessor;
            if ($chartNr !== null) {
                $this->setAliveInChart($chartNr);
            }
        }
        $this->cache['history'] = null;
    }
    
    /** Setter method for the chart number of this state
     * 
     * @param int $i
     * @return void
     * @see ParserState::chartNr, ParserState::getChartNr()
     */
    protected function setChartNr($i)
    {
        assert(is_int($i));
        $this->chartNr = $i;
    }
    
    /** Getter method for the chart number of this state
     * 
     * @return int
     * @see ParserState::chartNr, ParserState::setChartNr()
     */
    protected function getChartNr()
    {
        return $this->chartNr;
    }
    
    /** Setter method for the symbol name and key in the charts reduction table
     * 
     * @param string $symbolname
     * @param int $key
     * @return void
     * @see ParserState::reductionTableKey, ParserState::getReductionTableKey()
     */
    public function setReductionTableKey($symbolname, $key)
    {
        assert(is_string($symbolname) && is_int($key));
        $this->reductionTableKey = array($symbolname, $key);
    }
    
    /** Getter method for the symbol name and key in the charts reduction table
     * 
     * @return array
     * @see ParserState::reductionTableKey, ParserState::setReductionTableKey()
     */
    public function getReductionTableKey()
    {
        return $this->reductionTableKey;
    }
    
    /** Setter method for the pointer to the latest chart this state has a successor in
     * 
     * Calls itself on this states predecessor.
     * 
     * @param int $i
     * @see ParserState::aliveInChart, ParserState::getAliveInChart(), ParserState::updateAliveInChart()
     * @return void
     */
    public function setAliveInChart($i)
    {
        assert(is_int($i));
        if ($this->aliveInChart === $i) {
            // return immediately if already set to the latest chart
            return;
        }
        $this->aliveInChart = $i;
        $todo = $this->predecessors;
        while ($predecessor = array_pop($todo)) {
            if ($predecessor->updateAliveInChart($i)) {
                foreach ($predecessor->getPredecessors() as $state) {
                    $todo[] = $state;
                }
            }
        }
    }

    /** Sets the chart number the state is still alive
     * 
     * @param int $i
     * @return boolean  false if already set, true otherwise
     * @see ParserState::aliveInChart, ParserState::setAliveInChart()
     */
    public function updateAliveInChart($i)
    {
        assert(is_int($i));
        if ($this->aliveInChart === $i) {
            // return immediately if already set to the latest chart
            return false;
        }
        $this->aliveInChart = $i;
        return true;
    }
    
    /** Getter method for the pointer to the latest chart this state has a successor in
     * 
     * @return int   the chart number which has the latest successor of this state
     * @see ParserState::aliveInChart, ParserState::setAliveInChart()
     */
    public function getAliveInChart()
    {
        assert(is_int($this->aliveInChart));
        return $this->aliveInChart;
    }
    
    /** Setter method for the predecessor
     * 
     * @return void
     * @param \PHPSimpleLexYacc\Parser\ParserState $predecessor
     * @see ParserState::predecessors, ParserState::getPredecessors()
     */
    public function addPredecessor(ParserState $predecessor)
    {
        $this->predecessors[] = $predecessor;
    }
    
    /** Getter method for the predecessor
     * 
     * @return array  list of ParserStates
     * @see ParserState::predecessor, ParserState::addPredecessor()
     */
    public function getPredecessors()
    {
        return $this->predecessors;
    }

    /** Setter method for the container
     * 
     * @param array $container
     * @return void
     * @see ParserState::container, ParserState::getContainer(), ParserState::cloneContainer()
     */
    protected function setContainer(array $container)
    {
	$this->container = $container;
    }

    /** Getter method for the container
     * 
     * @return array
     * @see ParserState::container, ParserState::setContainer(), ParserState::cloneContainer()
     */
    public function getContainer()
    {
	return $this->container;
    }

    /** Makes a deep copy of the container
     * 
     * @return array
     * @see ParserState::container, ParserState::getContainer(), ParserState::setContainer(), ParserState::deepCopy()
     */
    public function cloneContainer()
    {
	return $this->deepCopy($this->container);
    }

    /** deep (recursively) copies an object
     * 
     * Works on booleans, integers, doubles, floats, strings, arrays and 
     * objects (which might need a __clone() method themselves).  Resources
     * can't be copied in a generic way, so just the reference to the resource 
     * is returned.  FIXME: exact duplicate of ContainerArray::deepCopy().
     * POSSIBLE FIX: declare ContainerArray::deepCopy() static and use it here.
     * 
     * @param mixed $object
     * @return mixed
     * @throws \Exception
     */
    protected function deepCopy($object)
    {
	$type = gettype($object);
	switch ($type) {
	case 'boolean':
	case 'integer':
	case 'double':
	case 'float':
	case 'string':
	case 'NULL':
	    return $object;
	case 'array':
	    $new = array();
	    foreach ($object as $key => $value) {
		$new[$key] = $this->deepCopy($value);
	    }
	    return $new;
	case 'object':
	    return clone $object;
	case 'resource':
	    trigger_error('Cannot clone a resource', E_WARNING);
	    return $object;
	default:
	    throw new \Exception('Tried to copy an unknown type.'); 
	}
    }

    /** is state already processed?
     * 
     * @return boolean
     * @see ParserState::processed, ParserState::setProcessed()
     */
    public function getProcessed()
    {
	return $this->processed;
    }

    /** Sets the state as processed
     * 
     * @return void
     * @see ParserState::processed, ParserState::getProcessed()
     */
    public function setProcessed()
    {
	$this->processed = true;
    }

    /** Adds a state to the shift list
     * 
     * The shift list holds the states to be processed in the next 
     * iteration of the parsing process.
     * 
     * @param \PHPSimpleLexYacc\Parser\ParserState $state
     * @return void
     * @see ParserState::getShifts(), ParserState::shifts, ParserState::shift()
     */
    private function addShift(ParserState $state)
    {
	$this->shifts[] = $state;
    }

    /** Returns the shift list
     * 
     * The shift list holds the states to be processed in the next 
     * iteration of the parsing process.
     * 
     * @return array
     * @see ParserState::addShift(), ParserState::shifts
     */
    public function getShifts()
    {
	$shifts = $this->shifts;
	$this->shifts = array();
	return $shifts;
    }

    /** Magic clone method
     * 
     * Clones x, ab and cd, which are arrays resp. a ParserState.
     * 
     * @return void
     * @see ParserState::x, ParserState::ab, ParserState::cd, ParserState::copyArray()
     */
    public function __clone()
    {
	$this->x = clone $this->x;
	$this->ab = $this->copyArray($this->ab);
	$this->cd = $this->copyArray($this->cd);
    }

    /** deep copies an array
     * 
     * FIXME: is the if clause needed?
     * 
     * @param array $array
     * @return null|array
     */
    private function copyArray(array $array)
    {
//	if ($array === null) {
//	    return null;
//	}
	$new = array();
	return array_merge($new, $array);
    }

    /** Sets the value of the state
     * 
     * @param mixed $value
     * @return void
     * @see ParserState::value, ParserState::getValue()
     */
    public function setValue($value)
    {
	$this->value = $value;
    }

    /** Returns the value of the state
     * 
     * @return mixed
     * @see ParserState::value, ParserState::setValue()
     */
    public function getValue()
    {
	return $this->value or "";
    }

    /** Sets the lhs of the state
     * 
     * x -> ab . cd from j
     * 
     * @param \PHPSimpleLexYacc\Parser\ParserToken $x
     * @return void
     * @see ParserState::x, ParserState::getX()
     */
    public function setX(ParserToken $x)
    {
	$this->x = $x;
    }
    
    /** Returns the lhs of the state
     * 
     * x -> ab . cd from j
     * 
     * @return \PHPSimpleLexYacc\Parser\ParserToken
     * @see ParserState::x, ParserState::setX()
     */
    public function getX()
    {
	$x = $this->x;
	assert($x instanceof ParserToken);
	return $x;
    }

    /** Sets the tokens processed so far
     * 
     * x -> ab . cd from j
     * 
     * @param array $ab
     * @return void
     * @see ParserState::ab, ParserState::getAb()
     */
    public function setAb(array $ab)
    {
	foreach ($ab as $sym) {
	    assert($sym instanceof ParserToken);
	}
	$this->ab = $ab;
    }
    
    /** Returns the tokens processed so far
     * 
     * x -> ab . cd from j
     * 
     * @return array
     * @see ParserState::ab, ParserState::setAb()
     */
    public function getAb()
    {
	return $this->ab;
    }

    /** Sets the tokens yet to process
     * 
     * x -> ab . cd from j
     * 
     * @param array $cd
     * @return void
     * @see ParserState::cd, ParserState::getcd()
     */
    public function setCd(array $cd)
    {
	foreach ($cd as $sym) {
	    assert($sym instanceof ParserToken);
	}
	$this->cd = $cd;
    }
    
    /** Returns the tokens yet to process
     * 
     * x -> ab . cd from j
     * 
     * @return array
     * @see ParserState::cd, ParserState::setCd()
     */
    public function getCd()
    {
	return $this->cd;
    }

    /** Sets the origin of the state
     * 
     * x -> ab . cd from j
     * 
     * @param int $j
     * @return void
     * @see ParserState::j, ParserState::getJ()
     */
    public function setJ($j)
    {
	assert(is_int($j));
	$this->j = $j;
    }
    
    /** Returns the origin of the state
     * 
     * x -> ab . cd from j
     * 
     * @return int
     * @see ParserState::j, ParserState::setJ()
     */
    public function getJ()
    {
	$j = $this->j;
	assert(is_int($j));
	return $j;
    }
    
    /** Sets the reduction rule that produced this state
     * 
     * @param \PHPSimpleLexYacc\Parser\ParserRule $rule
     * @return void
     * @see ParserState::rule, ParserState::getRule()
     */
    public function setRule(ParserRule $rule)
    {
	$this->rule = $rule;
    }

    /** Returns the reduction rule of the state
     * 
     * @return \PHPSimpleLexYacc\Parser\ParserRule
     * @see ParserState::rule, ParserState::setRule()
     */
    public function getRule()
    {
	return $this->rule;
    }

    /** Sets the reduction method
     * 
     * Holds a link to the reduction method. Either a closure or a string 
     * referencing a method.
     * 
     * @param callable|string $reduction
     * @return void
     * @see ParserState::reduction, ParserState::doReduction()
     */
    public function setReduction($reduction)
    {
	$this->reduction = $reduction;
    }

    /** Does a reduction
     * 
     * $tokens are the tokens which made up the complete state.
     * Checks whether reduction is a string or a closure, and calls the 
     * corresponding method.
     * 
     * @param \PHPSimpleLexYacc\Parser\Helpers\ContainerArray $tokens
     * @return mixed
     * @throws \Exception
     * @see ParserState::reduction, ParserState::computeValue()
     */
    public function doReduction(ContainerArray $tokens)
    {
	$reduction = $this->reduction;
        if ($reduction == Null) { return; }
	if (is_callable($reduction)) {
	    $result = $reduction->__invoke($tokens);
	} elseif (is_string($this->reduction) && $this->rule instanceof ParserRule) {
	    $parser = $this->rule->getParser();
	    assert($parser instanceof AbstractParser);
	    $result = $parser->$reduction($tokens);
	} else {
	    throw new \Exception('Reduction is not callable error.');
	}
	return $result;
    }

    /** Doing the closure of the state
     * 
     * Depending on the next expected token, all possible rules to achieve this 
     * token are returned as states.
     * 
     * @param ParserGrammar $grammar  the grammar definition
     * @param int $i          the current chart number, i.e. where the closure starts from
     * @return array          list of states
     * @see \PHPSimpleLexYacc\Parser\AbstractParser::parse()
     */
    public function closure(ParserGrammar $grammar, $i)
    {
	assert(is_int($i));
	$cd = $this->getCd();
        if (count($cd) === 0) {
            return array();
        }
	// x->ab.cd
	return array_map(function($rule) use ($i) {
		$container = $this->cloneContainer();
		return new ParserState($rule->getSymbol(), 
				       array(), 
				       $rule->getRule(), 
				       $i,
                                       $i,
				       $rule, 
				       $container,
                                       $this);
	    }, 
//	    array_values(array_filter((array) $grammar, function($rule) use ($cd) { 
//			return $rule->getSymbol()->equal($cd[0]); 
//		    }
//		    ))
                    $grammar->getClosures($cd[0])
                    );
    }

    /** Doing the shifts of the state
     * 
     * Tries to shift the state with the next token.  Shifts are postponed to 
     * $this->shifts due to performance reasons.
     * 
     * @param array $tokens   the list of input tokens
     * @param int $i          the current chart number
     * @return boolean|null   true if shifts are done, false otherwise.  null if the current token is the end_of_input_marker
     * @see \PHPSimpleLexYacc\Parser\AbstractParser::parse(), ParserState::addShift()
     */
    public function shift (array $tokens, $i) 
    {
	assert(is_int($i));
	assert($tokens[$i] instanceof Token or $tokens[$i] == "end_of_input_marker");
	if (is_string($tokens[$i])) {
	    return null;
	}
	// x->ab.cd from j tokens[i]==c?
	$cd = $this->getCd();
	if (count($cd) > 0 and $cd[0]->compare($tokens[$i])) {
            $x = $this->getX();
            $ab = $this->getAb();
            $j = $this->getJ();
            $rule = $this->getRule();
            $container = $this->cloneContainer();
            $cd0 = new ParserToken(array("type"  => $tokens[$i]->getType(),
					 "value" => $tokens[$i]->getValue()));
	    $this->addShift(new ParserState($x, 
					    array_merge($ab, array($cd0)), 
					    array_slice($cd, 1), 
					    $j,
                                            $i+1,
					    $rule,
					    $container,
                                            $this));
	    return true;
	} else {
	    return false;
	}
    }

    /** Doing the reductions of a state
     * 
     * Checks if state is finished (count(cd) == 0).  If so, returns all states
     * at chart[j] which expected x. 
     * 
     * @param array $reductions  the reduction table
     * @param int $i             the current chart number
     * @return array             the list of new states
     * @see \PHPSimpleLexYacc\Parser\AbstractParser::parse(), \PHPSimpleLexYacc\Parser\ParserToken::equal()
     */
    public function reductions(array $reductions, $i)
    {
	assert(is_int($i));
	// ab. from j
	// chart[j] has y->... .x ....from k
	$cd = $this->getCd();
	if (count($cd) != 0) { 
	    // no possible reductions at this time
	    return array();
	}
	$x = $this->getX();
	$ab = $this->getAb();
	$j = $this->getJ();
	$clone = clone $this;
	$reduction = $this->computeValue();
	$x->setValue($reduction[0]);
	$x->addToHistory($clone);
	return array_map(function($jstate) use ($x, $reduction, $i) {
		$container = $jstate->cloneContainer();
		if ($reduction instanceof ContainerArray) {
		    $reductionArray = clone $reduction;
		    foreach ($reductionArray->getContainer() as $key => $value) {
			$container[$key] = $value;
		    }
		}
		return new ParserState($jstate->getX(), 
				       array_merge($jstate->getAb(), array($x)), 
				       array_slice($jstate->getCd(), 1), 
				       $jstate->getJ(),
                                       $i,
				       $jstate->getRule(),
				       $container,
                                       $this);
	    }, $reductions);
//	    array_values(array_filter($chart->get($j), function($jstate) use ($x) {
//			return count($jstate->getCd()) > 0 and $jstate->getCd()[0]->equal($x);
//		    }
//                    )));
    }

    /** Returns the history of the state
     * 
     * makesup a unique parse tree.  the results are cached in $this->history 
     * for performance reasons.
     * 
     * @return string   The history as a string representation
     * @see ParserState::history
     */
    public function getHistory()
    {
	$tokens = $this->getAb();
        if (count($this->cache['history']) == 2 && $this->cache['history'][0] == count($tokens)) {
            return $this->cache['history'][1];
        }
	$result = "";
	foreach ($tokens as $t) {
	    $result .= " " . $t->getType();
	    $history = $t->getHistory();
	    if ($history instanceof ParserState) {
		$result .= " (" . $history->getHistory() . ")";
	    }
	    $result .= " ";
	}
        $this->cache['history'] = array(count($tokens), $result);
	return $result;
    }

    /** Returns a specific rank of the state
     * 
     * Due to the associativity and precedence of the according rule, the rank
     * is computed and returned.
     * 
     * @return array   the rank
     * @throws \Exception
     * @see ParserState::countParserTokens(), \PHPSimpleLexYacc\Parser\ParserRule::getAssociativity(), \PHPSimpleLexYacc\Parser\ParserRule::getPrecedence(), \PHPSimpleLexYacc\Parser\ParserToken::getHistory()
     */
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
		throw new \Exception('Associativity Error: neither left nor right (' . $assoc .')');
	    }
	    foreach ($child as $val) {
		$result[] = $val;
	    }
	} else {
	    $result = array();
	}

	return $result;
    }

    /** Returns the total number of terminals inherited by the state
     * 
     * @return int   the total number of terminals
     * @see ParserToken::getHistory() 
     */
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

    /** Returns the value of the state so far
     * 
     * According to the so far read symbols, the states value is computed by
     * the associated reduction function.  This method also takes care of the 
     * container.
     * 
     * @return array  
     * @see ParserState::container, ParserState::doReduction(), \PHPSimpleLexYacc\Parser\Helpers\ContainerArray
     */
    private function computeValue()
    {
	//assert(count($this->getCd()) == 0);
	$p = new ContainerArray();
	$p[0] = null;  // p[0] : Value of x (to be computed)
	$p->setContainer($this->cloneContainer());
	foreach ($this->getAb() as $token) {
	    $p[] = $token->getValue();
	}
	$p = $this->doReduction($p);
	return $p;
    }

    /** Checks if the state is fullfills the given rule.
     * 
     * WARNING: THIS METHOD SHOULD ONLY BE USED WITH THE START RULE!!!
     * 
     * @param \PHPSimpleLexYacc\Parser\ParserRule $rule
     * @return boolean
     * @see ParserState::toString(), \PHPSimpleLexYacc\Parser\ParserRule::toString()
     */
    public function equal(ParserRule $rule)
    {
	return $this->toString() == $rule->toString() . " .  from 0";
    }

    /** Returns a **unique** representation of the state
     * 
     * @return string
     * @see ParserState::toString(), \PHPSimpleLexYacc\Parser\ParserToken::__toString()
     */
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

    /** Returns a short, **non-unique** representation of the state
     * 
     * @return string
     * @see ParserState::toString(), \PHPSimpleLexYacc\Parser\ParserToken::getType()
     */
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