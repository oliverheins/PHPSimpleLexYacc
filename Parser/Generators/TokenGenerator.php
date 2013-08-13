<?php

include_once("MethodGenerator.php");

class TokenGenerator extends MethodGenerator
{
    protected $regexp;

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

}
