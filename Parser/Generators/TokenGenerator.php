<?php

include_once("MethodGenerator.php");

class TokenGenerator extends MethodGenerator
{
    protected $regexp;

    protected $filehierarchy;
    protected $linenumber;

    public function extractRegexp()
    {
	$body = $this->getBody();
	// Extract the regexp
	$needle = '/^\h*((?:\'[^\']+\')|(?:"[^"]+"))\h*;\s*/';
	$found = preg_match($needle, $body, $matches);
	if ($found) {
	    $line = $matches[0];
	    $regexp = $matches[1];
	    $body = str_replace($line, '', $body);
	} elseif ($found === false) {
	    throw new Exception("Fatal regexp error");
	} else {
	    $regexp = '//';
	}
	$this->setBody($body);
	$this->setRegexp($regexp);
	return $regexp;
    }

    public function setRegexp($r)
    {
	assert(is_string($r));
	$this->regexp = $r;
    }

    public function getRegexp()
    {
	$r = $this->regexp;
	assert(is_string($r));
	return $r;
    }

    public function getFilehierarchy() 
    {
	$n = $this->filehierarchy;
	assert(is_int($n));
	return $n;
    }

    public function setFilehierarchy($n)
    {
	assert(is_int($n));
	$this->filehierarchy = $n;

    }

    public function getLinenumber() 
    {
	$n = $this->linenumber;
	assert(is_int($n));
	return $n;
    }

    public function setLinenumber($n)
    {
	assert(is_int($n));
	$this->linenumber = $n;

    }

}
