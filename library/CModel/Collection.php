<?php

	/**
	 * Collection class. Allows efficient communication with the DB with stuff like lists (one query to get all the objects in a page, etc)
	 * 
	 * @version 1.0
	 * @package CModel
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	class CModel_Collection implements Iterator, ArrayAccess, Countable
	{
		const SEARCH_OPERATOR_LIKE		= 'like';
		const SEARCH_OPERATOR_EQUALS	= 'equals';
		
		const FK_ID = '%fk_id%';
		
		/**
		 * @var bool
		 */
		private $_do_query = TRUE;
		
		/**
		 * @var bool
		 */
		private $_add_search_conditions = TRUE;
		
		/**
		 * Class name. Class should inherit from CModel_Entity.
		 * @var string
		 */
		private $_class_name;
		
		/**
		 * Mapper class name. Class should inherit from CModel_Mapper.
		 * @var string
		 */
		private $_mapper_class_name;

		/**
		 * @var string|NULL
		 */
		private $_fk_field = NULL;
		
		/**
		 * @var CModel_Entity
		 */
		private $_fk_object = NULL;
		
		/**
		 * @var Zend_Db_Select
		 */
		private $_select;
		
		/**
		 * @var array
		 */
		private $_where = array();
		/**
		 * @var array
		 */
		private $_join = array();
		/**
		 * @var mixed
		 */
		private $_order;
		
		/**
		 * Internal position.
		 * @var int
		 */
		private $_position;
		
		/**
		 * This array can contain query result row objects and/or proper objects for this collection (CModel_Entity)
		 * @var Object[]
		 */
		private $_data;
		
		/**
		 * NULL means there is no pagination.
		 * @var int|NULL
		 */
		private $_items_per_page = NULL;
		
		/**
		 * Current page. 1-based. Only useful when $this->_items_per_page is not NULL
		 * @var int
		 */
		private $_page = 1;
		
		/**
		 * @see self::defineSearchCondition()
		 * @var array
		 */
		private $_search_conditions = array();
		
		/**
		 * @var string
		 */
		private $_search_query = NULL;
		
		/**
		 * Total number of pages
		 * @var int
		 */
		private $_pages = 1;
		
		/**
		 * @param string $class_name
		 * @param string $fk_field 
		 * @param string $fk_field 
		 */
		public function __construct($class_name, $fk_field = NULL, $fk_object = NULL)
		{
			$this->_class_name = $class_name;
			if (!is_subclass_of($this->_class_name, 'CModel_Entity'))
				throw new Exception('Wrong entity class: '.$this->_class_name);
			
			$this->_mapper_class_name = $class_name::getMapperClassName();
			if (!class_exists($this->_mapper_class_name))
				throw new Exception('Mapper not found');
			if (!is_subclass_of($this->_mapper_class_name, 'CModel_Mapper'))
				throw new Exception('Wrong mapper class');
			
			$this->_fk_field = $fk_field;
			
			if (!is_null($fk_object) && !($fk_object instanceof CModel_Entity))
				throw new Exception();
			$this->_fk_object = $fk_object;
			
			$this->reset();
		}
		
		/**
		 * Recreates the collection from the original query, but does not delete WHERE clauses, etc.
		 * @return NULL
		 */
		public function invalidate()
		{
			$this->_do_query = TRUE;
		}
		
		/**
		 * @return CModel_Table
		 */
		private function _getGateway()
		{
			$c = $this->_mapper_class_name;
			return $c::singleton()->_getGateway();
		}
		
		/**
		 * Recreates the collection from the original query. Deletes all posterior WHERE clauses, ORDER, etc.
		 * @return NULL
		 */
		public function reset()
		{
			$this->invalidate();
			$this->_join = array();
			$this->_where = array();
			$this->_order = array();
//			$this->_select = $this->_getGateway()->select(TRUE);
			
			if ($this->_fk_field && $this->_fk_object) {
				$this->where($this->_fk_field . ' = ?', self::FK_ID);
			}
		}

		/**
		 * Proxy for select object's "join" method.
		 * @param array|string|Zend_Db_Expr $name
		 * @param string $cond
		 * @param array|string $cols
		 * @param string $schema
		 * @return CModel_Collection 
		 */
		public function join($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = NULL)
		{
			$this->invalidate();
//			if ($cols)
//				$this->_select->setIntegrityCheck(FALSE);
//			$this->_select->join($name, $cond, $cols, $schema);
			$this->_join[] = func_get_args();
			return $this;
		}
		
		/**
		 * Proxy for select object's "where" method.
		 * @param string $cond
		 * @param mixed $value
		 * @param int $type 
		 * @return NULL
		 */
		public function where($cond, $value = NULL, $type = NULL)
		{
			$this->invalidate();
			$this->_where[] = func_get_args();
//			$this->_select->where($cond, $value, $type);
			return $this;
		}
		
		/**
		 * Proxy for select object's "order" method.
		 * @param mixed $spec 
		 * @return NULL
		 */
		public function order($spec)
		{
			$this->invalidate();
			$this->_order = $spec;
//			$this->_select->order($spec);
			return $this;
		}
		
		/**
		 * @param int|NULL $items_per_page if NULL, no pagination
		 */
		public function setItemsPerPage($items_per_page)
		{
			if ($items_per_page == $this->_items_per_page)
				return;
			
			$this->invalidate();
			$this->_items_per_page = is_null($items_per_page) || $items_per_page <= 0 ? NULL : (int) $items_per_page;
		}
		
		/**
		 * @param int $page 1-based
		 */
		public function setPage($page)
		{
			$page = $page >= 1 ? (int) $page : 1;
			
			$pages = $this->getPages();
			if (!is_null($pages) && $page > $pages)
				$page = $pages;
			
			if ($page == $this->_page)
				return;
			
			$this->invalidate();
			$this->_page = $page;
		}
		
		/**
		 * Gets the total number of pages in this collection. NULL if there is no pagination
		 * @return int|NULL
		 */
		public function getPages()
		{
			$this->_doQuery();
			return $this->_pages;
		}
		
		private function _doQuery()
		{
			if (!$this->_do_query)
				return;
			$this->_do_query = FALSE;
			
			if ($this->_fk_field && $this->_fk_object && !$this->_fk_object->id)
			{
				$this->_data = array();
				$this->_do_query = TRUE;
				return;
			}
			
			//create select query
			$select = $this->_getGateway()->select(TRUE);
			foreach ($this->_join as $join)
			{
				if (isset($join[2]) && $join[2])
					$select->setIntegrityCheck(FALSE);
				call_user_func_array(array($select, 'join'), $join);
			}
			foreach ($this->_where as $where)
			{
				if ($where[1] == self::FK_ID)
					$where[1] = $this->_fk_object->id;
				call_user_func_array(array($select, 'where'), $where);
			}
			if ($this->_order)
				$select->order($this->_order);

			//search, if any
			if ($this->_add_search_conditions && $this->_search_query)
			{
				$this->_add_search_conditions = FALSE;
				$temp_query = Zend_Registry::get('db')->select();
				$first = TRUE;
				foreach ($this->_search_conditions as $s)
				{
					switch ($s[1])
					{
						case self::SEARCH_OPERATOR_LIKE:
							if ($first)
								$temp_query->where($s[0], '%'.$this->_search_query.'%');
							else
								$temp_query->orWhere($s[0], '%'.$this->_search_query.'%');
							break;
						case self::SEARCH_OPERATOR_EQUALS:
							if ($first)
								$temp_query->where($s[0], $this->_search_query);
							else
								$temp_query->orWhere($s[0], $this->_search_query);
							break;
						default:
							throw new Exception();
					}
					if ($first) $first = FALSE;
				}
				if (!$first)
				{
					//there IS a where clause to be added to $select
					$select->where(join(' ', $temp_query->getPart(Zend_Db_Select::WHERE)));
				}
			}

			//pagination
			if (!is_null($this->_items_per_page) && $this->_items_per_page > 0)
			{
				$count_row = $this->_getGateway()->fetchRow(
					$select
						->reset(Zend_Db_Select::COLUMNS)
						->columns(array('count' => 'COUNT(*)'))
				);
				$this->_pages = ceil($count_row->count / $this->_items_per_page);
				$select
					->reset(Zend_Db_Select::COLUMNS)
					->columns()
					->limitPage($this->_page, $this->_items_per_page);
			}
			else
				$this->_pages = 1;

//CModel_Dev::d((string) $select, 'Query');
			$this->_data = array();
			foreach ($this->_getGateway()->fetchAll($select) as $row)
				$this->_data[] = $row;
		}
		
		public function defineSearchCondition($where, $operator = self::SEARCH_OPERATOR_LIKE)
		{
			$this->_search_conditions[] = array($where, $operator);
		}
		
		public function search($query)
		{
			if (!$query)
				$query = NULL;
			
			if ($query == $this->_search_query)
				return;
			
			$this->invalidate();
			$this->_search_query = $query;
		}
		
		public function rewind() {
			$this->_doQuery();
			$this->_position = 0;
		}

		public function current() {
			return $this->offsetGet($this->_position);
		}

		public function key() {
			return $this->_position;
		}

		public function next() {
			++$this->_position;
		}

		public function valid() {
			$this->_doQuery();
			return isset($this->_data[$this->_position]);
		}
		
		public function count() {
			$this->_doQuery();
			return count($this->_data);
		}
		
		public function offsetSet($offset, $value) {
			if (!is_object($value) || !is_a($value, $this->_class_name))
				throw new Exception('Wrong object type');
			
			$this->_doQuery();
			
			if (is_null($offset))
				$offset = count($this->_data);
			
			$this->_data[$offset] = $value;
		}

		public function offsetExists($offset) {
			$this->_doQuery();
			return isset($this->_data[$offset]);
		}

		public function offsetUnset($offset) {
			throw new Exception('Not supported yet: unsetting items from a CModel_Collection by offset');
		}

		public function offsetGet($offset) {
			$this->_doQuery();
			
			if (!isset($this->_data[$offset]))
				return NULL;
			
			if (is_a($this->_data[$offset], $this->_class_name))
				return $this->_data[$offset];
			else
			{
				$c = $this->_mapper_class_name;
				$ret = $c::singleton()->find($this->_data[$offset]);
				$this->_data[$offset] = $ret;
				return $ret;
			}
		}
		
		public function contains($object)
		{
			foreach ($this as $item)
				if (
					is_a($object, $this->_class_name)
					&& $object->id
					&& $item->id
					&& $object->id == $item->id
				)
					return TRUE;
			return FALSE;
		}
	}