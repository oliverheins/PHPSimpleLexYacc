<?php
namespace PHPSimpleLexYacc\Parser;

class ParserChart 
{
    private $chart = array();
    private $includes = array();
    private $chartIncludes = array();
    private $reductionTable = array();
    private $gcTable = array();

    public function __construct($length)
    {
	// Create chart as list of empty lists, length = no of tokens
	for ($i = 0; $i <= $length; $i++) {
	    $this->chart[$i] = array();
	    $this->includes[$i] = array();
            $this->chartIncludes[$i] = array();
	}
    }

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

    public function last()
    {
	assert(is_array($this->chart));
	$length = count($this->chart);
	if ($length < 3) {
	    return null;
	}
	return $this->chart[$length-2];
    }

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
        $this->chart[$index][$this->chartIncludes[$index][$str_rep]]->addPredecessor($elt);
	return false;
    }
    
    public function getReductions($index, $symbol)
    {
        if (isset($this->reductionTable[$index]) and isset($this->reductionTable[$index][$symbol])) {
            return $this->reductionTable[$index][$symbol];
        } else {
            return array();
        }
    }
    
    public function garbageCollection($index)
    {
//        $keys = array_keys($this->gcTable);
//        $max = count($this->gcTable);
        
        $newChart = array();
        for ($i=0, $count=count($this->chart); $i < $count; $i++) {
            $newChart[$i] = array();
            //$this->reductionTable[$i] = array();
            foreach ($this->chart[$i] as $state) {
                if ($state->getAliveInChart() >= $index) {
                    $newChart[$i][] = $state;
                    // building the reduction table
                    //$this->addToReductionTable($i, $state);
                } else {
                    // removing from reduction table
                    $rt = $state->getReductionTableKey();
                    unset($this->reductionTable[$i][$rt[0]][$rt[1]]);
                }
            }
        }
        $this->chart = $newChart;
    }
    
    private function addToReductionTable($index, $state)
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
}