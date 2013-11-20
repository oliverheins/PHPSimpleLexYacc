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

/** Class to generate properties
 */
class PropertyGenerator extends MemberGenerator
{
    /** default value
     *
     * @var mixed
     */
    protected $value = null;

    /** Constructor
     * 
     * @param array $parameters A key=>value list of the parameters
     * @see PropertyGenerator::setName(), PropertyGenerator::setReflection(), PropertyGenerator::setDocstring(), PropertyGenerator::setStatic(), PropertyGenerator::setPublic(), PropertyGenerator::setProtected(), PropertyGenerator::setPrivate(), PropertyGenerator::setFinal(), PropertyGenerator::setValue() 
     * @return void
     * @throws \Exception
     */
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
		throw new \Exception("Property has no property named " . $key);
	    }
	}
    }

    /** Generates the code for the property
     * 
     * In addition, the code is set with setCode()
     * 
     * @see PropertyGenerator::setCode(), PropertyGenerator::isFinal(), PropertyGenerator::isPublic(), PropertyGenerator::isPrivate(), PropertyGenerator::isProtected(), PropertyGenerator::isStatic(), PropertyGenerator::getValue(), PropertyGenerator::genValue()
     * @return string
     */
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

    /** Generate the string representation of the properties value
     * 
     * @param mixed $value
     * @return string
     * @throws \Exception
     */
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
	    throw new \Exception(ucfirst($type) . "s are not (yet) implemented.  Don't use them now, but file a bug report if you really need them.");
	    break;
	case 'unknown type':
	    throw new \Exception($type . ' is not a valid type, check your source.');
	    break;
	default:
	    throw new \Exception('This should not happen, consider this a bug: type '. $type . ' is unknown, but should be known. :(');
	}
    }

    /** Sets the value of the property
     * 
     * @see PropertyGenerator::value
     * @return void
     * @param mixed $value
     */
    public function setValue($value)
    {
	$this->value = $value;
    }

    /** Returns the value of the property
     * 
     * @return mixed
     * @see PropertyGenerator::value
     */
    public function getValue()
    {
	return $this->value;
    }
    
}