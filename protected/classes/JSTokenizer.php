<?php 

class JSTokenizer
{
	private $cursor = 0;
	private $source;

	public $tokens = array();
	public $tokenIndex = 0;
	public $lookahead = 0;
	public $scanNewlines = false;
	public $scanOperand = true;

	public $filename;
	public $lineno;

	private $keywords = array(
		'break',
		'case', 'catch', 'const', 'continue',
		'debugger', 'default', 'delete', 'do',
		'else', 'enum',
		'false', 'finally', 'for', 'function',
		'if', 'in', 'instanceof',
		'new', 'null',
		'return',
		'switch',
		'this', 'throw', 'true', 'try', 'typeof',
		'var', 'void',
		'while', 'with'
	);

	private $opTypeNames = array(
		';'	=> 'SEMICOLON',
		','	=> 'COMMA',
		'?'	=> 'HOOK',
		':'	=> 'COLON',
		'||'	=> 'OR',
		'&&'	=> 'AND',
		'|'	=> 'BITWISE_OR',
		'^'	=> 'BITWISE_XOR',
		'&'	=> 'BITWISE_AND',
		'==='	=> 'STRICT_EQ',
		'=='	=> 'EQ',
		'='	=> 'ASSIGN',
		'!=='	=> 'STRICT_NE',
		'!='	=> 'NE',
		'<<'	=> 'LSH',
		'<='	=> 'LE',
		'<'	=> 'LT',
		'>>>'	=> 'URSH',
		'>>'	=> 'RSH',
		'>='	=> 'GE',
		'>'	=> 'GT',
		'++'	=> 'INCREMENT',
		'--'	=> 'DECREMENT',
		'+'	=> 'PLUS',
		'-'	=> 'MINUS',
		'*'	=> 'MUL',
		'/'	=> 'DIV',
		'%'	=> 'MOD',
		'!'	=> 'NOT',
		'~'	=> 'BITWISE_NOT',
		'.'	=> 'DOT',
		'['	=> 'LEFT_BRACKET',
		']'	=> 'RIGHT_BRACKET',
		'{'	=> 'LEFT_CURLY',
		'}'	=> 'RIGHT_CURLY',
		'('	=> 'LEFT_PAREN',
		')'	=> 'RIGHT_PAREN',
		'@*/'	=> 'CONDCOMMENT_END'
	);

	private $assignOps = array('|', '^', '&', '<<', '>>', '>>>', '+', '-', '*', '/', '%');
	private $opRegExp;

	public function __construct()
	{
		$this->opRegExp = '#^(' . implode('|', array_map('preg_quote', array_keys($this->opTypeNames))) . ')#';

		// this is quite a hidden yet convenient place to create the defines for operators and keywords
		foreach ($this->opTypeNames as $operand => $name)
			define('OP_' . $name, $operand);

		define('OP_UNARY_PLUS', 'U+');
		define('OP_UNARY_MINUS', 'U-');

		foreach ($this->keywords as $keyword)
			define('KEYWORD_' . strtoupper($keyword), $keyword);
	}

	public function init($source, $filename = '', $lineno = 1)
	{
		$this->source = $source;
		$this->filename = $filename ? $filename : '[inline]';
		$this->lineno = $lineno;

		$this->cursor = 0;
		$this->tokens = array();
		$this->tokenIndex = 0;
		$this->lookahead = 0;
		$this->scanNewlines = false;
		$this->scanOperand = true;
	}

	public function getInput($chunksize)
	{
		if ($chunksize)
			return substr($this->source, $this->cursor, $chunksize);

		return substr($this->source, $this->cursor);
	}

	public function isDone()
	{
		return $this->peek() == TOKEN_END;
	}

	public function match($tt)
	{
		return $this->get() == $tt || $this->unget();
	}

	public function mustMatch($tt)
	{
	        if (!$this->match($tt))
			throw $this->newSyntaxError('Unexpected token; token ' . $tt . ' expected');

		return $this->currentToken();
	}

	public function peek()
	{
		if ($this->lookahead)
		{
			$next = $this->tokens[($this->tokenIndex + $this->lookahead) & 3];
			if ($this->scanNewlines && $next->lineno != $this->lineno)
				$tt = TOKEN_NEWLINE;
			else
				$tt = $next->type;
		}
		else
		{
			$tt = $this->get();
			$this->unget();
		}

		return $tt;
	}

	public function peekOnSameLine()
	{
		$this->scanNewlines = true;
		$tt = $this->peek();
		$this->scanNewlines = false;

		return $tt;
	}

	public function currentToken()
	{
		if (!empty($this->tokens))
			return $this->tokens[$this->tokenIndex];
	}

