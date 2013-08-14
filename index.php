<?php

include_once("Parser/LexerBuilder.php");

class TokenRules extends LexerBuilder
{
    function __construct() {
	$this->addTokens(array('SPACE', 'EOL', 'EQUAL', 'CURLYBEG', 'CURLYEND', 
			       'BACKSLASH',
			       'BRACKETSBEG', 'BRACKETSEND', 'PARENSBEG', 'PARENSEND',
			       'BAR', 'DQUOT', 'SQUOT', 'ASTERISK', 'EOF', 'STRING',
	       		       'TABLEBEG', 'TABLEEND', 'NEWROW', 'EXCLAM'));
	$this->setStates(array('table' => 'inclusive'));
    }

    function t_SPACE($token) {
	'/ +/';
	$length = strlen($token->getValue());
	$token->setValue($length);
	return $token;
    }

    function t_EOL($token) {
	'/\v/';
	return $token;
    }

    function t_BACKSLASH($token) {
	'/\\\(.)/';
	$rule = $token->getRule();
	$value = $token->getValue();
	preg_match($rule, $value, $matches);
	$token->setValue($matches[1]);
	$token->setType('STRING');
	return $this->concatToLastToken($token);
    }

    function t_EQUAL($token) {
	'/=/';
	return $token;
    }

    function t_CURLYBEG($token) {
	'/\{\{/';
	return $token;
    }

    function t_CURLYEND($token) {
	'/\}\}/';
	return $token;
    }

    function t_BRACKETSBEG($token) {
	'/\[\[/';
	return $token;
    }

    function t_BRACKETSEND($token) {
	'/\]\]/';
	return $token;
    }

    function t_PARENSBEG($token) {
	'/\(\(/';
	return $token;
    }

    function t_PARENSEND($token) {
	'/\)\)/';
	return $token;
    }

    function t_TABLEBEG($token) {
	'/\{\|/';
	$this->setCurrentState('table');
	return $token;
    }

    function t_table_TABLEEND($token) {
	'/\|\}/';
	$this->setCurrentState('INITIAL');
	return $token;
    }

    function t_table_NEWROW($token) {
	'/\|-/';
	return $token;
    }

    function t_table_EXCLAM($token) {
	'/!/';
	return $token;
    }

    function t_BAR($token) {
	'/\|/';
	return $token;
    }

    function t_DQUOT($token) {
	'/"/';
	return $token;
    }

    function t_SQUOT($token) {
	"/'/";
	return $token;
    }

    function t_ASTERISK($token) {
	'/\*/';
	return $token;
    }

    function t_EOF($token) {
	'/\Z/';
	return $token;
    }

    function t_STRING($token) {
	'/./';
	return $this->concatToLastToken($token);
    }

    /** Concats the value of a token 
     *
     * to the latest in the tokenlist if $token is of the same type as
     * last token.
     *
     * @param Token $token
     * @return Token
     * @return null
     */
    protected function concatToLastToken($token) {
	$type = $token->getType();
	if ($tokenlistlength = count($this->tokenlist)) {
	    $lasttoken = $this->tokenlist[$tokenlistlength-1];
	    if ($lasttoken->getType() == $type) {
		$lasttoken->setValue($lasttoken->getValue() . $token->getValue());
		return null;
	    }
	}
	return $token;
    }

}

$r = new TokenRules();
$lexer = $r->getLexer("SimpleWikiLexer");
unset($r);

$data = file_get_contents('test.txt');
$lexer->setData($data);
$lexer->lex();

$position = 0;
while($token = $lexer->getToken($position)) {
    echo $token->getType() . ":" . $token->getValue() . ":" .$token->getPosition()."<br>\n";
    $position++;
}
