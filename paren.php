<?php

include_once("Parser/LexerBuilder.php");
include_once("Parser/AbstractParser.php");
include_once("Parser/ParserRule.php");
include_once("Parser/ParserToken.php");

class ParenRules extends LexerBuilder
{
    function __construct()
    {
	$this->addTokens(['INPUT']);
	$this->setIgnoreTokens(" \n");
    }

    function t_INPUT($token)
    {
	'/./';
	return $token;
    }

}

$r = new ParenRules();
$lexer = $r->getLexer('ParenLexer');

$data = '( ((( ( ))))  )';
$lexer->setData($data);
$lexer->lex();

$position = 0;
while($token = $lexer->getToken($position)) {
    echo $token->getType() . ":" . $token->getValue() . ": pos " .$token->getPosition().", line ". $token->getLinenumber()."<br>\n";
    $position++;
}
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
