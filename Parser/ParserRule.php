<?php
/** Parser module of PhpSimpleLexCC
 *
 * @package Parser
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */
namespace PHPSimpleLexYacc\Parser;

require_once("ParserState.php");

/** Class for the parser rules
 * 
 */
class ParserRule
{
    /** the symbol (lhs) of the rule
     *
     * @var ParserToken
     */
    private $symbol;
    
    /** the actual rule (rhs)
     *
     * @var array  list of ParserTokens
     */
    private $rule;
    
    /** the associated reduction method
     *
     * @var callable|string
     */
    private $reduction;
    
    /** the precedence value
     *
     * @var int
     */
    private $precedence;
    
    /** the associativity: left = 0, right = 1
     *
     * @var int
     */
    private $assoc; 
    
    /** the instance of the parser
     *
     * @var Abstract Parser
     */
    private $parser = null;
    
    /** the cache, used for __toString() and toString()
     *
     * @var string
     * @see ParserRule::__toString(), ParserRule::toString()
     */
    private $cache = array();
    
    /** Constructor
     * 
     * Constructs the rule and sets up the cache for __toString() and toString()
     * methods.
     * 
     * @param ParserToken $symbol     LHS
     * @param array $rule             RHS
     * @param int $prec               precedence
     * @param int $assoc              associativity: 0 = left, 1 = right
     * @param AbstractParser $parser  backlink to the parser
     * @see ParserRule::symbol, ParserRule::precedence, ParserRule::associativity, ParserRule::rule, ParserRule::reduction, ParserRule::parser, ParserRule::cache
     */
    public function __construct($symbol, array $rule, $prec = 0, $assoc = 0, $parser = null)
    {
	assert(is_int($prec));
	assert(is_int($assoc));
	$this->setSymbol($symbol);
	$this->setPrecedence($prec);
	$this->setAssociativity($assoc);
	$this->setRule($rule);
	$this->setReduction((is_callable($symbol->getReduction()) or is_string($symbol->getReduction())) ? $symbol->getReduction() : null );
	if($parser !== null) {
	    $this->setParser($parser);
	}
        // Filling the cache
        $rule = '';
	foreach ($this->getRule() as $sym) {
	    $rule .= $sym->__toString() . " ";
	}
        $this->cache['__toString'] = $this->getSymbol()->__toString() . ": " . $rule;
	$rule = '';
	foreach ($this->getRule() as $sym) {
	    if ($sym instanceof ParserToken) {
		$rule .= $sym->getType() . " ";
	    }
	}
        $this->cache['toString'] = $this->getSymbol()->getType() . ": " . $rule;
    }

    /** Setter function for the parser
     *      * 
     * @param \PHPSimpleLexYacc\Parser\AbstractParser $parser
     * @return void
     * @see ParserRule::parser, ParserRule::getParser()
     */
    protected function setParser(AbstractParser $parser)
    {
	$this->parser = $parser;
    }

    /** Getter function for the parser
     * 
     * @return \PHPSimpleLexYacc\Parser\AbstractParser|null
     * @see ParserRule::parser, ParserRule::setParser()
     */
    public function getParser()
    {
	$parser = $this->parser;
	assert($parser instanceof AbstractParser or $parser === null);
	return $parser;
    }

    /** Setter function for the precedence
     * 
     * @param int $prec
     * @return void
     * @see ParserRule::precedence, ParserRule::getPrecedence()
     */
    protected function setPrecedence($prec)
    {
	assert(is_int($prec));
	$this->precedence = $prec;
    }

    /** Getter function for the precedence
     * 
     * @return int
     * @see ParserRule::precedence, ParserRule::setPrecedence()
     */
    public function getPrecedence()
    {
	$prec = $this->precedence;
	assert(is_int($prec));
	return $prec;
    }

    /** Setter function for the associativity
     * 
     * @param int $assoc
     * @return void
     * @see ParserRule::assoc, ParserRule::getAssociativity()
     */
    protected function setAssociativity($assoc = 0)
    {
	assert($assoc === 0 or $assoc === 1);
	$this->assoc = $assoc;
    }

    /** Getter function for the associativity
     * 
     * @return int
     * @see ParserRule::assoc, ParserRule::getAssociativity()
     */
    public function getAssociativity()
    {
	$assoc = $this->assoc;
	assert($assoc === 0 or $assoc === 1);
	return $assoc === 0 ? 'left' : 'right';
    }

    /** Setter function for the symbol
     * 
     * @param \PHPSimpleLexYacc\Parser\ParserToken $symbol
     * @return void
     * @see ParserRule::symbol, ParserRule::getSymbol()
     */
    public function setSymbol(ParserToken $symbol)
    {
	$this->symbol = $symbol;
    }

    /** Getter function for the symbol
     * 
     * @return \PHPSimpleLexYacc\Parser\ParserToken
     * @see ParserRule::symbol, ParserRule::setSymbol()
     */
    public function getSymbol()
    {
	$symbol = $this->symbol;
	assert($symbol instanceof ParserToken);
	return $symbol;
    }

    /** Setter function for the rule
     * 
     * @param array $rule
     * @return void
     * @see ParserRule::rule, ParserRule::getRule(), \PHPSimpleLexYacc\Parser\ParserToken
     */
    public function setRule(array $rule)
    {
	foreach ($rule as $symbol) {
	    assert($symbol instanceof ParserToken);
	}
	$this->rule = $rule;
    }

    /** Getter function for the rule
     * 
     * @return array
     * @see ParserRule::rule, ParserRule::setRule()
     */
    public function getRule()
    {
	$rule = $this->rule;
	assert(is_array($rule));
	return $rule;
    }

    /** Setter function for the reduction method
     * 
     * @param callable|string $reduction
     * @return void
     * @see ParserRule::reduction
     */
    private function setReduction($reduction)
    {
	assert(is_callable($reduction) or is_string($reduction) or $reduction === null);
	$this->reduction = $reduction;
    }

    /** Magic method __toString()
     * 
     * @return string
     * @see ParserRule::cache, ParserRule::toString(), ParserRule::__construct()
     */
    public function __toString()
    {
        return $this->cache['__toString'];
    }

    /** Somewhat simpler string reprecesentation as __toString()
     * 
     * @return string
     * @see ParserRule::cache, ParserRule::__toString(), ParserRule::__construct()
     */
    public function toString()
    {
        return $this->cache['toString'];
    }

}