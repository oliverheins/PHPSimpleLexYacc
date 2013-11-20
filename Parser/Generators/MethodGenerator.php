<?php
/** Generator module of PhpSimpleLexYacc
 *
 * @package generator
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */

namespace PHPSimpleLexYacc\Parser\Generators;

require_once("MemberGenerator.php");

/** Class to generate methods
 */
class MethodGenerator extends MemberGenerator
{
    /** the source body, possible stripped
     *
     * @var string
     */
    protected $body;
    
    /** the original source code of the method
     *
     * @var string
     */
    protected $source;
    
    /** list of parameters
     *
     * @var array
     */
    protected $parameters = array();
    
    /** is the method anonymous?
     *
     * @var boolean
     */
    protected $anonymous;

    /** Constructor
     * 
     * @param array $parameters A key=>value list of the parameters
     * @see MethodGenerator::setName(), MethodGenerator::setReflection(), MethodGenerator::setDocstring(), MethodGenerator::setAbstract(), MethodGenerator::setFinal(), MethodGenerator::setBody(), MethodGenerator::setSource(), MethodGenerator::setVisibility(), MethodGenerator::setPublic(), MethodGenerator::setPrivate(),MethodGenerator::setProtected(), MethodGenerator::setAnonymous(),    
     * @return void
     * @throws \Exception
     */
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

    /** Extracts the relevant portions of the source
     * 
     * @see MethodGenerator::setBody(), MethodGenerator::body, MethodGenerator::source, MethodGenerator::name 
     * @return void
     */
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

    /** Returns the body
     * 
     * @return string
     * @see MethodGenerator::body
     */
    public function getBody()
    {
	return $this->body;
    }

    /** Sets the body of the method
     * 
     * @param string $body
     * @return void
     * @see MethodGenerator::body
     */
    public function setBody($body)
    {
	assert(is_string($body));
	$this->body = $body;
    }

    /** Returns the source of the method
     * 
     * @return string
     * @see MethodGenerator::source
     */
    public function getSource()
    {
	return $this->source;
    }

    /** Sets the source of the method
     * 
     * @param string $source
     * @return void
     * @see MethodGenerator::source
     */
    public function setSource($source)
    {
	assert(is_string($source));
	$this->source = $source;
    }

    /** Returns the list of parameters
     * 
     * @see MethodGenerator::parameters
     * @return array
     */
    public function getParameters()
    {
	return $this->parameters;
    }

    /** Sets the list of parameters
     * 
     * @param array $parameters
     * @return void
     * @see MethodGenerator::parameters
     */
    public function setParameters(array $parameters)
    {
	$this->parameters = $parameters;
    }

    /** Sets if the method is anonymous
     * 
     * @return void
     * @see MethodGenerator::anonymous
     * @param bool $anonymous
     */
    public function setAnonymous($anonymous)
    {
	$this->anonymous = (bool) $anonymous;
    }

    /** Returns if the method is anonymous
     * 
     * @return boolean
     * @see MethodGenerator::anonymous
     */
    public function isAnonymous()
    {
	return $this->anonymous;
    }

    /** Generates an anonymous function
     * 
     * Sets MethodGenerator::code to the lambda function and 
     * returns the code.
     * 
     * @see MethodGenerator::code, MethodGenerator::getBodyCode(), MethodGenerator::setCode()
     * @return string
     */
    public function generateLambda()
    {
	$code = '';
	$code .= 'function' . ' ';
	$code .= $this->getBodyCode();

	$this->setCode($code);

	return $code;

    }

    /** Generates the code, sets and returns it
     * 
     * @see MethodGenerator::getDocstring(), MethodGenerator::isFinal(), MethodGenerator::isAbstract(), MethodGenerator::isPublic(), MethodGenerator::isPrivate(), MethodGenerator::isProtected(), MethodGenerator::isStatic(), MethodGenerator::getName(), MethodGenerator::getBodyCode(), MethodGenerator::setCode()
     * @return string
     */
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

    /** Generates the code of the method
     * 
     * @see MethodGenerator::Parameters, MethodGenerator::getBody(), \ReflectionParameter::getClass(), \ReflectionClass::getName(), \ReflectionParameter::isPassedByReference()
     * @return string
     */ 
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
