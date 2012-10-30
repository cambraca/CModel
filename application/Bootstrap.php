<?php

	class My_Loader extends Zend_Loader
	{
		public static function loadClass($class, $dirs = null)
		{
			parent::loadClass(
				$class,
				array(
					APPLICATION_PATH.'/forms',
					APPLICATION_PATH.'/models',
					
				)
			);
		}

		public static function autoload($class)
		{
			try {
				self::loadClass($class);
				return $class;
			} catch (Exception $e) {
				return false;
			}
		}
	}

	class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
	{
		
		protected function _initAutoLoader()
		{
			Zend_Loader::registerAutoload('My_Loader');
			$al = Zend_Loader_Autoloader::getInstance();
			$al->registerNamespace('CModel_');
		}

		/**
		 * Initialize the db.
		 */
		protected function _initMyDb() {
			//bootstrap the db
			$this->bootstrap('db');
			Zend_Registry::set('db', $this->getResource('db'));
		}

	}