<?php

	/**
	 * Used when validating required properties, when one of them evaluates to FALSE.
	 *
	 * @package Pricetag
	 * @subpackage Exceptions
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	class PT_Exception_Entity_RequiredProperty extends PT_Exception
	{
		protected $_title = 'Required property';
		protected $_message = 'Property "%property%" is required.';
		protected $_vars = array('property');
	}
