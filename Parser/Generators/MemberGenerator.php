<?php
/** Generator module of PhpSimpleLexYacc
 *
 * @package generator
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */

namespace PHPSimpleLexYacc\Parser\Generators;

require_once("CodeGenerator.php");

/** Abstract class for generating class members
 * 
 * Class members are its properties and its methods.
 * 
 * @abstract
 */
abstract class MemberGenerator extends CodeGenerator
{
    /** If the member is private
     *
     * @var boolean
     */
    protected $private = false;
    
    /** If the member is protected
     *
     * @var boolean
     */
    protected $protected = false;
    
    /** If the member is public
     *
     * @var public
     */
    protected $public = true;
    
    /** Holds the string representation of the visibility
     *
     * @var string
     * @see MemberGenerator::private, MemberGenerator::protected, MemberGenerator::public
     */
    protected $visibility = "public";
    
    /** If the member is static
     *
     * @var boolean
     */
    protected $static = false;

    /** Returns the visibility (string rep)
     * 
     * @return string
     * @see MemberGenerator::visibility 
     */
    public function getVisibility()
    {
	return $this->visibility;
    }

    /** Sets the visibility from a string
     * 
     * @see MemberGenerator::setProtected(), MemberGenerator::setPrivate(), MemberGenerator::setPublic(), MemberGenerator::setAbstract(), MemberGenerator::setFinal(), MemberGenerator::setStatic(), MemberGenerator::visibility 
     * @param string $visibility
     * @throws \Exception
     * @return void
     */
    public function setVisibility($visibility)
    {
	$keywords = explode(' ', $visibility);
	foreach ($keywords as $keyword) {
	    assert(is_string($keyword));
	    switch ($keyword) {
	    case ('protected'):
	    case ('private'):
	    case ('public'):
	    case ('abstract'):
	    case ('final'):
	    case ('static'):
		$f = "set" . ucfirst($keyword);
		$this->$f(true);
		break;
	    case (''):
		break;
	    default:
		throw new \Exception("Keyword not allowed: " . $key);
	    }
	}
	$this->visibility = $visibility;
    }

    /** Returns if the member is private
     * 
     * @see MemberGenerator::private
     * @return boolean
     */
    public function isPrivate()
    {
	return $this->private;
    }

    /** Sets if the member is private
     * 
     * If so, MemberGenerator::protected and MemberGenerator::public are set
     * to false.
     * 
     * @param boolean $private
     * @return void
     * @see MemberGenerator::private
     */
    public function setPrivate($private)
    {
	$this->private = (bool) $private;
	if ($private) {
	    $this->public = false;
	    $this->protected = false;
	}

    }

    /** Returns if the member is public
     * 
     * @return boolean
     * @see MemberGenerator::public
     */
    public function isPublic()
    {
	return $this->public;
    }

    /** Sets if the member is public
     * 
     * If so, MemberGenerator::protected and MemberGenerator::private are set
     * to false.
     * 
     * @param boolean $public
     * @return void
     * @see MemberGenerator::private, MemberGenerator::protected, MemberGenerator::public
     */
    public function setPublic($public)
    {
	$this->public = (bool) $public;
	if ($public) {
	    $this->protected = false;
	    $this->private = false;
	}
    }

    /** Returns if the member is protected
     * 
     * @return boolean
     * @see MemberGenerator::protected
     */
    public function isProtected()
    {
	return $this->protected;
    }

    /** Sets if the member is protected
     * 
     * If so, MemberGenerator::public and MemberGenerator::private are set
     * to false.
     * 
     * @param boolean $protected
     * @return void
     * @see MemberGenerator::private, MemberGenerator::protected, MemberGenerator::public
     */
    public function setProtected($protected)
    {
	$this->protected = (bool) $protected;
	if ($protected) {
	    $this->public = false;
	    $this->private = false;
	}
    }

    /** Returns if the member is static
     * 
     * @return boolean
     * @see MemberGenerator::static
     */
    public function isStatic()
    {
	return $this->static;
    }

    /** Sets if the member is static
     * 
     * @param boolean $static
     * @return void
     * @see MemberGenerator::static
     */
    public function setStatic($static)
    {
	$this->static = (bool) $static;
    }
}