	public function get($chunksize = 1000)
	{
		while($this->lookahead)
		{
			$this->lookahead--;
			$this->tokenIndex = ($this->tokenIndex + 1) & 3;
			$token = $this->tokens[$this->tokenIndex];
			if ($token->type != TOKEN_NEWLINE || $this->scanNewlines)
				return $token->type;
		}

		$conditional_comment = false;

		// strip whitespace and comments
		while(true)
		{
			$input = $this->getInput($chunksize);

			// whitespace handling; gobble up \r as well (effectively we don't have support for MAC newlines!)
			$re = $this->scanNewlines ? '/^[ \r\t]+/' : '/^\s+/';
			if (preg_match($re, $input, $match))
			{
				$spaces = $match[0];
				$spacelen = strlen($spaces);
				$this->cursor += $spacelen;
				if (!$this->scanNewlines)
					$this->lineno += substr_count($spaces, "\n");

				if ($spacelen == $chunksize)
					continue; // complete chunk contained whitespace

				$input = $this->getInput($chunksize);
				if ($input == '' || $input[0] != '/')
					break;
			}

			// Comments
			if (!preg_match('/^\/(?:\*(@(?:cc_on|if|elif|else|end))?.*?\*\/|\/[^\n]*)/s', $input, $match))
			{
				if (!$chunksize)
					break;

				// retry with a full chunk fetch; this also prevents breakage of long regular expressions (which will never match a comment)
				$chunksize = null;
				continue;
			}

			// check if this is a conditional (JScript) comment
			if (!empty($match[1]))
			{
				$match[0] = '/*' . $match[1];
				$conditional_comment = true;
				break;
			}
			else
			{
				$this->cursor += strlen($match[0]);
				$this->lineno += substr_count($match[0], "\n");
			}
		}

		if ($input == '')
		{
			$tt = TOKEN_END;
			$match = array('');
		}
		elseif ($conditional_comment)
		{
			$tt = TOKEN_CONDCOMMENT_START;
		}
		else
		{
			switch ($input[0])
			{
				case '0': case '1': case '2': case '3': case '4':
				case '5': case '6': case '7': case '8': case '9':
					if (preg_match('/^\d+\.\d*(?:[eE][-+]?\d+)?|^\d+(?:\.\d*)?[eE][-+]?\d+/', $input, $match))
					{
						$tt = TOKEN_NUMBER;
					}
					else if (preg_match('/^0[xX][\da-fA-F]+|^0[0-7]*|^\d+/', $input, $match))
					{
						// this should always match because of \d+
						$tt = TOKEN_NUMBER;
					}
				break;

				case '"':
				case "'":
					if (preg_match('/^"(?:\\\\(?:.|\r?\n)|[^\\\\"\r\n]+)*"|^\'(?:\\\\(?:.|\r?\n)|[^\\\\\'\r\n]+)*\'/', $input, $match))
					{
						$tt = TOKEN_STRING;
					}
					else
					{
						if ($chunksize)
							return $this->get(null); // retry with a full chunk fetch

						throw $this->newSyntaxError('Unterminated string literal');
					}
				break;

				case '/':
					if ($this->scanOperand && preg_match('/^\/((?:\\\\.|\[(?:\\\\.|[^\]])*\]|[^\/])+)\/([gimy]*)/', $input, $match))
					{
						$tt = TOKEN_REGEXP;
						break;
					}
				// FALL THROUGH

				case '|':
				case '^':
				case '&':
				case '<':
				case '>':
				case '+':
				case '-':
				case '*':
				case '%':
				case '=':
				case '!':
					// should always match
					preg_match($this->opRegExp, $input, $match);
					$op = $match[0];
					if (in_array($op, $this->assignOps) && $input[strlen($op)] == '=')
					{
						$tt = OP_ASSIGN;
						$match[0] .= '=';
					}
					else
					{
						$tt = $op;
						if ($this->scanOperand)
						{
							if ($op == OP_PLUS)
								$tt = OP_UNARY_PLUS;
							elseif ($op == OP_MINUS)
								$tt = OP_UNARY_MINUS;
						}
						$op = null;
					}
				break;

				case '.':
					if (preg_match('/^\.\d+(?:[eE][-+]?\d+)?/', $input, $match))
					{
						$tt = TOKEN_NUMBER;
						break;
					}
				// FALL THROUGH

				case ';':
				case ',':
				case '?':
				case ':':
				case '~':
				case '[':
				case ']':
				case '{':
				case '}':
				case '(':
				case ')':
					// these are all single
					$match = array($input[0]);
					$tt = $input[0];
				break;

				case '@':
					// check end of conditional comment
					if (substr($input, 0, 3) == '@*/')
					{
						$match = array('@*/');
						$tt = TOKEN_CONDCOMMENT_END;
					}
					else
						throw $this->newSyntaxError('Illegal token');
				break;

				case "\n":
					if ($this->scanNewlines)
					{
						$match = array("\n");
						$tt = TOKEN_NEWLINE;
					}
					else
						throw $this->newSyntaxError('Illegal token');
				break;

				default:
					// FIXME: add support for unicode and unicode escape sequence \uHHHH
					if (preg_match('/^[$\w]+/', $input, $match))
					{
						$tt = in_array($match[0], $this->keywords) ? $match[0] : TOKEN_IDENTIFIER;
					}
					else
						throw $this->newSyntaxError('Illegal token');
			}
		}

		$this->tokenIndex = ($this->tokenIndex + 1) & 3;

		if (!isset($this->tokens[$this->tokenIndex]))
			$this->tokens[$this->tokenIndex] = new JSToken();

		$token = $this->tokens[$this->tokenIndex];
		$token->type = $tt;

		if ($tt == OP_ASSIGN)
			$token->assignOp = $op;

		$token->start = $this->cursor;

		$token->value = $match[0];
		$this->cursor += strlen($match[0]);

		$token->end = $this->cursor;
		$token->lineno = $this->lineno;

		return $tt;
	}

	public function unget()
	{
		if (++$this->lookahead == 4)
			throw $this->newSyntaxError('PANIC: too much lookahead!');

		$this->tokenIndex = ($this->tokenIndex - 1) & 3;
	}

	public function newSyntaxError($m)
	{
		return new Exception('Parse error: ' . $m . ' in file \'' . $this->filename . '\' on line ' . $this->lineno);
	}
}
?>