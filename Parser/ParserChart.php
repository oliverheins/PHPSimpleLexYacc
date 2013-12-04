<?php
/** Parser module of PhpSimpleLexCC
 *
 * @package Parser
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */
namespace PHPSimpleLexYacc\Parser;

/** The Chart of parser states
 * 
 */
class ParserChart 
{
    /** the actual chart table
     *
     * two dimensional array [chartnr][key] = state
     * 
     * @var array
     * @see ParserChart::set(), ParserChart::get(), ParserChart::add(), ParserChart::chartIncludes, ParserChart::includes
     */
    private $chart = array();
    
    /** a lookup table for a string representation of a state
     *
     * true if str_rep is in chartnr
     * two dimensional array [chartnr][str_rep] = true
     *
     * @var array()
     * @see ParserChart::set(), ParserChart::get(), ParserChart::add(), ParserChart::chartIncludes, ParserChart::chart
     */
    private $includes = array();

    /** a table mapping from a string representation of a state to its chart key
     *
     * two dimensional array [chartnr][str_rep] = key
     *
     * @var array()
     * @see ParserChart::set(), ParserChart::get(), ParserChart::add(), ParserChart::chart, ParserChart::includes
     */
    private $chartIncludes = array();
    
    /** table of possible reductions for a symbol at this chart
     *
     * two dimensional array [chartnr][symbolname] = array(states)
     * 
     * @var array
     * @see ParserChart::addToReduction(), ParserChart::getReductions()
     */
    private $reductionTable = array();
    
    /** list of charts that may need garbage collection
     *
     * @var array
     * @see ParserChart::garbageCollection()
     */
    private $gcTable = array();

    /** Constructor
     * 
     * Creates $chart, $includes and $chartincludes as two dimensional arrays.
     * 
     * @param int $length
     * @return void
     */
    public function __construct($length)
    {
	// Create chart as list of empty lists, length = no of tokens
	for ($i = 0; $i <= $length; $i++) {
	    $this->chart[$i] = array();
	    $this->includes[$i] = array();
            $this->chartIncludes[$i] = array();
	}
    }

    /** Sets the chart at position $index to an element or a list of elements
     * 
     * @param int $index
     * @param \PHPSimpleLexYacc\Parser\ParserState|array $elt  Either a single ParserState or a list of ParserStates
     * @return void
     * @see ParserChart::add()
     */
    public function set($index, $elt)
    {
	assert(is_int($index));

	if (is_array($elt)) {
	    // it should be possible to pass an array of elements
	    $this->chart[$index] = array();
	    $this->includes[$index] = array();
	    foreach ($elt as $state) {
		assert($state instanceof ParserState);
		$this->add($index, $state);
	    }
	    return;
	}

	assert($elt instanceof ParserState);
	$this->chart[$index] = array();
	$this->includes[$index] = array();

	return $this->add($index, $elt);
    }

    /** Gets a particular chart or the whole table
     * 
     * @param int $index  If -1, return the whole chart table
     * @return array  the chart at pos $index or the whole chart table
     * @see ParserChart::chart
     */
    public function get($index) 
    {
	// if $index == -1, return whole chart
	if ($index == -1) {
	    return $this->chart;
	}
	// check if chart[index] exists, otherwise return false
	if (!array_key_exists($index, $this->chart)) {
	    return false;
	}
	return $this->chart[$index];

    }

    /** Returns the last parser chart
     * 
     * @return array|null  null if none
     * @see ParserChart::chart, ParserChart::get()
     */
    public function last()
    {
	assert(is_array($this->chart));
	$length = count($this->chart);
	if ($length < 3) {
	    return null;
	}
	return $this->chart[$length-2];
    }

