<?php 

class JSCompilerContext
{
	public $inFunction = false;
	public $inForLoopInit = false;
	public $ecmaStrictMode = false;
	public $bracketLevel = 0;
	public $curlyLevel = 0;
	public $parenLevel = 0;
	public $hookLevel = 0;

	public $stmtStack = array();
	public $funDecls = array();
	public $varDecls = array();

	public function __construct($inFunction)
	{
		$this->inFunction = $inFunction;
	}
}

?>