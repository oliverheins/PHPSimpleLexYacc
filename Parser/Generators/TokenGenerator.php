<?php

include_once("MethodGenerator.php");

class TokenGenerator extends MethodGenerator
{
    protected $regexp;

    protected $classhierarchy;
    protected $linenumber;

    public function __construct(array $parameters = array())
    {
	foreach ($parameters as $key => $value) {
	    switch ($key) {
	    case "name":
	    case "body":
	    case "source":
	    case "parameters":
	    case "reflection":
	    case "docstring":
	    case "visibility":
	    case "public":
	    case "private":
	    case "protected":
	    case "static":
	    case "abstract":
	    case "final":
	    case "anonymous":
	    case "classhierarchy":
	    case "linenumber":
	    case "regexp":
		$f = 'set' . ucfirst($key);
		$this->$f($value);
		break;
	    default:
		throw new Exception("Method has no property " . $key);
	    }
	}
    }

    public static function compare(TokenGenerator $a, TokenGenerator $b)
    {
	if ($a->classhierarchy == $b->classhierarchy) {
	    if ($a->linenumber == $b->linenumber) {
		return 0;
	    }
	    return $a->linenumber < $b->linenumber ? -1 : 1;
	}
	return $a->classhierarchy < $b->classhierarchy ? -1 : 1;
    }

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

    public function getClasshierarchy() 
    {
	$n = $this->classhierarchy;
	assert(is_int($n));
	return $n;
    }

    public function setClasshierarchy($n)
    {
	assert(is_int($n));
	$this->classhierarchy = $n;

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
