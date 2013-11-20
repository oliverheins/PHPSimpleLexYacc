<?php
/** Helper module of PhpSimpleLexCC
 *
 * @package helper
 * @author    Oliver Heins 
 * @copyright 2013 Oliver Heins <oheins@sopos.org>
 * @license GNU Affero General Public License; either version 3 of the license, or any later version. See <http://www.gnu.org/licenses/agpl-3.0.html>
 */

namespace PHPSimpleLexYacc\Parser\Helpers;

/** Indenter for PHP code
 * 
 * Simple, ad hoc coded parser for PHP that indents the code.  Recognizes 
 * strings, heredocs, nowdocs and commentaries.
 */
class SimpleIndenter
{
    /** indentation level
     * 
     * @const INDENTLEVEL indentation level
     */
    const INDENTLEVEL = 4;

    /** the data to parse
     *
     * @var string
     */
    private $data;
    
    /** stores the result of the parsing
     *
     * @var string
     */
    private $result;

    /** the current mode the parser is in
     *
     * @var string
     */
    private $mode;
    
    /** whether the next character should be ignored or not
     *
     * @var boolean
     */
    private $continue;
    
    /** the current level of indentation
     *
     * @var int
     * @see SimpleIndenter::prefetch
     */
    private $indent;
    
    /** the margin chars of the current string (" or ')
     *
     * @var string
     */
    private $stringtype;
    
    /** helper var to indicate whether a comment might end with the next char
     *
     * @var boolean
     */
    private $mightend;
    
    /** helper var 
     *
     * Used to get indentation right even with closing paren at start of line.
     * Usually 0 (no paren at sol) or 1.  indentation = indent - prefetch.
     * 
     * @see SimpleIndenter::indent
     * @var int
     */
    private $prefetch;
    
    /** helper var to skip multiple empty lines
     *
     * @var boolean
     */
    private $emptyline;
    
    /** helper var to identify heredocs/nowdocs
     *
     * heredocs/nowdocs start with <<<.  this var counts the number of 
     * subsequent '<'s.
     * 
     * @var int
     */
    private $heredoc = 0;
    
    /** the current heredoc/nowdoc identifier
     *
     * @var string
     */
    private $heredocident = '';
    
    /** helper to identify the end of current heredoc/nowdoc
     *
     * @var string
     */
    private $heredocend = '';
    
    /** Is the current read char the first in a line?
     *
     * @var boolean
     */
    private $start_of_line;

    /** Constructor
     * 
     * @param string $data
     */
    public function __construct($data)
    {
	assert(is_string($data));
	$this->data = $data;
	$this->result = array();
    }

    /** Returns the prettyprinted result
     * 
     * process() should be called before.  Otherwise, an empty string is 
     * returned.
     * 
     * @see SimpleIndenter::process()
     * @return string  The prettyprinted result
     */
    public function getResult()
    {
	return implode("\n", $this->result);
    }

    /** The actual parser
     * 
     * Implemented as an adhoc parser.  Calls the different mode methods and 
     * indents the current line.
     * 
     * @see SimpleIndenter::indentLine(), SimpleIndenter::eolcommentmode(), SimpleIndenter::heredocgetidentmode(), SimpleIndenter::heredocmode(), SimpleIndenter::multilinecommentmode(), SimpleIndenter::normalmode(), SimpleIndenter::possiblecommentmode(), SimpleIndenter::stringmode(), 
     * @throws \Exception
     * @return void
     */
    public function process()
    {
	assert(is_string($this->data));
	if (strlen($this->data) == 0) {
	    throw new \Exception('SimpleIndenter has to be initialized with some amount of data to indent.');
	}
	$lines = explode("\n", $this->data);
	$this->result = array();
	$this->mode = 'normal';
	$this->indent = 0;
	$this->mightend = false;
	foreach ($lines as $line) {
	    // if a line starts with a closing paren, it indentation
	    // should be -1
	    $this->prefetch = (strlen($line) > 0 && preg_match('/^[\}\)\]]/', $line)) ? 1 : 0;
	    // skip multiple empty lines
	    if (strlen($line) === 0 && $this->mode != 'string') {
		if ($this->emptyline === true) {
		    $addthisline = false;
		} else {
		    $this->emptyline = true;
		    $addthisline = true;
		}
	    } else {
		$this->emptyline = false;
		$addthisline = true;
	    }
	    if ($addthisline === true) {
		// indentation must not happen in strings
		if ($this->mode != 'string' and $this->mode != 'heredoc') {
		    $this->result[] = $this->indentLine() . $line;
		} else {
		    $this->result[] = $line;
		}
	    }
	    $this->continue = false;
	    // process line
	    $this->start_of_line = true;
	    while (strlen($line) > 0) {
		// get char and cut it off from line
		$char = substr($line, 0, 1);
		$line = substr($line, 1);

		// if last char was a backslash, ignore this one
		if ($this->continue === true) {
		    $this->continue = false;
		    continue;
		}
		switch ($this->mode) {
		case 'normal':
		    $this->normalmode($char);
		    break;
		case 'possiblecomment':
		    $this->possiblecommentmode($char);
		    break;
		case 'eolcomment':
		    $this->eolcommentmode($char);
		    break;
		case 'multilinecomment':
		    $this->multilinecommentmode($char);
		    break;
		case 'string':
		    $this->stringmode($char);
		    break;
		case 'heredocgetident':
		    $this->heredocgetidentmode($char);
		    break;
		case 'heredoc':
		    $this->heredocmode($char);
		}
		$this->start_of_line = false;
	    }
	    // EOL comments end here
	    if ($this->mode == 'eolcomment') {
		$this->mode = 'normal';
	    }
	    // heredoc may start here
	    if ($this->mode == 'heredocgetident') {
		$this->mode = 'heredoc';
		$this->heredocident .= ';';
	    }
	}
    }

