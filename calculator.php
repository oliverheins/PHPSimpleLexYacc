<?php

include_once("Parser/LexerBuilder.php");
include_once("Parser/AbstractParser.php");
include_once("Parser/ParserRule.php");
include_once("Parser/ParserToken.php");

class CalculatorRules extends LexerBuilder
{
    function __construct()
    {
	$this->addTokens(['NUM', 'PLUS', 'MINUS', 'TIMES', 'EOD', 'DIVIDE', 'SEMICOLON']);
	$this->setIgnoreTokens(" ");
    }

    function t_NUM($token) {
	'/[0-9]+\.?[0-9]*/';
	return $token;
    }

    var $t_PLUS   = '/\+/';
    var $t_MINUS  = '/-/';
    var $t_TIMES  = '/\*/';
    var $t_DIVIDE = '/\//';
    var $t_SEMICOLON = '/;/';

    var $t_EOD = "/\Z/";

}

$r = new CalculatorRules();
$lexer = $r->getLexer('CalculatorLexer');

$data = "10 * 2 - 3 * 2 - 4 - 8/2;";
$lexer->setData($data);
$lexer->lex();

$position = 0;
while($token = $lexer->getToken($position)) {
    echo $token->getType() . ":" . $token->getValue() . ": pos " .$token->getPosition().", line ". $token->getLinenumber()."<br>\n";
    $position++;
}
echo "\n<hr>\n\n";
$tokens = $lexer->getTokens();

$p_S = function(array $p) {
    $p[0] = $p[1];
    return $p;
};

$p_plus = function(array $p) {
    $p[0] = $p[1] + $p[3];
    return $p;
};

$p_minus = function(array $p) {
    $p[0] = $p[1] - $p[3];
    return $p;
};

$p_times = function(array $p) {
    $p[0] = $p[1] * $p[3];
    return $p;
};

$p_divide = function(array $p) {
    $p[0] = $p[1] / $p[3];
    return $p;
};


$p_num = function(array $p) {
    $p[0] = $p[1];
    return $p;
};

$grammar = [
	    
	    new ParserRule(new ParserToken(["type" => "F", "reduction" => $p_S]),
	    		   [new ParserToken(["type" => "S"])]),
	    new ParserRule(new ParserToken(["type" => "S", "reduction" => $p_S]),
			   [new ParserToken(["type" => "EXP"]),
			    new ParserToken(["type" => "SEMICOLON"])]),
	    new ParserRule(new ParserToken(["type" => "EXP", "reduction" => $p_times]), 
			   [new ParserToken(["type" => "EXP"]),
			    new ParserToken(["type" => "TIMES"]), 
			    new ParserToken(["type" => "EXP"]) ],
			   1),
	    new ParserRule(new ParserToken(["type" => "EXP", "reduction" => $p_divide]), 
			   [new ParserToken(["type" => "EXP"]),
			    new ParserToken(["type" => "DIVIDE"]), 
			    new ParserToken(["type" => "EXP"]) ],
			   1),
	    new ParserRule(new ParserToken(["type" => "EXP", "reduction" => $p_plus]), 
			   [new ParserToken(["type" => "EXP"]),
			    new ParserToken(["type" => "PLUS"]), 
			    new ParserToken(["type" => "EXP"]) ],
			   2),
	    new ParserRule(new ParserToken(["type" => "EXP", "reduction" => $p_minus]), 
			   [new ParserToken(["type" => "EXP"]),
			    new ParserToken(["type" => "MINUS"]), 
			    new ParserToken(["type" => "EXP"]) ],
			   2),
	    new ParserRule(new ParserToken(["type" => "EXP", "reduction" => $p_num]), 
			   [new ParserToken(["type" => "NUM"])]),
	    ];

// Complex Point: StartRule, first symbol
$cp = array($grammar[1], 0); 

$parser = new AbstractParser($grammar, array($cp));
$result=$parser->parse($tokens);

echo "<hr>\n\n";
echo $data . "<br>\n\n";
$final = $parser->getFinalStates();
$i = 0;
foreach ($final as $state) {
    $i++;
    echo "<h5>State $i</h5>";
    echo $state. "<br>";
    echo $state->getHistory() . "<br>";
    echo "<hr>";
}
