<?php

include_once("MemberGenerator.php");

class PropertyGenerator extends MemberGenerator
{
    protected $value = null;

    public function __construct(array $parameters = array()) {
	foreach ($parameters as $key => $value) {
	    switch ($key) {
	    case "name":
	    case "reflection":
	    case "docstring":
	    case "abstract":
	    case "static":
	    case "public":
	    case "protected":
	    case "private":
	    case "final":
	    case "value":
		$f = 'set' . ucfirst($key);
		$this->$f($value);
		break;
	    default:
		throw new Exception("Property has no property named " . $key);
	    }
	}
    }

    public function generateCode()
    {
	$code = '';
	if ($this->isFinal()) {
	    $code .= 'final' . ' ';
	}
	if ($this->isPublic()) {
	    $code .= 'public' . ' ';
	}
	if ($this->isPrivate()) {
	    $code .= 'private' . ' ';
	}
	if ($this->isProtected()) {
	    $code .= 'protected' . ' ';
	}
	if ($this->isStatic()) {
	    $code .= 'static' . ' ';
	}
	$code .= '$' . $this->getName();

	$value = $this->getValue();
	if (is_string($value)) {
	    $code .= ' = ' . $value;
	}

	$code .= ';' . "\n";

	$this->setCode($code);

	return $code;
    }

    public function setValue($value)
    {
	assert(is_string($value));
	$this->value = $value;
    }

    public function getValue()
    {
	assert(is_string($value));
	return $this->value;
    }
    
}