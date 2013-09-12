<?php

class ParserChart 
{
    private $chart = array();
    private $includes = array();

    public function __construct($length)
    {
	// Create chart as list of empty lists, length = no of tokens
	for ($i = 0; $i <= $length; $i++) {
	    $this->chart[$i] = array();
	    $this->includes[$i] = array();
	}
    }

    public function set($index, $elt)
    {
	assert(is_int($index));
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
    public function add($index, $elt) 
    {
	assert(is_int($index));
	assert($elt instanceof ParserState);

	$str_rep = $elt->__toString();

	// Just for safety, should never happen
	if (!array_key_exists($index, $this->chart)) {
	    $this->chart[$index] = array();
	    $this->includes[$index] = array();
	}
	// check if element already exists.  If not, add to chart
	if (!array_key_exists($str_rep, $this->includes[$index])) {
	    array_unshift($this->chart[$index], $elt);
	    $this->includes[$index][$str_rep] = true;
	    return True;
	}
	return False;
    }

}