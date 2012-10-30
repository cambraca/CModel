<?php

	/**
	 * Used when trying to set a value that fails validation.
	 *
	 * @package Pricetag
	 * @subpackage Exceptions
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	class PT_Exception_Entity_Validation extends PT_Exception
	{
		protected $_title = 'Validation error';
		protected $_message = 'Invalid %property%: "%value%".';
		protected $_vars = array('property', 'value');
		
		public function  __construct($property, $value, $messages = NULL)
		{
			if ($messages)
			{
				$this->_vars = array();
				
				if (is_array($messages))
				{
					if (count($messages) == 1)
						$message = implode('', $messages);
					else
						$message = '- '.implode(PHP_EOL.'- ', $messages);
				}
				else
					$message = $messages;
				
				$this->_message = $message;
				parent::__construct();
			}
			else
				parent::__construct($property, $value);
		}
	}
