<?php

	/**
	 * Used when trying to set values that fails validation.
	 *
	 * @package Pricetag
	 * @subpackage Exceptions
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	class PT_Exception_Entity_ValidationGroup extends PT_Exception
	{
		protected $_title = 'Validation error';
		protected $_message = 'There were validation errors. Please try again.';
		
		private $_validation_errors = array();
		
		public function  __construct($validation_errors)
		{
			parent::__construct();
			
			$this->_validation_errors = $validation_errors;
		}
		
		public function getValidationErrors()
		{
			return $this->_validation_errors;
		}
	}
