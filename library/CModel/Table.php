<?php

	/**
	 * Main Table class. Extends from Zend_Db_Table.
	 * 
	 * @version 1.0
	 * @package CModel
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	class CModel_Table extends Zend_Db_Table
	{
//		const MIN_ID = 1000000000;
////		const MAX_ID = 4294967295; //max INT UNSIGNED value in MySQL
//		const MAX_ID = 1999999999; //max INT UNSIGNED value in MySQL
		
		private static $_chars;
		
		public function insert($data, $no_id_generation = FALSE)
		{
			if (in_array('id', $this->_getCols()))
			{
				do
				{
					$data['id'] = self::_generateId();
					$found = $this->find($data['id'])->count();
				} while ($found);
			}
			return parent::insert($data);
		}
		
		private function _generateId()
		{
//			return rand(self::MIN_ID, self::MAX_ID);
			
			//6 alphanumeric characters
			if (!self::$_chars)
				self::$_chars = array_merge(range('a','z'), range(2,9)); //exclude 0 and 1 to avoid confusion with letters o and l
			
			$count = count(self::$_chars);
			
			$ret = '';
			
			for ($i = 0; $i < 6; $i++)
				$ret .= self::$_chars[rand(0, $count-1)];
			
			return $ret;
		}
	}