<?php
/** Helper module of PhpSimpleLexCC
 *
 * @package helper
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */

namespace PHPSimpleLexYacc\Parser\Helpers;

/** Helper class to generate the code for rules
 * 
 * Rules are simply stored in an array.  Main focus is generating the code
 * for the rules.
 */
class ParserRules
{
    /** The rule list
     *
     * @var array
     */
    private $rules;

    /** Constructor
     * 
     * Initializes $this->rules with an empty array.
     * 
     * @return void
     */
    public function __construct()
    {
	$this->rules = array();
    }

    /** Adds a rule to the rule list
     * 
     * @param string $lhs   The left hand side of the rule: a single symbol
     * @param array $rule   The right hand side of the rule: a list of symbols
     * @param string $function  The name of the function associated with the rule
     * @param int $associativity   It's associativity
     * @param int $precedence   It's precedence
     * @return void
     */
    public function addRule($lhs, $rule, $function = null, $associativity = 0, $precedence = 0)
    {
	$this->rules[] = array($lhs, $rule, $function, $associativity, $precedence);
    }

    /** Returns the list of rules
     * 
     * @return array  The list of rules
     */
    public function getRules()
    {
	return $this->rules;
    }

    /** Generates the code for the parser
     * 
     * @see ParserRules::rules
     * @return string
     */
    public function generateCode()
    {
	// Generate the grammar
	$code = '$this->setGrammar(array(';
	foreach ($this->rules as $rule) {
	    $lhs           = $rule[0];
	    $rhs           = $rule[1];
	    $function      = $rule[2];
	    $precedence    = $rule[3];
	    $associativity = $rule[4];
	    $code .= 'new ParserRule(new ParserToken(array("type" => "' . $lhs . '", '
		. '"reduction" => "' . $function . '")),' . "\n";
	    $code .= 'array(';
	    foreach ($rhs as $symbol) {
		$code .= 'new ParserToken(array("type" => "' . $symbol .'")),' . "\n";
	    }
	    $code .= '),' . "\n";
	    $code .= $precedence . ',' . "\n";
	    $code .= $associativity . ',' . "\n";
	    $code .= '$this),' . "\n";
	}
	$code .= '));' . "\n";

	return $code;
    }

}