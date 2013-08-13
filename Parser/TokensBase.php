<?php

abstract class TokensBase
{
    private $tokens = array();
    private $tokenlist = array();

    protected function setTokens(array $tokens)
    {
	$this->tokens = $tokens;
    }

    protected function getTokens()
    {
	return $this->tokens;
    }

    protected function setTokenlist(array $tokenlist)
    {
	$this->tokenlist = $tokenlist;
    }

    protected function getTokenlist() {
	return $this->tokenlist;
    }

}