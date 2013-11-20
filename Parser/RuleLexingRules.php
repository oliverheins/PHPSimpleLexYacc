<?php
/** Builder module of PhpSimpleLexCC
 *
 * @package   Builder
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license   GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */

namespace PHPSimpleLexYacc\Parser;

require_once("LexerBuilder.php");

/** The rule definition for the parser lexer
 */
class RuleLexingRules extends LexerBuilder
{
    /** Constructor
     *
     * Only knows the tokens SYMBOL, COLON, CHAR and BAR.  Newlines
     * and spaces are ignored.
     *
     * @return void
     */
    function __construct()
    {
	$this->addTokens(array('SYMBOL', 'COLON', 'CHAR', 'BAR'));
	$this->setIgnoreTokens(" \n");
    }

    /** regexp for strings
     *
     * @var string
     */
    var $t_CHAR   = '/\'[^\']+\'|"[^"]+"/';
    
    /** regexp for bar (|)
     *
     * @var string
     */
    var $t_BAR    = '/\|/';
    
    /** regexp for colen (:)
     *
     * @var string
     */
    var $t_COLON  = '/:/';
    
    /** regexp for symbols
     *
     * @var string
     */
    var $t_SYMBOL = '/[a-zA-Z0-9]+/';

}