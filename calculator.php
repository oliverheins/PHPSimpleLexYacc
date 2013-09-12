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

$data = "128 + 12;";
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
//    echo "<h5>p_S</h5>b\n";
//    var_dump($p);
//    echo "\n<hr>\n\n";
    return $p;
};

$p_plus = function(array $p) {
    $p[0] = $p[1] + $p[3];
//    echo "<h5>p_plus</h5>b\n";
//    var_dump($p);
//    echo "\n<hr>\n\n";
    return $p;
};

$p_num = function(array $p) {
    $p[0] = $p[1];
//    echo "<h5>p_num</h5>b\n";
//    var_dump($p);
//    echo "\n<hr>\n\n";
    return $p;
};

$grammar = [
	    new ParserRule(new ParserToken(["type" => "S", "reduction" => $p_S]),
			   [new ParserToken(["type" => "EXP"]),
			    new ParserToken(["type" => "SEMICOLON"])]),
	    new ParserRule(new ParserToken(["type" => "EXP", "reduction" => $p_plus]), 
			   [new ParserToken(["type" => "EXP"]),
			    new ParserToken(["type" => "PLUS"]), 
			    new ParserToken(["type" => "EXP"]) ]),
	    new ParserRule(new ParserToken(["type" => "EXP", "reduction" => $p_plus]), 
			   [new ParserToken(["type" => "EXP"]),
			    new ParserToken(["type" => "MINUS"]), 
			    new ParserToken(["type" => "EXP"]) ]),
	    new ParserRule(new ParserToken(["type" => "EXP", "reduction" => $p_num]), 
			   [new ParserToken(["type" => "NUM"])]),
	    ];
$parser = new AbstractParser($grammar);
$result=$parser->parse($tokens);
