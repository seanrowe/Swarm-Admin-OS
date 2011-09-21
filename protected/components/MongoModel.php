<?php

/**
 * Quick wrapper that implements the mongodb class
 * to replace Active Record, which does not support
 * mongodb
 *
 * @author Sean
 */
abstract class MongoModel
{
	/**
	 * Holds an instance of the class
	 * @var type
	 */
	public static $instance = null;

	/**
	 * The id of the record.  Initially null until
	 * an insert, or if the record is populated with
	 * a find
	 * @var type MongoId
	 */
	public $id = null;

	/**
	 * The mongo database instance
	 * @var type object
	 */
	protected $db = null;

	/**
	 * The name of the collection we are working with
	 * @var type string
	 */
	protected $table = null;

	/**
	 * Holds the values for a record so that
	 * magic methods __get and __set can
	 * be used
	 * @var type array
	 */
	protected $record = array();

	/**
	 * Gets the field names
	 * @return type
	 */
	public function fields()
	{
		return array();
	}

	/**
	 * Instantiate the database object using
	 * the credentials in the configuration
	 */
	public function __construct()
	{
		if (false === isset(Yii::app()->params['mongo']))
		{
			throw new Exception('Missing mongo configuration array in params');
		}

		if (false === is_array(Yii::app()->params['mongo']))
		{
			throw new Exception('Mongo must be an array in params configuration');
		}

		if (false === isset(Yii::app()->params['mongo']['host']))
		{
			Yii::app()->params['mongo']['host'] = 'localhost';
		}

		if (false === isset(Yii::app()->params['mongo']['port']))
		{
			Yii::app()->params['mongo']['host'] = 27017;
		}

		if (false === isset(Yii::app()->params['mongo']['db']))
		{
			throw new Exception('You must specify the database in mongo configuration');
		}

		$connection = "mongodb://";

		if (true === isset(Yii::app()->params['mongo']['username']))
		{
			if (false === isset(Yii::app()->params['mongo']['password']))
			{
				throw new Exception('You must supply the password');
			}

			$connection .= Yii::app()->params['mongo']['username'] . ':' . Yii::app()->params['mongo']['password'] . '@';
		}

		$connection .=	Yii::app()->params['mongo']['host'];

		if (true === isset(Yii::app()->params['mongo']['port']))
		{
			$connection .= ':' . Yii::app()->params['mongo']['port'];
		}

		$connection .= '/' . Yii::app()->params['mongo']['db'];

		try
		{
			if (true === isset(Yii::app()->params['mongo']['replica']))
			{
				if (false === is_array(Yii::app()->params['mongo']['replica']))
				{
					throw new Exception('Replica must an array in the mongo configuration');
				}

				$this->db = new Mongo($connection, Yii::app()->params['mongo']['replica']);
			}

			else
			{
				$this->db = new Mongo($connection);
			}
		}

		catch(Exception $e)
		{
			Yii::log($e->getMessage(), 'error', 'system.db.MongoModel');
			throw new Exception('There was an issue connecting to the database');
		}

		$this->record = $this->fields();
	}

	/**
	 * Set the value in a record
	 * @param type $key
	 * @param type $value
	 */
	public function __set($key, $value)
	{
		if (false === isset($this->record[$key]))
		{
			throw new Exception("$key is not set in record");
		}

		$this->record[$key] = $value;
	}

	/**
	 * Get the value in a record
	 */
	public function __get($key)
	{
		if (false === isset($this->record[$key]))
		{
			throw new Exception("$key is not set in record");
		}

		return $this->record[$key];
	}

	/**
	 * Check if a variable has been set in the record
	 * @param type $key
	 */
	public function __isset($key)
	{
		return isset($this->record[$key]);
	}

	/**
	 * Validation is run before any crud operations
	 * @return type
	 */
	public function validate()
	{
		return true;
	}

	/**
	 * Inserts a record.  Will throw an exception if
	 * there are no windows to insert along with the
	 * module information
	 */
	public function insert()
	{
		$this->validate();
		$this->collection()->insert($this->record, array('safe' => true));
		$this->id = $this->record['_id'];
	}

	/**
	 * Updates a record.  The id should already have been populated,
	 * and will throw an exception if it has not
	 */
	public function update()
	{
		if (null === $this->id)
		{
			throw new Exception('ID has not been set, so an update can not be performed');
		}

		$this->validate();
		$this->collection()->update(array("_id" => $this->id), $this->record, array('safe' => true));
	}

	/**
	 * Remove a record
	 */
	public function remove()
	{
		if (null === $this->id)
		{
			throw new Exception('ID has not been set, so an update can not be performed');
		}

		$this->validate();
		$this->collection()->remove(array("_id" => $this->id), array('safe' => true));
	}

	/**
	 * Wrapper for MongoCollection::find
	 * @param array $query
	 * @param array $fields
	 * @return type MongoCursor
	 */
	public function find(array $query = array(), array $fields = array())
	{
		return $this->collection()->find($query, $fields);
	}

	/**
	 * Wrapper for MongoCollection::findOne
	 * @param array $query
	 * @param array $fields
	 * @return type MongoCursor
	 */
	public function findOne(array $query = array(), array $fields = array())
	{
		return $this->collection()->find($query, $fields);
	}
}

?>
