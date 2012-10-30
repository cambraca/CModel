<?php

	/**
	 * Used when trying to set a new property in an Entity.
	 *
	 * @package Pricetag
	 * @subpackage Exceptions
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	class CModel_Exception_Entity_NewProperty extends CModel_Exception
	{
		//protected $_title;
		protected $_message = 'You cannot set new properties on this object.';
	}
