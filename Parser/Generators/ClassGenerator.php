<?php
namespace PHPSimpleLexYacc\Parser\Generators;

require_once("MethodGenerator.php");
require_once("PropertyGenerator.php");

class ClassGenerator extends CodeGenerator
{
    protected $methods = array();
    protected $properties = array();
    protected $implements = false;
    protected $extension = false;

    public function __construct(array $parameters = array())
    {
	foreach ($parameters as $key => $value) {
	    switch ($key) {
	    case "name":
	    case "reflection":
	    case "docstring":
	    case "abstract":
	    case "final":
	    case "extension":
	    case "implementation":
		$f = 'set' . ucfirst($key);
		$this->$f($value);
		break;
	    default:
		throw new \Exception("Class has no property named " . $key);
	    }
	}
    }

    public function addMethod(MethodGenerator $method) 
    {
	$this->methods[] = $method;
    }

    public function addProperty(PropertyGenerator $property) 
    {
	$this->properties[] = $property;
    }

    public function getMethods()
    {
	return $this->methods;
    }

    public function getProperties()
    {
	return $this->properties;
    }

    public function isExtension()
    {
	return (bool) $this->extension;
    }

    public function setExtension($extension)
    {
	$this->extension = is_string($extension) ? $extension : false;
    }

    public function getExtension()
    {
	return $this->extension;
    }

    public function isImplementation()
    {
	return (bool) $this->implements;
    }

    public function setImplementation($implementation)
    {
	$this->implements = is_string($implementation) ? $implementation : false;
    }

    public function getInterface()
    {
	return $this->implements;
    }

    public function generateCode()
    {
	$code = '';
	$code .= $this->getDocstring() ? $this->getDocstring() . "\n" : '';

	if ($this->isAbstract()) {
	    $code .= 'abstract' . ' ';
	}
	$code .= 'class' . ' ' . $this->getName();
	if ($this->isExtension()) {
	    $code .= ' extends ' . $this->getExtension();
	}
	if ($this->isImplementation()) {
	    $code .= ' implements ' . $this->getInterface();
	}
	$code .= "\n" . '{' . "\n";

	foreach ($this->properties as $property) {
	    $code .= $property->generateCode();
	    $code .= "\n\n";
	}

	foreach ($this->methods as $method) {
	    $code .= $method->generateCode();
	    $code .= "\n\n";
	}

	$code .= '}' . "\n";

	$this->setCode($code);

	return $code;

    }

}