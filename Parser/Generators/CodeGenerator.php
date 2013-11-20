<?php
/** Generator module of PhpSimpleLexYacc
 *
 * @package generator
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */

namespace PHPSimpleLexYacc\Parser\Generators;

/** Base class of all code generators
 * 
 * Abstract class containing all methods and properties the different code 
 * generator share.
 * 
 * @abstract
 */
abstract class CodeGenerator
{
    /** The name of the member
     *
     * @var string
     */
    protected $name;
    
    /** The reflection object of the member
     *
     * @var \Reflector
     */
    protected $reflection;
    
    /** The docstring of the member
     *
     * @var string
     */
    protected $docstring;
    
    /** Is the member abstract?
     *
     * @var boolean
     */
    protected $abstract;
    
    /** Is the member final?
     *
     * @var boolean
     */
    protected $final;
    
    /** The source code of the member
     *
     * @var string
     */
    protected $code;

    /** Constructor
     * 
     * @param array $parameters A key=>value list of the parameters
     * @abstract
     * @return void
     */
    abstract public function __construct(array $parameters = array());

    /** Returns the name of the member
     * 
     * @return string  The name of the member
     */
    public function getName()
    {
	return $this->name;
    }

    /** Sets the name of the member
     * 
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
	assert(is_string($name));
	$this->name = $name;
    }

    /** Returns the reflector
     * 
     * @return \Reflector|null
     */
    public function getReflection()
    {
	return $this->reflection;
    }

    /** Sets the reflector
     * 
     * @param \Reflector $reflection
     * @return void
     */
    public function setReflection(\Reflector $reflection)
    {
	$this->reflection = $reflection;
    }

    /** Returns the docstring
     * 
     * @return string
     */
    public function getDocstring()
    {
	return $this->docstring;
    }

    /** Sets the docstring
     * 
     * @param string|boolean $docstring  should be a string or false (\ReflectionFunctionAbstract::getDocComment() returns false if there's no docstring
     * @return void
     */
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

    /** Is member abstract?
     * 
     * @return boolean
     */
    public function isAbstract()
    {
	return $this->abstract;
    }

    /** Sets if member is abstract
     * 
     * if member is abstract, final is set to false.
     * 
     * @param mixed $abstract
     * @return void
     */
    public function setAbstract($abstract)
    {
	$this->abstract = (bool) $abstract;
        if ($this->abstract) { $this->final = false; }
    }

    /** is member final?
     * 
     * @return boolean
     */
    public function isFinal()
    {
	return $this->final;
    }

    /** Sets if member is final
     * 
     * if member is final, abstract is set to false
     * 
     * @param mixed $final
     * @return void
     */
    public function setFinal($final)
    {
	$this->final = (bool) $final;
        if ($this->final) { $this->abstract = false; }
    }

    /** Returns the source code or false
     * 
     * @return string|boolean
     */
    public function getCode()
    {
	return $this->code || false;
    }

    /** Sets the source code of the member
     * 
     * @param string $code
     * @return void
     */
    public function setCode($code)
    {
	if (! is_string($code)) {
	    $code = false;
	}
	$this->code = $code;
    }

}