    /** Adds a parser state to the chart
     * 
     * Also takes care of the reduction table.  If the element already exists, 
     * don't add it.
     * 
     * @param int $index
     * @param \PHPSimpleLexYacc\Parser\ParserState $elt
     * @return boolean  true if state is added, false otherwise.
     * @see ParserChart::chart, ParserChart::chartIncludes, ParserChart::includes, ParserChart::addToReductionTable, ParserState::addPredecessor()
     */
    public function add($index, $elt) 
    {
	assert(is_int($index));
	assert($elt instanceof ParserState);

	$str_rep = $elt->__toString() . $elt->getHistory();

	// Just for safety, should never happen
	if (!isset($this->chart[$index])) {
	    $this->chart[$index] = array();
	    $this->includes[$index] = array();
            $this->chartIncludes[$index] = array();
	}
	// check if element already exists.  If not, add to chart
	if (!isset($this->includes[$index][$str_rep])) {
	    $chartkey = array_push($this->chart[$index], $elt) - 1;
            $this->chartIncludes[$index][$str_rep] = $chartkey; 
	    $this->includes[$index][$str_rep] = true;
            $this->addToReductionTable($index, $elt);
	    return true;
	}
        // If the element already exists, we have to add this element and its ancestors as predecessors, though
        $this->chart[$index][$this->chartIncludes[$index][$str_rep]]->addPredecessor($elt);
	return false;
    }
    
    /** Returns the reductions for a symbol at a chart number
     * 
     * @param int $index      the chart numbber
     * @param string $symbol  the symbol
     * @return array          list of ParserStates
     * @see ParserChart::reductionTable, ParserChart::addToReductionTable()
     */
    public function getReductions($index, $symbol)
    {
        if (isset($this->reductionTable[$index]) and isset($this->reductionTable[$index][$symbol])) {
            return $this->reductionTable[$index][$symbol];
        } else {
            return array();
        }
    }
    
    /** Adds a parser state to the reduction table
     * 
     * Finds out the current processed symbol, and adds it to the reduction
     * table.
     * 
     * @param int $index
     * @param ParserState $state
     * @return void
     * @see ParserChart::getReductions(), ParserChart::reductionTable
     */
    private function addToReductionTable($index, ParserState $state)
    {
        $cd = $state->getCd();
        if (count($cd) === 0) {
            return;
        }
        $symbolname = $cd[0]->getType();
        if (!isset($this->reductionTable[$index])) {
            $this->reductionTable[$index] = array();
        }
        if (!isset($this->reductionTable[$index][$symbolname])) {
            $this->reductionTable[$index][$symbolname] = array($state);
        } else {
            $this->reductionTable[$index][$symbolname][] = $state;
        }
        end($this->reductionTable[$index][$symbolname]);
        $state->setReductionTableKey($symbolname, key($this->reductionTable[$index][$symbolname]));
    }

    /** Makes a garbage collection
     * 
     * If a chart is in gcTable, make a garbage collection on it.  The latest
     * chart is added to gcTable, so on the next iteration garbage collection
     * will be done on it.  If a chart in gcTable has very few (<5) entries,
     * it is removed from gcTable.
     * 
     * @param int $index
     * @return void
     * @see ParserChart::gcTable, ParserState::aliveInChart
     */
    public function garbageCollection($index)
    {
        $keys = array_keys($this->gcTable);
        
        for ($i=0; $i < $index; $i++) {
            if (!isset($this->gcTable[$i])) {
                // nothing to do in this state
                continue;
            }
            if (count($this->chart[$i] < 5)) { // very few elements
                // on next iteration, don't gc this chart anymore
                unset($this->gcTable[$i]);
            }
            $newChart = array();
            foreach ($this->chart[$i] as $state) {
                if ($state->aliveInChart >= $index) {
                    $newChart[] = $state;
                } else {
                    // removing from reduction table
                    $rt = $state->reductionTableKey;
                    unset($this->reductionTable[$i][$rt[0]][$rt[1]]);
                }
            }
            $this->chart[$i] = $newChart;
        }
        $this->gcTable[$index] = true;
    }
}