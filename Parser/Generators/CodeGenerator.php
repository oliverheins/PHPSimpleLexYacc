<?php

abstract class CodeGenerator
{
    protected $name;
    protected $reflection;
    protected $docstring;
    protected $abstract;
    protected $final;
    protected $code;

    abstract public function __construct(array $parameters = array());

    public function getName()
    {
	return $this->name;
    }

    public function setName($name)
    {
	assert(is_string($name));
	$this->name = $name;
    }

    public function getReflection()
    {
	return $this->reflection;
    }

    public function setReflection(ReflectionFunctionAbstract $reflection)
    {
	$this->reflection = $reflection;
    }

    public function getDocstring()
    {
	return $this->docstring;
    }

    public function setDocstring($docstring)
    {
	if ($docstring === false) {
	    // ReflectionFunctionAbstract::getDocComment returns false
	    // if there's no docstring
	    $docstring = '';
	}
	assert(is_string($docstring));
	$this->docstring = $docstring;
    }

    public function isAbstract()
    {
	return $this->abstract;
    }

    public function setAbstract($abstract)
    {
	$this->abstract = (bool) $abstract;
	$this->final = ! $abstract;
    }

    public function isFinal()
    {
	return $this->final;
    }

    public function setFinal($final)
    {
	$this->final = (bool) $final;
	$this->abstract = ! $final;
    }

    public function getCode()
    {
	return $this->code || false;
    }

    public function setCode($code)
    {
	if (! is_string($code)) {
	    $code = false;
	}
	$this->code = $code;
    }

}