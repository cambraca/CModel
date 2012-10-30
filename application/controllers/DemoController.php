<?php

	class DemoController extends Zend_Controller_Action
	{

		public function indexAction()
		{
			$this->view->authors = AuthorMapper::singleton()->findAll();
		}

		public function authorAction()
		{
			$this->view->author = AuthorMapper::singleton()->find($this->_getParam('id'));
		}

	}