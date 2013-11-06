<?php

require_once("MemberGenerator.php");

class PropertyGenerator extends MemberGenerator
{
    protected $value = null;

    public function __construct(array $parameters = array()) {
	foreach ($parameters as $key => $value) {
	    switch ($key) {
	    case "name":
	    case "reflection":
	    case "docstring":
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
	if ($value !== null)  {
	    $code .= ' = ' . $this->genValue($value);
	}

	$code .= ';' . "\n";

	$this->setCode($code);

	return $code;
    }

    private function genValue($value)
    {
	// FIXME: This method is an exact duplicate of
	// ParserBuilder::genValue($value)
	$type = gettype($value);
	switch ($type) {
	case 'array':
	    // look if the array is a 'flat' array, i.e. keys are from
	    // 0..len(array)-1.  This enables a more concise notation,
	    // but might be expensive.
	    //
	    // The idea is to create a flat array, and compare its
	    // keys with the keys of the original.  If there's no
	    // difference, the array is a flat one.
	    $flat = false;
	    $flatarray = array_values($value);
	    if (count(array_diff(array_keys($value), array_keys($flatarray))) == 0) {
		$flat = true;
	    }
	    $result = array();
	    foreach ($value as $key => $subval) {
		if ($flat == true) {
		    $result[] = $this->genValue($subval);
		} else {
		    $result[] = $this->genValue($key) . ' => ' . $this->genValue($subval);
		}
	    }
	    return 'array('. implode(', ', $result) . ')';
	case 'boolean':
	    return $value ? 'true' : 'false';
	case 'double':
	case 'float':
	case 'integer':
	    return $value;
	case 'string':
	    $result = '';
	    while (strlen($value) > 0) {
		$char = substr($value, 0, 1);
		$value = substr($value, 1);
		if ($char == "'") {
		    $char = "\\'";
		} elseif ($char == '\\') {
		    $char = '\\\\';
		}
		$result .= $char;
	    }
	    return "'" . $result . "'";
	case 'object':
	case 'resource':
	    throw new Exception(ucfirst($type) . "s are not (yet) implemented.  Don't use them now, but file a bug report if you really need them.");
	    break;
	case 'unknown type':
	    throw new Exception($type . ' is not a valid type, check your source.');
	    break;
	default:
	    throw new Exception('This should not happen, consider this a bug: type '. $type . ' is unknown, but should be known. :(');
	}
    }

    public function setValue($value)
    {
	$this->value = $value;
    }

    public function getValue()
    {
	return $this->value;
    }
    
}