<?php

	/**
	 * Main Mapper class.
	 * 
	 * @version 1.0
	 * @package CModel
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	abstract class CModel_Mapper
	{

		/**
		 * @var CModel_Table
		 */
		protected $_tableGateway = NULL;
		
		/**
		 * The table name, to construct the gateway object.
		 * @var string
		 */
		protected $_tableName;

		/**
		 * Key is id, value is domain object.
		 * @var array
		 */
		protected $_identityMap = array();
		
		/**
		 * Defines classes that are logically the children of the current class.
		 * @var array for example array('ProjectMapper', 'TableMapper') for class CModel_StepMapper
		 */
		protected $_childClasses = array();
		
		/**
		 * Contains the instances of the mapper objects, keyed by class name.
		 * Normally there can only be one mapper instance for each class.
		 * @var CModel_Mapper
		 */
		private static $_instances = array();

		/**
		 * Do not use the constructor. Use the singleton() static function instead.
		 * @param Zend_Db_Table_Abstract $tableGateway 
		 */
		private function __construct(Zend_Db_Table_Abstract $tableGateway = NULL)
		{
			if (is_null($tableGateway)) {
				$this->_tableGateway = new CModel_Table($this->_tableName);
				self::$_instances[get_called_class()] = $this;
			} else {
				$this->_tableGateway = $tableGateway;
			}
		}
		
		/**
		 * Returns an instance of the mapper class.
		 * Keeps a record of instantiated classes, and returns the already created object if one exists.
		 * Should only be called from a subclass of CModel_Mapper.
		 * @return CModel_Mapper subclass
		 */
		public static function singleton()
		{
			$class = get_called_class();
			return array_key_exists($class, self::$_instances) ? self::$_instances[$class] : new $class();
		}

		/**
		 * @return CModel_Table
		 */
		public function _getGateway()
		{
			return $this->_tableGateway;
		}

		/**
		 * Retrieves a previously saved entity object, by id.
		 * @param int|string $id it can be a single id, or a multiple id (string with ids separated by |)
		 * @return CModel_Entity
		 */
		protected function _getIdentity($id)
		{
			if (array_key_exists($id, $this->_identityMap)) {
				return $this->_identityMap[$id];
			}
		}

		/**
		 * Stores the entity in an array keyed by id.
		 * @param int|string $id @see CModel_Mapper::_getIdentity()
		 * @param CModel_Entity $entity 
		 */
		protected function _setIdentity($id, $entity)
		{
			$this->_identityMap[$id] = $entity;
		}
		
		/**
		 * Clears the identity map. Optionally clear "child classes"' caches too.
		 */
		public function clearCache($recursive = FALSE)
		{
			$this->_identityMap = array();
			if ($recursive)
				foreach ($this->_childClasses as $class_name)
				{
					$class_name = 'CModel_'.$class_name.'Mapper';
					$class_name::singleton()->clearCache(TRUE);
				}
		}
		
		/**
		 * Returns an object, by id.
		 * @param int|array $id if int, loads from the DB; if array, loads from the array (must include all the fields from the table)
		 * @return CModel_Entity
		 */
		abstract public function find($id);

		/**
		 * Gets the last_update_dt for the given object, always from the database.
		 * This assumes the object has an id and a last_update_dt
		 * @param CModel_Entity $object
		 * @return timestamp|NULL NULL if not found
		 */
		public function getLastUpdateDt($object)
		{
			if (!$object)
				return NULL;
			if (!$object->id)
				return NULL;
			
			$table = $this->_getGateway();
			$row = $table->fetchRow(
				$table
					->select(TRUE)
					->reset(Zend_Db_Table::COLUMNS)
					->columns('last_update_dt')
					->where('id = ?', $object->id)
			);
			if (!$row)
				return NULL;
			$value = $row->last_update_dt;
			if ($value == CModel_Entity::NULL_TIMESTAMP)
				return NULL;
			if (!is_numeric($value))
				$value = strtotime($value); //try to figure out the timestamp
			return date(CModel_Entity::DATETIME_FORMAT, $value);
		}

	}
