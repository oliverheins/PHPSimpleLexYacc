<?php
/** Builder module of PhpSimpleLexCC
 *
 * @package Builder
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */
namespace PHPSimpleLexYacc\Parser;

require_once("Helpers/SimpleIndenter.php");
use PHPSimpleLexYacc\Parser\Helpers\SimpleIndenter;

/** AbstractBuilder class
 *
 * Abstract class used by LexerBuilder and ParserBuilder.
 */
abstract class AbstractBuilder
{
    /** List of properties
     *
     * Holds all the properties of the lexer/parser definition that
     * don't define any token/rule
     *
     * @var array
     */
    protected $properties = array();

    /** List of token/rule related methods
     *
     * Holds all the methods of the lexer/parser definition that are
     * immedialitely related to the token/rule definition.
     *
     * @var array 
     */
    protected $innerMethods = array();

    /** List of methods not immedialitely related to token/rule definition
     *
     * Holds all the methods of the lexer/parser definition that don't
     * define any token/rule.
     *
     * @var array
     */
    protected $extraMethods = array();

    /** Table of class hierarchy
     *
     * Maps a filename to the class hierarchy: 
     * child -> parent =~ 1 -> 2
     *
     * @var array
     * @see LexerBuilder::addClasshierarchy(), LexerBuilder::getClasshierarchy()
     */
    private $classhierarchy = array();

    /** Debug status
     *
     * 0: off
     * 1: on
     * 2: verbose
     * 
     * @var int
     */
    protected $debug = 0;
    
    /** Adds a class to the class hierarchy
     * 
     * An integer value correspending to the nesting level is added.
     *
     * @param string $classname  The name of the class
     * @param int    $n          The nesting level
     * @see LexerBuilder::$classhierarchy
     * @return void
     */
    protected function addClasshierarchy($classname, $n)
    {
	assert(is_string($classname));
	assert($classname != '');
	assert(is_int($n));
	assert($n >= 1);
	$this->classhierarchy[str_replace('\\', '', $classname)] = $n;
    }

    /** Returns the whole class hierarchy table
     *
     * @return array  the class hierarchy table
     * @see LexerBuilder::$classhierarchy
     */
    protected function getClasshierarchy()
    {
	$c = $this->classhierarchy;
	assert(is_array($c));
	return $c;
    }

    /** Returns the level of a class in the class hierarchy
     *
     * @param  string $classname  The name of the class
     * @return int|boolean  The level of the class in the class hierarchy, or false if the class is not in the hierarchy 
     */
    protected function getLevelForClass($classname)
    {
	assert(is_string($classname));
	$classname = str_replace('\\', '', $classname);
	if (isset($this->classhierarchy[$classname])) { 
	    return $this->classhierarchy[$classname];
	}
	return false;
    }

    /** Checks if a cached version of the class can be used
     *
     * Checks if the timestamp of the cache file is newer than any of
     * its class definition files.  In addition, if
     * AbstractBuilder::$debug is true, returns false.
     *
     * @param  string $filename
     * @return boolean true if cache is up-to-date, false otherwise 
     */
    protected function useCache($filename)
    {
	$usecache = false;
        $object = new \ReflectionObject($this);
        $classlevel = 1;
        $newestfiletime = 0;
        while (true) { // traverse all parents of the lexer
	    // definition and get the latest
	    // modification time
            $classfile = $object->getFileName();
            $classname = $object->getName(); // Build class hierarchy
            $this->addClasshierarchy($classname, $classlevel);
            $classlevel++;
            $filetime = @filemtime($classfile);
            $newestfiletime = $filetime > $newestfiletime ? $filetime : $newestfiletime;
            if ($ancestor = $object->getParentClass()) {
                $object = $ancestor;
            } else {
                break;
            }
        }
	// if debug is on, we create the file
	if (isset($this->debug)) { 
	    return false; 
	}
	// Use cache only if the cached file is newer than the
	// last modification
        if (@file_exists($filename) && $newestfiletime < @filemtime($filename)) {
            $usecache = true;
        }
	return $usecache;
    }
    
    /** Returns the generated Lexer/Parser
     *
     * Checks if the lexer/parser definition was changed recently. If
     * so, it generates a new one before returning the lexer/parser.
     * Also builds the class hierarchy.
     *
     * @access protected
     * @param  string $type       "Lexer" or "Parser"
     * @param  string $name       The name of the build (must be a valid classname)
     * @return AbstractLexer|AbstractParser The generated Lexer resp. Parser
     * @see                       AbstractBuilder::createBuild()
     */
    protected function getBuild($type, $name)
    {
	assert(is_string($type) && $type != '' && is_string($name) && $name != '');
	if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*\Z/', $name)) {
	    throw new \Exception($type . " must be a valid PHP classname.");
	}
	$dir = 'Parser/';
	$filename = $dir . $name . '.php';

	if ($this->useCache($filename) === false) {
	    // Build a new parser
	    $parser = $this->createBuild($name);
	    $indenter = new SimpleIndenter($parser);
	    $indenter->process();
	    $parser = $indenter->getResult();
	    if (!file_put_contents($filename, $parser)) {
		throw new \Exception("Can't write " . $filename);
	    }
	}
	$name = 'PHPSimpleLexYacc\\Parser\\' . $name;
	require_once($filename);
	return new $name();
    }
    
    /** Abstract function for the concrete creator
     *
     * Must implement the concrete creator
     * 
     * @abstract
     * @param string $name Classname
     */
    abstract protected function createBuild($name);

}