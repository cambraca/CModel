<?php

	/**
	 * Main Entity class.
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	abstract class CModel_Entity
	{
		/**
		 * Used by the date() function to make a string that is suitable for the db.
		 */
		const DATE_FORMAT		= 'Y-m-d';
		const DATETIME_FORMAT	= 'Y-m-d H:i:s';
		const NULL_TIMESTAMP	= '0000-00-00 00:00:00';

		/**
		 * Version of the object. It can be overridden in a subclass.
		 */
		const VERSION = 1;
		
		/**
		 * This variable is set by the constructor.
		 * @var int
		 */
		private $_version;

		/**
		 * @var Zend_Session_Namespace
		 */
		private static $session;

		/**
		 * @var array
		 */
		protected $_references = array();
		
		/**
		 * @var array keyed by name
		 */
		protected $_data;
		
		/**
		 * $var array keyed by field name, value is list of validators
		 */
		protected static $_validators = array();

		/**
		 * $var array keyed by field name, value is list of filters
		 */
		protected static $_filters = array();
		
		/**
		 * Extra validators for this specific object
		 * @var array keyed by field name
		 */
		protected $_custom_validators = array();

		/**
		 * Extra filters for this specific object
		 * @var array keyed by field name
		 */
		protected $_custom_filters = array();

		/**
		 * List of fields that are readonly.
		 * @var array
		 */
		protected $_readonly = array();
		
		/**
		 * List of required properties.
		 * @var array
		 */
		protected $_required = array();
		
		/**
		 * Allows read-only properties to be set.
		 * Only TRUE throughout the constructor.
		 * @var bool
		 */
		protected $_creating = TRUE;
		
		/**
		 * Allows read-only properties to be set.
		 * Only TRUE during saving.
		 * @var bool
		 */
		private $_saving = FALSE;
		
		/**
		 * If TRUE, allows readonly properties to be set.
		 */
		private $_ignore_readonly = FALSE;
		
		/**
		 * Whether the object needs to be saved.
		 * For objects with children (for example, projects with steps), the save function is always called for each children.
		 * @var bool
		 */
		private $_changed = FALSE;
		
		/**
		 * When TRUE, __set doesn't throw validation errors directly, but instead gathers them in $this->_validation_errors.
		 * @see self::_collectValidation(), self::_checkValidation()
		 * @var bool
		 */
		private $_collect_validation_errors = FALSE;
		
		/**
		 * Key is property name, value is array of error messages.
		 * @var array
		 */
		private $_validation_errors;
		
		/**
		 * Create an object from an array of data.
		 * @param array $data 
		 */
		public function __construct($data = NULL)
		{
			$this->_version = static::VERSION;
			if (!is_null($data)) {
				$this->collectValidationErrors();
				foreach ($data as $name => $value) {
					$this->{$name} = $value;
				}
				$this->checkValidation();
			}
			$this->_creating = FALSE;
		}
		
		public function getVersion()
		{
			return $this->_version;
		}

		public function toArray()
		{
			return $this->_data;
		}
		
		/**
		 * Begins a validation collection routine. Between this call and checkValidation(), all calls to __set will not throw validation exceptions.
		 * Instead, when you call checkValidation(), they will be thrown as a single exception.
		 * @return NULL
		 */
		public function collectValidationErrors()
		{
			if ($this->_collect_validation_errors)
				throw new PT_Exception('Already collecting validation errors'); //TODO: change
			
			$this->_collect_validation_errors = TRUE;
			$this->_validation_errors = array();
		}
		
		public function checkValidation()
		{
			if (!$this->_collect_validation_errors)
				throw new PT_Exception('Not collecting validation errors. Call collectValidationErrors() first'); //TODO: change
			
			$this->_collect_validation_errors = FALSE;
			
			if ($this->_validation_errors)
				throw new PT_Exception_Entity_ValidationGroup($this->_validation_errors);
		}
		
		/**
		 * Checks the given data against this model's defined validation rules.
		 * The array may contain only some of the properties defined in the model.
		 * @param array $data keyed by property name
		 * @return NULL
		 * @throws PT_Exception_Entity_ValidationGroup
		 */
		public static function checkValidationGroup($data)
		{
			foreach ($data as $key => $value)
				self::_validate($key, $value, self::getGlobalValidators($key), TRUE, $errors);
			
			if ($errors)
				throw new PT_Exception_Entity_ValidationGroup($errors);
		}
		
		private static function _validate($name, $value, $validators, $collect_errors, &$errors)
		{
			if ($collect_errors && !is_array($errors))
				$errors = array();
			if ($validators)
			{
				//runs if there actually are validators on that field
				$e = new Zend_Form_Element('value', array('validators' => $validators));
				$v = new Zend_Validate();
				foreach ($e->getValidators() as $validator)
					$v->addValidator($validator);
				if (!$v->isValid($value))
				{
					if ($collect_errors)
					{
						if (!array_key_exists($name, $errors))
							$errors[$name] = array();
						$errors[$name] = array_merge($errors[$name], $v->getMessages());
					}
					else
						throw new PT_Exception_Entity_Validation($name, $value, $v->getMessages());
				}
			}
			
		}

		public function __set($name, $value)
		{
			if (!array_key_exists($name, $this->_data))
				throw new PT_Exception_Entity_NewProperty();
			
			if (!$this->_ignore_readonly && !$this->_creating && !$this->_saving && in_array($name, $this->_readonly))
				throw new PT_Exception_Entity_ReadOnly($name);
			
			//process filters
			foreach ($this->getFilters($name) as $filter)
			{
				if (is_string($filter))
					$filter = array($filter, NULL, array());
				$value = Zend_Filter::filterStatic($value, $filter[0], $filter[2]);
			}
			
			//handle _dt fields (timestamps)
			$is_dt = ($name == 'dt' || (strlen($name) > 3 && substr($name, -3) == '_dt'));
			$is_date = ($name == 'date' || (strlen($name) > 5 && substr($name, -5) == '_date'));
			if ($is_dt || $is_date)
			{
				if (is_object($value) && is_a($value, 'DateTime'))
					$value = $value->format($is_dt ? self::DATETIME_FORMAT : self::DATE_FORMAT);
				elseif ($value == self::NULL_TIMESTAMP)
					$value = NULL;
				else
				{
					if (is_string($value))
						$value = strtotime($value); //try to figure out the timestamp
					elseif (!is_numeric($value))
						$value = NULL;

					$value = is_null($value) ? NULL : date($is_dt ? self::DATETIME_FORMAT : self::DATE_FORMAT, $value); //convert to a format that the db can handle
				}
			}
			
			//process validators
			if ($value || in_array($name, $this->_required))
			{
				//if the value is empty and not required, skip validation
				self::_validate($name, $value, $this->getValidators($name), $this->_collect_validation_errors, $this->_validation_errors);
//				if ($validators = $this->getValidators($name))
//				{
//					//runs if there actually are validators on that field
//					$e = new Zend_Form_Element('value', array('validators' => $validators));
//					$v = new Zend_Validate();
//					foreach ($e->getValidators() as $validator)
//						$v->addValidator($validator);
//					if (!$v->isValid($value))
//					{
//						if ($this->_collect_validation_errors)
//						{
//							if (!array_key_exists($name, $this->_validation_errors))
//								$this->_validation_errors[$name] = array();
//							$this->_validation_errors[$name] = array_merge($this->_validation_errors[$name], $v->getMessages());
//						}
//						else
//							throw new PT_Exception_Entity_Validation($name, $value, $v->getMessages());
//					}
//				}
			}
			//this is another option of doing validation. when it fails, it doesn't return nice error messages
			/*foreach ($this->getValidators($name) as $validator)
			{
				if (is_string($validator))
					$validator = array($validator, NULL, array());
				if (!Zend_Validate::is($value, $validator[0], $validator[2]))
					throw new PT_Exception_Entity_Validation($name, $value);
			}*/
			
			$this->_data[$name] = $value;
			
			if ($value == NULL && $this->getReferenceId($name))
				//clear reference
				$this->setReferenceId($name, NULL);
			
			if (!$this->_creating)
				$this->_changed = TRUE;
		}

		public function __get($name)
		{
			if (array_key_exists($name, $this->_data))
				return $this->_data[$name];
		}

		public function __isset($name)
		{
			return isset($this->_data[$name]);
		}

		public function __unset($name)
		{
			if (isset($this->_data[$name])) {
				unset($this->_data[$name]);
			}
		}

		/**
		 * Set a reference
		 * @param string $name
		 * @param int|array $id this can be an array of ids
		 */
		public function setReferenceId($name, $id)
		{
			$this->_references[$name] = $id;
		}

		public function getReferenceId($name)
		{
			if (isset($this->_references[$name]))
				return $this->_references[$name];
			else
				return NULL;
		}
		
		/**
		 * Set the "changed" flag to TRUE.
		 * This is not meant to be used regularly; only in very special places.
		 * @return NULL
		 */
		public function setChanged()
		{
			$this->_changed = TRUE;
		}
		
		/**
		 * Returns whether the object has changed.
		 * @return bool
		 */
		public function hasChanged()
		{
			return $this->_changed;
		}
		
		/**
		 * Called when the object was saved to the db.
		 * If overriden, make sure this is also called.
		 * @return NULL
		 */
		public function saved()
		{
			$this->_changed = FALSE;
			$this->_saving = FALSE;
		}
		
		/**
		 * Called when an object is about to be saved.
		 * @return NULL
		 */
		public function saving()
		{
			$this->_saving = TRUE;
		}
		
		public function isSaving()
		{
			return $this->_saving;
		}
		
		/**
		 * Temporarily allow readonly properties to be set.
		 * Make sure to call allowReadOnly(FALSE) afterwards.
		 * @param bool $allow
		 */
		protected function allowReadOnly($allow = TRUE)
		{
			$this->_ignore_readonly = (bool) $allow;
		}
		
		/**
		 * Return the validators for the specified field, or an empty array if there are none.
		 * @param string $field
		 * @return array
		 */
		public static function getGlobalValidators($field)
		{
			$class = get_called_class();
			
			if (array_key_exists($field, $class::$_validators))
				return $class::$_validators[$field];
			else
				return array();
		}

		/**
		 * Return the filters for the specified field, or an empty array if there are none.
		 * @param string $field
		 * @return array
		 */
		public static function getGlobalFilters($field)
		{
			$class = get_called_class();
			
			if (array_key_exists($field, $class::$_filters))
				return $class::$_filters[$field];
			else
				return array();
		}
		
		public function getValidators($field)
		{
			$class = get_called_class();
			
			if (is_array($this->_custom_validators) && array_key_exists($field, $this->_custom_validators))
				return array_merge($class::getGlobalValidators($field), $this->_custom_validators[$field]);
			else
				return $class::getGlobalValidators($field);
		}
		
		public function getFilters($field)
		{
			$class = get_called_class();
			
			if (is_array($this->_custom_filters) && array_key_exists($field, $this->_custom_filters))
				return array_merge($class::getGlobalFilters($field), $this->_custom_filters[$field]);
			else
				return $class::getGlobalFilters($field);
		}
		
		/**
		 * Checks required properties.
		 * @return NULL
		 * @throws PT_Exception_Entity_RequiredProperty
		 */
		public function validateRequiredProperties()
		{
			foreach ($this->_required as $property)
				if (!$this->$property)
					throw new PT_Exception_Entity_RequiredProperty($property);
		}
		
		/**
		 * Generates a unique temp id. The last number used is stored in the session.
		 */
		public static function generateTempId()
		{
			if (!self::$session)
				self::$session = new Zend_Session_Namespace('PT_Entity', TRUE);
			
			if (!isset(self::$session->last_temp_id))
				$last_temp_id = self::$session->last_temp_id = 1;
			else
				$last_temp_id = self::$session->last_temp_id++;
			
			return 'new-'.self::$session->last_temp_id;
		}

	}
