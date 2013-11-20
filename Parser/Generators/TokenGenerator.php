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

/** Class to generate special methods called Tokens
 * 
 * Tokens extend normal methods by a regexp, classhierarchy 
 * information and the starting line in the original source
 */
class TokenGenerator extends MethodGenerator
{
    /** holds the regexp as string representation
     *
     * @var string
     */
    protected $regexp;

    /** the number in the class hierarchy
     *
     * @var int
     */
    protected $classhierarchy;

    /** the starting line of the method in the source
     *
     * @var int
     */
    protected $linenumber;

    /** Constructor
     * 
     * @param array $parameters A key=>value list of the parameters
     * @see TokenGenerator::setName(), TokenGenerator::setReflection(), TokenGenerator::setDocstring(), TokenGenerator::setAbstract(), TokenGenerator::setFinal(), TokenGenerator::setBody(), TokenGenerator::setSource(), TokenGenerator::setVisibility(), TokenGenerator::setPublic(), TokenGenerator::setPrivate(), TokenGenerator::setProtected(), TokenGenerator::setAnonymous(), TokenGenerator::setClasshierarchy(), TokenGenerator::setRegexp(), TokenGenerator::setLinenumber()
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
	    case "classhierarchy":
	    case "linenumber":
	    case "regexp":
		$f = 'set' . ucfirst($key);
		$this->$f($value);
		break;
	    default:
		throw new \Exception("Method has no property " . $key);
	    }
	}
    }

    /** Compares the source location of two Tokens
     * 
     * @param \PHPSimpleLexYacc\Parser\Generators\TokenGenerator $a
     * @param \PHPSimpleLexYacc\Parser\Generators\TokenGenerator $b
     * @return int -1: $a before $b, 0: equal, 1: $a after $b
     */
    public static function compare(TokenGenerator $a, TokenGenerator $b)
    {
	if ($a->classhierarchy == $b->classhierarchy) {
	    if ($a->linenumber == $b->linenumber) {
		return 0;
	    }
	    return $a->linenumber < $b->linenumber ? -1 : 1;
	}
	return $a->classhierarchy < $b->classhierarchy ? -1 : 1;
    }

    /** extracts the regexp
     * 
     * Sets the body and the extracted regexp
     * 
     * @see TokenGenerator::getBody(), TokenGenerator::setBody(), TokenGenerator::setRegexp()
     * @return string
     * @throws \Exception
     */
    public function extractRegexp()
    {
	$body = $this->getBody();
	// Extract the regexp
	$needle = '/^\h*((?:\'(?:[^\']|\\\')+\')|(?:"(?:[^"]|\\")+"))\h*;\s*/';
	$found = preg_match($needle, $body, $matches);
	if ($found) {
	    $line = $matches[0];
	    $regexp = $matches[1];
	    $body = str_replace($line, '', $body);
	} elseif ($found === false) {
	    throw new \Exception("Fatal regexp error");
	} else {
	    $regexp = '//';
	}
	$this->setBody($body);
	$this->setRegexp($regexp);
	return $regexp;
    }

    /** Sets the regexp
     * 
     * @see TokenGenerator::regexp
     * @return void
     * @param string $r
     */
    public function setRegexp($r)
    {
	assert(is_string($r));
	$this->regexp = $r;
    }

    /** Returns the regexp string
     * 
     * @see TokenGenerator::regexp
     * @return string
     */
    public function getRegexp()
    {
	$r = $this->regexp;
	assert(is_string($r));
	return $r;
    }

    /** Returns the class hierarchy
     * 
     * @return int 
     */
    public function getClasshierarchy() 
    {
	$n = $this->classhierarchy;
	assert(is_int($n));
	return $n;
    }

    /** Sets the class hierarchy
     * 
     * @see TokenGenerator::classhierarchy
     * @param int $n
     * @return void
     */
    public function setClasshierarchy($n)
    {
	assert(is_int($n));
	$this->classhierarchy = $n;
    }

    /** Returns the starting line in the source
     * 
     * @see TokenGenerator::linenumber
     * @return int
     */
    public function getLinenumber() 
    {
	$n = $this->linenumber;
	assert(is_int($n));
	return $n;
    }

    /** Sets the starting line in the source
     * 
     * @see TokenGenerator::linenumber
     * @param int $n
     * @return void
     */
    public function setLinenumber($n)
    {
	assert(is_int($n));
	$this->linenumber = $n;
    }

}
