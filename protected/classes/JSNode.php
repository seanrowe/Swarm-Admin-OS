<?php 

class JSNode
{
	private $type;
	private $value;
	private $lineno;
	private $start;
	private $end;

	public $treeNodes = array();
	public $funDecls = array();
	public $varDecls = array();

	public function __construct($t, $type=0)
	{
		if ($token = $t->currentToken())
		{
			$this->type = $type ? $type : $token->type;
			$this->value = $token->value;
			$this->lineno = $token->lineno;
			$this->start = $token->start;
			$this->end = $token->end;
		}
		else
		{
			$this->type = $type;
			$this->lineno = $t->lineno;
		}

		if (($numargs = func_num_args()) > 2)
		{
			$args = func_get_args();;
			for ($i = 2; $i < $numargs; $i++)
				$this->addNode($args[$i]);
		}
	}

	// we don't want to bloat our object with all kind of specific properties, so we use overloading
	public function __set($name, $value)
	{
		$this->$name = $value;
	}

	public function __get($name)
	{
		if (isset($this->$name))
			return $this->$name;

		return null;
	}

	public function addNode($node)
	{
		if ($node !== null)
		{
			if ($node->start < $this->start)
				$this->start = $node->start;
			if ($this->end < $node->end)
				$this->end = $node->end;
		}

		$this->treeNodes[] = $node;
	}
}
?>