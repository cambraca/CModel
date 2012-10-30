<?php

	/**
	 * Author Mapper class.
	 * @package FoodOrder
	 * @subpackage Mappers
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	class AuthorMapper extends CModel_Mapper
	{
		protected $_tableName = 'author';
		protected $_entityClass = 'Author';

		public function save(Author $author)
		{
			if (!$author->id || $author->hasChanged())
			{
				$author->validateRequiredProperties();
				$author->saving();
				$data = array(
					'name'	=> $author->name,
					'email'	=> $author->email,
				);
				if (!$author->id) {
					$author->id = $this->_getGateway()->insert($data);
					$this->_setIdentity($author->id, $author);
				} else {
					$where = $this->_getGateway()->getAdapter()
						->quoteInto('id = ?', $author->id);
					$this->_getGateway()->update($data, $where);
				}
				$author->saved();
			}
		}

		public function find($id)
		{
			if (is_object($id))
			{
				$result = $id;
				$id = $result->id;
			}
			if ($this->_getIdentity($id)) {
				return $this->_getIdentity($id);
			}
			if (!isset($result))
				$result = $this->_getGateway()->find($id)->current();
			$author = new $this->_entityClass(array(
				'id'	=> $result->id,
				'name'	=> $result->name,
				'email'	=> $result->email,
			));

			$this->_setIdentity($id, $author);
			return $author;
		}
		
		public function findAll()
		{
			return new CModel_Collection('Author');
		}

	}