    /** Heredoc mode parsing
     * 
     * @see SimpleIndenter::heredocend, SimpleIndenter::start_of_line, SimpleIndenter::heredocident, SimpleIndenter::mode
     * @param string $char
     * @return void
     */
    private function heredocmode($char) {
	// we're only interested if the identifier starts at beginning
	// of line
	if (strlen($this->heredocend) == 0 and $this->start_of_line != true) {
	    return;
	}
	// checks if the found char is part of the heredoc identifier
	$next = substr($this->heredocident, strlen($this->heredocend), 1);
	if ($char == $next) {
	    $this->heredocend .= $char;
	} else {
	    $this->heredocend = '';
	}
	if ($this->heredocend == $this->heredocident) {
	    // identifier found, heredoc ends here
	    $this->mode = 'normal';
	    $this->heredocident = '';
	    $this->heredocend = '';
	}
    }

    /** Gets the heredoc identifier
     * 
     * @see SimpleIndenter::heredocident
     * @param string $char
     * @return void
     */
    private function heredocgetidentmode($char) {
	if ($char != "'") {
	    // nowdoc identifiers are enclosed in single quotes, we
	    // don't care for simplicity
	    $this->heredocident .= $char;
	}
    }

    /** normal mode parsing
     * 
     * Parens are counted, scans for the beginning of other modes.
     * 
     * @see SimpleIndenter::mode, SimpleIndenter::continue, SimpleIndenter::indent, SimpleIndenter::stringtype, SimpleIndenter::heredoc
     * @param string $char
     * @return void
     */
    private function normalmode($char)
    {
	switch ($char) {
	case '\\':
	    $this->continue = true;
	    break;
	case '{':
	case '(':
	case '[':
	    $this->indent++;
	    break;
	case '}':
	case ')':
	case ']':
	    $this->indent--;
	    break;
	case '"':
	case "'":
	    $this->mode = "string";
   	    $this->stringtype = $char;
	    break;
	case '/':
	    $this->mode = "possiblecomment";
	    break;
	case '<':
	    if ($this->heredoc == 2) { 
		$this->mode = "heredocgetident";
	    } else {
		$this->heredoc++;
	    }
	    return;
	}
	$this->heredoc = 0;
    }

    /** Scans if we see a comment
     * 
     * Comments start with a slash, followed either by another slash 
     * (eolcomment) or by an asterisk (multilinecomment)
     * 
     * @see SimpleIndenter::mode, SimpleIndenter::normalmode()
     * @param string $char
     * @return void
     */
    private function possiblecommentmode($char)
    {
	switch ($char) {
	case '/':
	    $this->mode = "eolcomment";
	    break;
	case '*':
	    $this->mode = "multilinecomment";
	    break;
	default:
	    $this->mode = "normal";
	    $this->normalmode($char);
	}
    }

    /** Skips the rest of the line
     * 
     * @see SimpleIndenter::continue
     * @param string $char
     * @return void
     */
    private function eolcommentmode($char)
    {
	$this->continue = true;
    }

    /** Skips until the end of the comment
     * 
     * @see SimpleIndenter::mightend, SimpleIndenter::mode
     * @param string $char
     * @return void
     */
    private function multilinecommentmode($char)
    {
	if ($this->mightend === true and $char == '/') {
	    $this->mode = 'normal';
	    $this->mightend = false;
	    return;
	}

	if ($char == '*') {
	    $this->mightend = true;
	} else {
	    $this->mightend = false;
	}
    }

    /** skips until the end of the string
     * 
     * @see SimpleIndenter::continue, SimpleIndenter::stringtype, SimpleIndenter::mode
     * @param string $char
     * @return void
     */
    private function stringmode($char)
    {
	if ($char == '\\') {
	    $this->continue = true;
	    return;
	}
	if ($char == $this->stringtype) {
	    $this->stringtype = '';
	    $this->mode = 'normal';
	}
    }

    /** Returns an amount of spaces
     * 
     * indentation = (indent - prefetch) * INDENTLEVEL
     * 
     * @see SimpleIndenter::indent, SimpleIndenter::prefetch, SimpleIndenter::INDENTLEVEL
     * @return string
     */
    private function indentLine()
    {
	$string = '';
	$n = ($this->indent - $this->prefetch) * self::INDENTLEVEL;
	for ($i = 0; $i < $n; $i++) {
	    $string .= ' ';
	}
	return $string;
    }
}