<?php 
/**
 * JSMinPlus version 1.3
 *
 * Minifies a javascript file using a javascript parser
 *
 * This implements a PHP port of Brendan Eich's Narcissus open source javascript engine (in javascript)
 * References: http://en.wikipedia.org/wiki/Narcissus_(JavaScript_engine)
 * Narcissus sourcecode: http://mxr.mozilla.org/mozilla/source/js/narcissus/
 * JSMinPlus weblog: http://crisp.tweakblogs.net/blog/cat/716
 *
 * Tino Zijdel <crisp@tweakers.net>
 *
 * Usage: $minified = JSMinPlus::minify($script [, $filename])
 *
 * Versionlog (see also changelog.txt):
 * 17-05-2009 - fixed hook:colon precedence, fixed empty body in loop and if-constructs
 * 18-04-2009 - fixed crashbug in PHP 5.2.9 and several other bugfixes
 * 12-04-2009 - some small bugfixes and performance improvements
 * 09-04-2009 - initial open sourced version 1.0
 *
 * Latest version of this script: http://files.tweakers.net/jsminplus/jsminplus.zip
 *
 */

/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is the Narcissus JavaScript engine.
 *
 * The Initial Developer of the Original Code is
 * Brendan Eich <brendan@mozilla.org>.
 * Portions created by the Initial Developer are Copyright (C) 2004
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s): Tino Zijdel <crisp@tweakers.net>
 * PHP port, modifications and minifier routine are (C) 2009
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */


class JSMinPlus
{
	private $parser;
	private $reserved = array(
		'break', 'case', 'catch', 'continue', 'default', 'delete', 'do',
		'else', 'finally', 'for', 'function', 'if', 'in', 'instanceof',
		'new', 'return', 'switch', 'this', 'throw', 'try', 'typeof', 'var',
		'void', 'while', 'with',
		// Words reserved for future use
		'abstract', 'boolean', 'byte', 'char', 'class', 'const', 'debugger',
		'double', 'enum', 'export', 'extends', 'final', 'float', 'goto',
		'implements', 'import', 'int', 'interface', 'long', 'native',
		'package', 'private', 'protected', 'public', 'short', 'static',
		'super', 'synchronized', 'throws', 'transient', 'volatile',
		// These are not reserved, but should be taken into account
		// in isValidIdentifier (See jslint source code)
		'arguments', 'eval', 'true', 'false', 'Infinity', 'NaN', 'null', 'undefined'
	);

	private function __construct()
	{
		$this->parser = new JSParser();
	}

	public static function minify($js, $filename='')
	{
		static $instance;

		// this is a singleton
		if(!$instance)
			$instance = new JSMinPlus();

		return $instance->min($js, $filename);
	}

	private function min($js, $filename)
	{
		try
		{
			$n = $this->parser->parse($js, $filename, 1);
			return $this->parseTree($n);
		}
		catch(Exception $e)
		{
			echo $e->getMessage() . "\n";
		}

		return false;
	}

