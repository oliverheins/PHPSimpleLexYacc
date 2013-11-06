<?php
namespace PHPSimpleLexYacc\Parser\Generators;

require_once("MemberGenerator.php");

class MethodGenerator extends MemberGenerator
{
    protected $body;
    protected $source;
    protected $parameters = array();
    protected $anonymous;

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
		$f = 'set' . ucfirst($key);
		$this->$f($value);
		break;
	    default:
		throw new \Exception("Method has no property " . $key);
	    }
	}
    }

    public function extractBody() 
    {
	$source = $this->getSource();
	$name = $this->getName();
	assert(is_string($source) and is_string($name));
	// Strip the body of the function definition
	$needle = '/\h*((?:(?:(?:abstract)|(?:final))\h+)?(?:(?:(?:public)|(?:protected)|(?:private))\h+)?(?:static\h+)?)?\h*function\h+'.$name.'\h*\([^)]*\)\s*\{\s*(.+)/';
	preg_match($needle, $source, $matches);
	$this->setVisibility($matches[1]);
	$source = preg_replace($needle, '$2', $source);
	$needle = '/\s*\}\s*\Z/';
	$body = preg_replace($needle, '', $source);
	$this->setBody($body);
    }


    public function getBody()
    {
	return $this->body;
    }

    public function setBody($body)
    {
	assert(is_string($body));
	$this->body = $body;
    }

    public function getSource()
    {
	return $this->source;
    }

    public function setSource($source)
    {
	assert(is_string($source));
	$this->source = $source;
    }

    public function getParameters()
    {
	return $this->parameters;
    }

    public function setParameters(array $parameters)
    {
	$this->parameters = $parameters;
    }

    public function setAnonymous($anonymous)
    {
	$this->anonymous = (bool) $anonymous;
    }

    public function isAnonymous()
    {
	return $this->anonymous;
    }

    public function generateLambda()
    {
	$code = '';
	$code .= 'function' . ' ';
	$code .= $this->getBodyCode();

	$this->setCode($code);

	return $code;

    }

    public function generateCode()
    {
	$code = '';
	$code .= $this->getDocstring() ? $this->getDocstring() . "\n" : '';

	if ($this->isFinal()) {
	    $code .= 'final' . ' ';
	}
	if ($this->isAbstract()) {
	    $code .= 'abstract' . ' ';
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

	$code .= 'function' . ' ' . $this->getName();
	$code .= $this->getBodyCode();

	$this->setCode($code);

	return $code;
    }

    protected function getBodyCode()
    {
	$code = '(';

	$parray = array();
	foreach ($this->getParameters() as $p) {
	    $s = '';
	    if ($c = $p->getClass()) {
		$s .= $c->getName() . " ";
	    }
	    $s .= $p->isPassedByReference() ? '&' : '' . '$' . $p->getName();
	    //	    $s .= $p->isDefaultValueConstant() ? ' = ' . $p->getDefaultValueConstantName() : '';
	    $parray[] = $s;
	}
	$parameters = implode(', ', $parray);
	
	$code .= $parameters . ')' . " ";
	$code .= '{' . "\n";
	$code .= $this->getBody() . "\n";
	$code .= '}' . "\n";

	return $code;
    }

}
