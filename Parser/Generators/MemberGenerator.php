<?php

include_once("CodeGenerator.php");

abstract class MemberGenerator extends CodeGenerator
{
    protected $private = false;
    protected $protected = false;
    protected $public = true;
    protected $visibility = "public";
    protected $static = false;

    public function getVisibility()
    {
	return $this->visibility;
    }

    public function setVisibility($visibility)
    {
	$keywords = explode(' ', $visibility);
	foreach ($keywords as $keyword) {
	    assert(is_string($keyword));
	    switch ($keyword) {
	    case ('protected'):
	    case ('private'):
	    case ('public'):
	    case ('abstract'):
	    case ('final'):
	    case ('static'):
		$f = "set" . ucfirst($keyword);
		$this->$f(true);
		break;
	    case (''):
		break;
	    default:
		throw new Exception("Keyword not allowed: " . $key);
	    }
	}
	$this->visibility = $visibility;
    }

    public function isPrivate()
    {
	return $this->private;
    }

    public function setPrivate($private)
    {
	$this->private = (bool) $private;
	if ($private) {
	    $this->public = false;
	    $this->protected = false;
	}

    }

    public function isPublic()
    {
	return $this->public;
    }

    public function setPublic($public)
    {
	$this->public = (bool) $public;
	if ($public) {
	    $this->protected = false;
	    $this->private = false;
	}
    }

    public function isProtected()
    {
	return $this->protected;
    }

    public function setProtected($protected)
    {
	$this->protected = (bool) $protected;
	if ($protected) {
	    $this->public = false;
	    $this->private = false;
	}
    }

    public function isStatic()
    {
	return $this->static;
    }

    public function setStatic($static)
    {
	$this->static = (bool) $static;
    }
}