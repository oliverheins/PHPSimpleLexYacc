<?php
/** Generator module of PhpSimpleLexYacc
 *
 * @package generator
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */

namespace PHPSimpleLexYacc\Parser\Generators;

require_once("MethodGenerator.php");
require_once("PropertyGenerator.php");

/** Class for generating classes :)
 */
class ClassGenerator extends CodeGenerator
{
    /** holds the class methods
     *
     * @var array
     */
    protected $methods = array();
    
    /** holds the class properties
     *
     * @var array
     */
    protected $properties = array();
    
    /** Does the class implement an interface?
     *
     * @var boolean
     */
    protected $implements = false;
    
    /** Does the class extend a parent class?
     *
     * @var boolean
     */
    protected $extension = false;

    /** Constructor
     * 
     * @param array $parameters A key=>value list of the parameters
     * @see ClassGenerator::setName(), ClassGenerator::setReflection(), ClassGenerator::setDocstring(), ClassGenerator::setAbstract(), ClassGenerator::setFinal(), ClassGenerator::setExtension(), ClassGenerator::setImplementation(), 
     * @return void
     * @throws \Exception
     */
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

    /** Adds a method
     * 
     * @param \PHPSimpleLexYacc\Parser\Generators\MethodGenerator $method
     * @return void
     * @see ClassGenerator::methods
     */
    public function addMethod(MethodGenerator $method) 
    {
	$this->methods[] = $method;
    }

    /** Adds a property
     * 
     * @param \PHPSimpleLexYacc\Parser\Generators\PropertyGenerator $property
     * @return void
     * @see ClassGenerator::properties
     */
    public function addProperty(PropertyGenerator $property) 
    {
	$this->properties[] = $property;
    }

    /** Returns the list of methods
     * 
     * @return array
     * @see ClassGenerator::methods
     */
    public function getMethods()
    {
	return $this->methods;
    }

    /** Returns the properties list
     * 
     * @return array
     * @see ClassGenerator::properties
     */
    public function getProperties()
    {
	return $this->properties;
    }

    /** Returns if the class extends a parent class
     * 
     * @return boolean
     * @see ClassGenerator::extension
     *      */
    public function isExtension()
    {
	return (bool) $this->extension;
    }

    /** Sets if the class extends a parent class
     * 
     * @param string|null $extension
     * @return void
     * @see ClassGenerator::extension
     */
    public function setExtension($extension)
    {
	$this->extension = is_string($extension) ? $extension : false;
    }

    /** Returns the name of the extended (parent) class
     * 
     * @return string|boolean
     * @see ClassGenerator::extension
     */
    public function getExtension()
    {
	return $this->extension;
    }

    /** Returns if the class implements an interface
     * 
     * @return boolean
     * @see ClassGenerator::implements
     */
    public function isImplementation()
    {
	return (bool) $this->implements;
    }

    /** Sets the name of the interface
     * 
     * @param string|null $implementation
     * @see ClassGenerator::implements
     * @return void
     */
    public function setImplementation($implementation)
    {
	$this->implements = is_string($implementation) ? $implementation : false;
    }

    /** Returns the name of the interface
     * 
     * @return string|boolean
     * @see ClassGenerator::implements
     */
    public function getInterface()
    {
	return $this->implements;
    }

    /** Generates the php code of a class
     * 
     * Generates, sets and returns the code.  Uses the generateCode() methods
     * of its properties and methods.
     * 
     * @see ClassGenerator::setCode(), ClassGenerator::getDocstring(), ClassGenerator::getName(), ClassGenerator::getExtension(), ClassGenerator::getInterface(), PropertyGenerator::generateCode(), MethodGenerator::generateCode()
     * @return string
     */
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