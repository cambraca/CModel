<?php

	/**
	 * Used when trying to set a read-only property in an Entity.
	 *
	 * @package Pricetag
	 * @subpackage Exceptions
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	class CModel_Exception_Entity_ReadOnly extends CModel_Exception
	{
		protected $_title = 'Read-only';
		protected $_message = 'Property "%property%" marked as read-only.';
		protected $_vars = array('property');
	}
