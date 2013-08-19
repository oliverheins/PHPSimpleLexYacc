# Simple PHP Parser Generator

Simple PHP Parser Generator is a minimal implemantation of the Lex/Yacc parser tooles for PHP greatly inspired by [PLY (Python Lex-Yacc)](http://www.dabeaz.com/ply/).

## Lexer

### Usage

#### Example

    /* SampleLexer.php */
    include_once(
    class TokenRules extends LexerBuilder
    {
        function __construct() {
        	$this->addTokens(array('SPACE', 'EOL', 'EQUAL', 'CURLYBEG', 'CURLYEND', 
	       		       'BACKSLASH', 'TABLEBEG', 'TABLEEND', 'NEWROW', 'EOF'));
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
        var $t_CURLYBEG = '/\{\{/';
        var $t_CURLYEND = '/\}\}/';

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

        var t_EOF($token) = '/\Z/';

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

    $data = file_get_contents('test.txt');
    $lexer->setData($data);
    $lexer->lex();

    // The actual lexing process.  However, this will be most likely 
    // done by the automatically generated parser.
    $position = 0;
    while($token = $lexer->getToken($position)) {
        echo $token->getType() . ":" . $token->getValue() . ": pos " 
            . $token->getPosition().", line ". $token->getLinenumber()."<br>\n";
        $position++;
    }
