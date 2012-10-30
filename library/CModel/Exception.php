<?php

	/**
	 * Main exception class. All others should extend this.
	 *
	 * @package Pricetag
	 * @subpackage Exceptions
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	class CModel_Exception extends Exception
	{
		/**
		 * Error title. Override to change from the default (see function getTitle()).
		 * @var string
		 */
		protected $_title;
		
		/**
		 * For example "Object %object% has a %node%."
		 * @var string
		 */
		protected $_message = 'Unknown Pricetag error.';
		
		/**
		 * For example array("object", "node")
		 * @var array
		 */
		protected $_vars = array();

		/**
		 * Two modes: one in which $this->vars is empty ($message is used, and if it's ommitted, the default message is used);
		 * the other, when $vars is defined, takes the function args and uses them for string replacements.
		 * So for example, if $vars = array('one', 'two'), and $message = 'Hello, %two%, %one%, %two% again.',
		 * new PT_Exception_CustomException('uno', 'dos') will give the following message:
		 * "Hello, dos, uno, dos again."
		 * @param $message 
		 */
		public function  __construct($message = '')
		{
			if ($this->_vars)
			{
				$vars = array();
				foreach ($this->_vars as $v)
					$vars[] = '%'.$v.'%';
				$args = func_get_args();
				parent::__construct(
					str_replace($vars, $args, $this->_message)
				);
			} else
				parent::__construct(
					$message ? $message : $this->_message
				);
		}
		
		public function getTitle()
		{
			return $this->_title ? $this->_title : 'Pricetag error';
		}
	}
