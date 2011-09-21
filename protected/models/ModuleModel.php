<?php

/**
 * Model class for the module collection
 *
 * @author Sean
 */
class ModuleModel extends MongoModel
{
	/**
	 * Set the field names for this model
	 */
	public function fields()
	{
		return array(
			'name' => null,
			'title' => null,
			'description' => null,
			'windows' => array()
		);
	}

	/**
	 * Return a static instance
	 * @return type
	 */
	public static function instance()
	{
		if (null === self::$instance)
		{
			$class = __CLASS__;
			self::$instance = new $class();
		}

		return self::$instance;
	}

	/**
	 * Gets the module collection
	 * @return type MongoCollection
	 */
	public function collection()
	{
		return $this->db->swarm->module;
	}

	/**
	 * Add a window to the module. The window array
	 * must contain the following parameters:
	 *
	 *	description
	 *	title
	 *	name
	 *
	 * @param array $window
	 */
	public function addWindow(array $window)
	{
		if (false === isset($window['description']) || '' == $window['description'])
		{
			throw new Exception('You must supply the window description');
		}

		if (false === isset($window['title']) || '' == $window['title'])
		{
			throw new Exception('You must supply the window title');
		}

		if (false === isset($window['name']) || '' == $window['name'])
		{
			throw new Exception('You must supply the window name');
		}

		$this->windows[] = $window;
	}

	/**
	 * Check that the values have been properly
	 * assigned to the model
	 */
	public function validate()
	{
		if (false === isset($this->windows) || true === empty($this->windows))
		{
			throw new Exception('You must supply at least one window in order to insert');
		}

		if (false === isset($this->name) || '' == $this->name)
		{
			throw new Exception('You must supply a name for the module');
		}

		if (false === isset($this->title) || '' == $this->title)
		{
			throw new Exception('You must supply a title for the module');
		}

		if (false === isset($this->description) || '' == $this->description)
		{
			throw new Exception('You must supply a description for the module');
		}
	}
}

?>
