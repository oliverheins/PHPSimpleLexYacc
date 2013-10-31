<?php

include_once("LexerBuilder.php");

class RuleLexingRules extends LexerBuilder
{
    function __construct()
    {
	$this->addTokens(array('SYMBOL', 'COLON', 'CHAR', 'BAR'));
	$this->setIgnoreTokens(" \n");
    }

    var $t_CHAR   = '/\'[^\']+\'|"[^"]+"/';
    var $t_BAR    = '/\|/';
    var $t_COLON  = '/:/';
    var $t_SYMBOL = '/[a-zA-Z0-9]+/';

}