	private function parseTree($n, $noBlockGrouping = false)
	{
		$s = '';

		switch ($n->type)
		{
			case KEYWORD_FUNCTION:
				$s .= 'function' . ($n->name ? ' ' . $n->name : '') . '(';
				$params = $n->params;
				for ($i = 0, $j = count($params); $i < $j; $i++)
					$s .= ($i ? ',' : '') . $params[$i];
				$s .= '){' . $this->parseTree($n->body, true) . '}';
			break;

			case JS_SCRIPT:
				// we do nothing with funDecls or varDecls
				$noBlockGrouping = true;
			// FALL THROUGH

			case JS_BLOCK:
				$childs = $n->treeNodes;
				$lastType = 0;
				for ($c = 0, $i = 0, $j = count($childs); $i < $j; $i++)
				{
					$type = $childs[$i]->type;
					$t = $this->parseTree($childs[$i]);
					if (strlen($t))
					{
						if ($c)
						{
							$s = rtrim($s, ';');

							if ($type == KEYWORD_FUNCTION && $childs[$i]->functionForm == DECLARED_FORM)
							{
								// put declared functions on a new line
								$s .= "\n";
							}
							elseif ($type == KEYWORD_VAR && $type == $lastType)
							{
								// mutiple var-statements can go into one
								$t = ',' . substr($t, 4);
							}
							else
							{
								// add terminator
								$s .= ';';
							}
						}

						$s .= $t;

						$c++;
						$lastType = $type;
					}
				}

				if ($c > 1 && !$noBlockGrouping)
				{
					$s = '{' . $s . '}';
				}
			break;

			case KEYWORD_IF:
				$s = 'if(' . $this->parseTree($n->condition) . ')';
				$thenPart = $this->parseTree($n->thenPart);
				$elsePart = $n->elsePart ? $this->parseTree($n->elsePart) : null;

				// empty if-statement
				if ($thenPart == '')
					$thenPart = ';';

				if ($elsePart)
				{
					// be carefull and always make a block out of the thenPart; could be more optimized but is a lot of trouble
					if ($thenPart != ';' && $thenPart[0] != '{')
						$thenPart = '{' . $thenPart . '}';

					$s .= $thenPart . 'else';

					// we could check for more, but that hardly ever applies so go for performance
					if ($elsePart[0] != '{')
						$s .= ' ';

					$s .= $elsePart;
				}
				else
				{
					$s .= $thenPart;
				}
			break;

			case KEYWORD_SWITCH:
				$s = 'switch(' . $this->parseTree($n->discriminant) . '){';
				$cases = $n->cases;
				for ($i = 0, $j = count($cases); $i < $j; $i++)
				{
					$case = $cases[$i];
					if ($case->type == KEYWORD_CASE)
						$s .= 'case' . ($case->caseLabel->type != TOKEN_STRING ? ' ' : '') . $this->parseTree($case->caseLabel) . ':';
					else
						$s .= 'default:';

					$statement = $this->parseTree($case->statements, true);
					if ($statement)
					{
						$s .= $statement;
						// no terminator for last statement
						if ($i + 1 < $j)
							$s .= ';';
					}
				}
				$s .= '}';
			break;

			case KEYWORD_FOR:
				$s = 'for(' . ($n->setup ? $this->parseTree($n->setup) : '')
					. ';' . ($n->condition ? $this->parseTree($n->condition) : '')
					. ';' . ($n->update ? $this->parseTree($n->update) : '') . ')';

				$body  = $this->parseTree($n->body);
				if ($body == '')
					$body = ';';

				$s .= $body;
			break;

			case KEYWORD_WHILE:
				$s = 'while(' . $this->parseTree($n->condition) . ')';

				$body  = $this->parseTree($n->body);
				if ($body == '')
					$body = ';';

				$s .= $body;
			break;

			case JS_FOR_IN:
				$s = 'for(' . ($n->varDecl ? $this->parseTree($n->varDecl) : $this->parseTree($n->iterator)) . ' in ' . $this->parseTree($n->object) . ')';

				$body  = $this->parseTree($n->body);
				if ($body == '')
					$body = ';';

				$s .= $body;
			break;

			case KEYWORD_DO:
				$s = 'do{' . $this->parseTree($n->body, true) . '}while(' . $this->parseTree($n->condition) . ')';
			break;

			case KEYWORD_BREAK:
			case KEYWORD_CONTINUE:
				$s = $n->value . ($n->label ? ' ' . $n->label : '');
			break;

			case KEYWORD_TRY:
				$s = 'try{' . $this->parseTree($n->tryBlock, true) . '}';
				$catchClauses = $n->catchClauses;
				for ($i = 0, $j = count($catchClauses); $i < $j; $i++)
				{
					$t = $catchClauses[$i];
					$s .= 'catch(' . $t->varName . ($t->guard ? ' if ' . $this->parseTree($t->guard) : '') . '){' . $this->parseTree($t->block, true) . '}';
				}
				if ($n->finallyBlock)
					$s .= 'finally{' . $this->parseTree($n->finallyBlock, true) . '}';
			break;

			case KEYWORD_THROW:
				$s = 'throw ' . $this->parseTree($n->exception);
			break;

			case KEYWORD_RETURN:
				$s = 'return';
				if ($n->value)
				{
					$t = $this->parseTree($n->value);
					if (strlen($t))
					{
						if (	$t[0] != '(' && $t[0] != '[' && $t[0] != '{' &&
							$t[0] != '"' && $t[0] != "'" && $t[0] != '/'
						)
							$s .= ' ';

						$s .= $t;
					}
				}
			break;

			case KEYWORD_WITH:
				$s = 'with(' . $this->parseTree($n->object) . ')' . $this->parseTree($n->body);
			break;

			case KEYWORD_VAR:
			case KEYWORD_CONST:
				$s = $n->value . ' ';
				$childs = $n->treeNodes;
				for ($i = 0, $j = count($childs); $i < $j; $i++)
				{
					$t = $childs[$i];
					$s .= ($i ? ',' : '') . $t->name;
					$u = $t->initializer;
					if ($u)
						$s .= '=' . $this->parseTree($u);
				}
			break;

			case KEYWORD_DEBUGGER:
				throw new Exception('NOT IMPLEMENTED: DEBUGGER');
			break;

			case TOKEN_CONDCOMMENT_START:
			case TOKEN_CONDCOMMENT_END:
				$s = $n->value . ($n->type == TOKEN_CONDCOMMENT_START ? ' ' : '');
				$childs = $n->treeNodes;
				for ($i = 0, $j = count($childs); $i < $j; $i++)
					$s .= $this->parseTree($childs[$i]);
			break;

			case OP_SEMICOLON:
				if ($expression = $n->expression)
					$s = $this->parseTree($expression);
			break;

			case JS_LABEL:
				$s = $n->label . ':' . $this->parseTree($n->statement);
			break;

			case OP_COMMA:
				$childs = $n->treeNodes;
				for ($i = 0, $j = count($childs); $i < $j; $i++)
					$s .= ($i ? ',' : '') . $this->parseTree($childs[$i]);
			break;

			case OP_ASSIGN:
				$s = $this->parseTree($n->treeNodes[0]) . $n->value . $this->parseTree($n->treeNodes[1]);
			break;

			case OP_HOOK:
				$s = $this->parseTree($n->treeNodes[0]) . '?' . $this->parseTree($n->treeNodes[1]) . ':' . $this->parseTree($n->treeNodes[2]);
			break;

			case OP_OR: case OP_AND:
			case OP_BITWISE_OR: case OP_BITWISE_XOR: case OP_BITWISE_AND:
			case OP_EQ: case OP_NE: case OP_STRICT_EQ: case OP_STRICT_NE:
			case OP_LT: case OP_LE: case OP_GE: case OP_GT:
			case OP_LSH: case OP_RSH: case OP_URSH:
			case OP_MUL: case OP_DIV: case OP_MOD:
				$s = $this->parseTree($n->treeNodes[0]) . $n->type . $this->parseTree($n->treeNodes[1]);
			break;

			case OP_PLUS:
			case OP_MINUS:
				$left = $this->parseTree($n->treeNodes[0]);
				$right = $this->parseTree($n->treeNodes[1]);

				switch ($n->treeNodes[1]->type)
				{
					case OP_PLUS:
					case OP_MINUS:
					case OP_INCREMENT:
					case OP_DECREMENT:
					case OP_UNARY_PLUS:
					case OP_UNARY_MINUS:
						$s = $left . $n->type . ' ' . $right;
					break;

					case TOKEN_STRING:
						//combine concatted strings with same quotestyle
						if ($n->type == OP_PLUS && substr($left, -1) == $right[0])
						{
							$s = substr($left, 0, -1) . substr($right, 1);
							break;
						}
					// FALL THROUGH

					default:
						$s = $left . $n->type . $right;
				}
			break;

			case KEYWORD_IN:
				$s = $this->parseTree($n->treeNodes[0]) . ' in ' . $this->parseTree($n->treeNodes[1]);
			break;

			case KEYWORD_INSTANCEOF:
				$s = $this->parseTree($n->treeNodes[0]) . ' instanceof ' . $this->parseTree($n->treeNodes[1]);
			break;

			case KEYWORD_DELETE:
				$s = 'delete ' . $this->parseTree($n->treeNodes[0]);
			break;

			case KEYWORD_VOID:
				$s = 'void(' . $this->parseTree($n->treeNodes[0]) . ')';
			break;

			case KEYWORD_TYPEOF:
				$s = 'typeof ' . $this->parseTree($n->treeNodes[0]);
			break;

			case OP_NOT:
			case OP_BITWISE_NOT:
			case OP_UNARY_PLUS:
			case OP_UNARY_MINUS:
				$s = $n->value . $this->parseTree($n->treeNodes[0]);
			break;

			case OP_INCREMENT:
			case OP_DECREMENT:
				if ($n->postfix)
					$s = $this->parseTree($n->treeNodes[0]) . $n->value;
				else
					$s = $n->value . $this->parseTree($n->treeNodes[0]);
			break;

			case OP_DOT:
				$s = $this->parseTree($n->treeNodes[0]) . '.' . $this->parseTree($n->treeNodes[1]);
			break;

			case JS_INDEX:
				$s = $this->parseTree($n->treeNodes[0]);
				// See if we can replace named index with a dot saving 3 bytes
				if (	$n->treeNodes[0]->type == TOKEN_IDENTIFIER &&
					$n->treeNodes[1]->type == TOKEN_STRING &&
					$this->isValidIdentifier(substr($n->treeNodes[1]->value, 1, -1))
				)
					$s .= '.' . substr($n->treeNodes[1]->value, 1, -1);
				else
					$s .= '[' . $this->parseTree($n->treeNodes[1]) . ']';
			break;

			case JS_LIST:
				$childs = $n->treeNodes;
				for ($i = 0, $j = count($childs); $i < $j; $i++)
					$s .= ($i ? ',' : '') . $this->parseTree($childs[$i]);
			break;

			case JS_CALL:
				$s = $this->parseTree($n->treeNodes[0]) . '(' . $this->parseTree($n->treeNodes[1]) . ')';
			break;

			case KEYWORD_NEW:
			case JS_NEW_WITH_ARGS:
				$s = 'new ' . $this->parseTree($n->treeNodes[0]) . '(' . ($n->type == JS_NEW_WITH_ARGS ? $this->parseTree($n->treeNodes[1]) : '') . ')';
			break;

			case JS_ARRAY_INIT:
				$s = '[';
				$childs = $n->treeNodes;
				for ($i = 0, $j = count($childs); $i < $j; $i++)
				{
					$s .= ($i ? ',' : '') . $this->parseTree($childs[$i]);
				}
				$s .= ']';
			break;

			case JS_OBJECT_INIT:
				$s = '{';
				$childs = $n->treeNodes;
				for ($i = 0, $j = count($childs); $i < $j; $i++)
				{
					$t = $childs[$i];
					if ($i)
						$s .= ',';
					if ($t->type == JS_PROPERTY_INIT)
					{
						// Ditch the quotes when the index is a valid identifier
						if (	$t->treeNodes[0]->type == TOKEN_STRING &&
							$this->isValidIdentifier(substr($t->treeNodes[0]->value, 1, -1))
						)
							$s .= substr($t->treeNodes[0]->value, 1, -1);
						else
							$s .= $t->treeNodes[0]->value;

						$s .= ':' . $this->parseTree($t->treeNodes[1]);
					}
					else
					{
						$s .= $t->type == JS_GETTER ? 'get' : 'set';
						$s .= ' ' . $t->name . '(';
						$params = $t->params;
						for ($i = 0, $j = count($params); $i < $j; $i++)
							$s .= ($i ? ',' : '') . $params[$i];
						$s .= '){' . $this->parseTree($t->body, true) . '}';
					}
				}
				$s .= '}';
			break;

			case KEYWORD_NULL: case KEYWORD_THIS: case KEYWORD_TRUE: case KEYWORD_FALSE:
			case TOKEN_IDENTIFIER: case TOKEN_NUMBER: case TOKEN_STRING: case TOKEN_REGEXP:
				$s = $n->value;
			break;

			case JS_GROUP:
				$s = '(' . $this->parseTree($n->treeNodes[0]) . ')';
			break;

			default:
				throw new Exception('UNKNOWN TOKEN TYPE: ' . $n->type);
		}

		return $s;
	}

	private function isValidIdentifier($string)
	{
		return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $string) && !in_array($string, $this->reserved);
	}
}

?>