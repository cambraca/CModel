<?php

	/**
	 * Post Mapper class.
	 * @package FoodOrder
	 * @subpackage Mappers
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	class PostMapper extends CModel_Mapper
	{
		protected $_tableName = 'post';
		protected $_entityClass = 'Post';

		public function save(Post $post)
		{
			if (!$post->id || $post->hasChanged())
			{
				$post->validateRequiredProperties();
				$post->saving();
				$data = array(
					'author_id'	=> $post->author_id,
					'title'		=> $post->title,
					'content'	=> $post->content,
				);
				if (!$post->id) {
					$post->id = $this->_getGateway()->insert($data);
					$this->_setIdentity($post->id, $post);
				} else {
					$where = $this->_getGateway()->getAdapter()
						->quoteInto('id = ?', $post->id);
					$this->_getGateway()->update($data, $where);
				}
				$post->saved();
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
			$post = new $this->_entityClass(array(
				'id'		=> $result->id,
				'author_id'	=> $result->author_id,
				'title'		=> $result->title,
				'content'	=> $result->content,
			));

			$this->_setIdentity($id, $post);
			return $post;
		}

	}
