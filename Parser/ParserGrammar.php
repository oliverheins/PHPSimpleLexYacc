<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace PHPSimpleLexYacc\Parser;

include_once('ParserRule.php');

//use PHPSimpleLexYacc\Parser\ParserRule;

/**
 * Description of ParserGrammar
 *
 * @author olli
 */
class ParserGrammar extends \ArrayObject 
{
    private $cache = array();
    
    public function __construct(array $grammar)
    {
        foreach ($grammar as $rule) {
            assert($rule instanceof ParserRule);
        }
        parent::__construct($grammar);
    }
    
    public function getClosures($symbol)
    {
        $type = $symbol->getType();
        if (! isset($this->cache[$type])) {
            $this->cache[$type] = array_filter((array) $this, function($rule) use ($symbol) { 
                return $rule->getSymbol()->equal($symbol); 
                });
        }
        return $this->cache[$type];
    }
}
