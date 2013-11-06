<?php
namespace PHPSimpleLexYacc\Parser\Helpers;

class SimpleIndenter
{
    const INDENTLEVEL = 4;

    private $data;
    private $result;
    private $mode;
    private $continue;
    private $indent;
    private $stringtype;
    private $mightend;
    private $prefetch;
    private $emptyline;
    private $heredoc = 0;
    private $heredocident = '';
    private $heredocend = '';
    private $start_of_line;

    public function __construct($data)
    {
	assert(is_string($data));
	$this->data = $data;
	$this->result = array();
    }

    public function getResult()
    {
	return implode("\n", $this->result);
    }

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

    private function heredocgetidentmode($char) {
	if ($char != "'") {
	    // nowdoc identifiers are enclosed in single quotes, we
	    // don't care for simplicity
	    $this->heredocident .= $char;
	}
    }

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

    private function eolcommentmode($char)
    {
	$this->continue = true;
    }

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