<?php

	/**
	 * Author class.
	 * @package FoodOrder
	 * @subpackage Model
	 * @author Camilo Bravo <cambraca@gmail.com>
	 *
	 * @property string $id;
	 * @property string $name;
	 * @property string $email;
	 */
	class Author extends CModel_Entity
	{
		protected $_data = array(
			'id'	=> NULL,
			'name'	=> '',
			'email'	=> '',
			'posts'	=> NULL,
		);

		protected $_readonly = array(
		);
		
		protected $_required = array(
		);
		
		protected static $_filters = array(
		);
		
		protected static $_validators = array(
		);
		
		public function __construct($data = NULL) {
			parent::__construct($data);
			
			$this->setCollection('posts', 'Post', 'author_id');
		}

	}
