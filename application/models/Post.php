<?php

	/**
	 * Post class.
	 * @package FoodOrder
	 * @subpackage Model
	 * @author Camilo Bravo <cambraca@gmail.com>
	 *
	 * @property string $id;
	 * @property string $author_id;
	 * @property string $title;
	 * @property string $content;
	 */
	class Post extends CModel_Entity
	{
		protected $_data = array(
			'id'		=> NULL,
			'author_id'	=> '',
			'title'		=> '',
			'content'	=> '',
		);

		protected $_readonly = array(
		);
		
		protected $_required = array(
		);
		
		protected static $_filters = array(
		);
		
		protected static $_validators = array(
		);

	}
