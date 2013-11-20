<?php
/** Helper module of PhpSimpleLexCC
 *
 * @package helper
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */

namespace PHPSimpleLexYacc\Parser\Helpers;

/** ContainerArray class
 * 
 * Class that behaves like an array (it inherits from \ArrayObject), but also 
 * has a container.
 */
class ContainerArray extends \ArrayObject
{
    /** container array
     *
     * @var array
     */
    public $container = array();

    /** Returns the container
     * 
     * @return array the container
     */
    public function getContainer()
    {
	return $this->container;
    }

    /** Sets the container
     * 
     * @param array $container
     * @return void
     */
    public function setContainer(array $container)
    {
	$this->container = $container;
    }

    /** Magic clone method
     * 
     * calls deepCopy()
     * 
     * @return void
     * @see ContainerArray::deepCopy()
     */
    public function __clone()
    {
	$this->container = $this->deepCopy($this->container);
    }

    /** Makes a recursive copy of a variable
     * 
     * Works on booleans, integers, doubles, floats, strings, arrays and 
     * objects (which might need a __clone() method themselves).  Resources
     * can't be copied in a generic way, so just the reference to the resource 
     * is returned.
     * 
     * @param mixed $object
     * @return mixed
     * @throws \Exception
     */
    private function deepCopy($object)
    {
	$type = gettype($object);
	switch ($type) {
	case 'boolean':
	case 'integer':
	case 'double':
	case 'float':
	case 'string':
	case 'NULL':
	    return $object;
	case 'array':
	    $new = array();
	    foreach ($object as $key => $value) {
		$new[$key] = $this->deepCopy($value);
	    }
	    return $new;
	case 'object':
	    return clone $object;
	case 'resource':
	    trigger_error('Cannot clone a resource.  Maybe you should put the resource in a wrapper object.', E_WARNING);
	    return $object;
	default:
	    throw new \Exception('Tried to copy an unknown type.'); 
	}
    }

}
