<?php
/** Parser module of PhpSimpleLexCC
 *
 * @package Parser
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */

namespace PHPSimpleLexYacc\Parser;

include_once('ParserRule.php');

/**
 * The Grammar class
 * 
 * Extends from ArrayObject, so mainly behaves like an array, but caches the 
 * closures.
 */
class ParserGrammar extends \ArrayObject 
{
    /** the cache
     *
     * @var array
     * @see ParserGrammar::getClosurces()
     */
    private $cache = array();
    
    /** Constructor.
     * 
     * Asserts that the grammar is a list of ParserRules.  Then calls the parent
     * constructor.
     * 
     * @param array $grammar
     * @return void
     */
    public function __construct(array $grammar)
    {
        foreach ($grammar as $rule) {
            assert($rule instanceof ParserRule);
        }
        parent::__construct($grammar);
    }
    
    /** Returns the closures for a given symbol.
     * 
     * Once computed, the closures are cached.
     * 
     * @param ParserToken $symbol
     * @return array   list of ParserRules
     * @see ParserGrammar::cache
     */
    public function getClosures($symbol)
    {
        $type = $symbol->getType();
        if (! isset($this->cache[$type])) {
            $this->cache[$type] = array_filter((array) $this, function($rule) use ($symbol) { 
                return $rule->getSymbol()->equal($symbol); 
                });
        }
        return $this->cache[$type];
    }
}
