<?php

$a = array(true, false, null);
for ($i=0; $i<3; $i++) {
    echo "isset: ";
    echo isset($a[$i]) ? "true" : "false";
    echo "<br>";
    echo "ake: ";
    echo array_key_exists($i, $a) ? "true" : "false";
    echo "<br>";
}
exit();

class MyArray extends \ArrayObject
{
    public $container = array();

    public function getContainer()
    {
	return $this->container;
    }

    public function setContainer(array $container)
    {
	$this->container = $container;
    }

    public function __clone()
    {
	$this->container = $this->deepCopy($this->container);
    }

    private function deepCopy($object)
    {
	$type = gettype($object);
	switch ($type) {
	case 'boolean':
	case 'integer':
	case 'double':
	case 'float':
	case 'string':
	case 'NULL':
	    return $object;
	case 'array':
	    $new = array();
	    foreach ($object as $key => $value) {
		$new[$key] = $this->deepCopy($value);
	    }
	    return $new;
	case 'object':
	    return clone $object;
	case 'resource':
	    trigger_error('Cannot clone a resource', E_WARNING);
	    return $object;
	default:
	    throw new \Exception('Tried to copy an unknown type.'); 
	}
    }

}

$b = function() { echo "hallo"; };

echo gettype($b);

$a = new MyArray();

$a[] = 1;
$a[] = 2;

echo "<h3>a</h3>";

foreach ($a as $key => $value) {
    echo "$key => $value<br>";
}


$ab = [1, 2, 3, 4, ['a', 'b', new \StdClass], 5, 6];

$container = $a->getContainer();

$container[] = 3;
$container[] = 4;
$container[] = $ab;

$b = clone $a;

$container[2][4] = 'foo';

// $a->setContainer($container);

// unset($container);

echo "container:<br>";

foreach ($a->container as $key => $value) {
    echo "$key => $value<br>";
}

echo "<h3>b</h3>";

foreach ($b as $key => $value) {
    echo "$key => $value<br>";
}

echo "container:<br>";

foreach ($b->container as $key => $value) {
    echo "$key => $value<br>";
}

var_dump($b->container);
var_dump($a->container);



exit();

include_once("Parser/LexerBuilder.php");
include_once("Parser/AbstractParser.php");
include_once("Parser/ParserRule.php");
include_once("Parser/ParserToken.php");

//include_once("Parser/SimpleWikiLexer.php");

class TokenRules extends LexerBuilder
{
    function __construct() {
	$this->addTokens(array('SPACE', 'EOL', 'EQUAL', 'CURLYBEG', 'CURLYEND', 
			       'BACKSLASH',
			       'BRACKETSBEG', 'BRACKETSEND', 'PARENSBEG', 'PARENSEND',
			       'BAR', 'DQUOT', 'SQUOT', 'ASTERISK', 'EOF', 'STRING',
	       		       'TABLEBEG', 'TABLEEND', 'NEWROW', 'EXCLAM'));
	$this->setStates(array('table' => 'inclusive'));
       	// $this->setIgnoreTokens("\t");
    }

    function t_SPACE($token) {
	'/ +/';
	$length = strlen($token->getValue());
	$token->setValue($length);
	return $token;
    }

    function t_EOL($token) {
	'/\v/';
	$this->linenumber++;
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

    var $t_EQUAL = "/=/";

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

$a = array(false, null);
for ($i=0; $i<count($a); $i++) {
    echo "<br>isset: " . isset($a[$i]) ? "true" : "false";
    echo "<br>ake: " . array_key_exists($a[$i]) ? "true" : "false";
}
exit();

$r = new TokenRules();
$lexer = $r->getLexer("SimpleWikiLexer");
//unset($r);

//$lexer = new SimpleWikiLexer();

$data = file_get_contents('test.txt');
$lexer->setData($data);
$lexer->lex();

//$position = 0;
//while($token = $lexer->getToken($position)) {
//    echo $token->getType() . ":" . $token->getValue() . ": pos " .$token->getPosition().", line ". $token->getLinenumber()."<br>\n";
//    $position++;
//}

$tokens = $lexer->getTokens();

$grammar = [
	    new ParserRule(new ParserToken(["type" => "S"]),
			   [new ParserToken(["type" => "P"])]),
	    new ParserRule(new ParserToken(["type" => "P"]), 
			   [new ParserToken(["value" => "("]),
			    new ParserToken(["type" => "P"]), 
			    new ParserToken(["value" => ")"]) ]),
	    new ParserRule(new ParserToken(["type" => "P"]), [ ]),
	    ];
$parser = new AbstractParser($grammar);
$result=$parser->parse($tokens);
