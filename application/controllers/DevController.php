<?php

	/**
	 * Pages for developers. Doesn't work when the app environment is "production".
	 * @package Dev
	 * @subpackage Controllers
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	class DevController extends Zend_Controller_Action
	{
		
		public function init()
		{
			parent::init();
/*
			$ajaxContext = $this->_helper->getHelper('AjaxContext');
			$ajaxContext
				->addActionContext('addComment', 'json')
				->initContext();*/
		}

		/**
		 * Override normal checks (i.e. allow anonymous users).
		 * Checks if the app env is production.
		 */
		public function preDispatch()
		{
			if (APPLICATION_ENV == 'production')
				throw new PT_Exception_NotAvailableInProd();
			
			$this->_check_company = FALSE;
			$this->_check_login = FALSE;
			
			parent::preDispatch();
		}
		
		public function addCommentAction()
		{
			$mapper = CModel_DevWireframeMapper::singleton();
			$w = $mapper->getByAction($c = $this->_getParam('c'), $a = $this->_getParam('a'));
			$w->addComment($_POST['comment'], ($u = CModel_User::getCurrentUser()) ? $u->username : $_POST['author']);
			$mapper->save($w);
			//CModel_Dev::d(array('c' => $c, 'a' => $a, 'w' => $w, 'post' => $_POST)); exit;
			$this->_helper->redirector($a, $c);
		}
		
		
		/** Uploads assets (files) to wireframes
		*
		*/
		 public function addAssetAction() 
		 {
		 	//$this->view->headTitle('Home');
			//$this->view->title = 'Zend_Form_Element_File Example';
			//$this->view->bodyCopy = "<p>Please fill out this form.</p>";
			
			$form = new forms_UploadwfForm();
			
			if ($this->_request->isPost()) {
				$formData = $this->_request->getPost();
				if ($form->isValid($formData)) {
				

					// success -
					$uploadedData = $form->getValues();
					$fullFilePath = $form->file->getFileName();
					$mapper = CModel_DevWireframeMapper::singleton();
					$w = $mapper->getByAction($as = $this->_getParam('as'), $a = $this->_getParam('a'));
					$w->addComment($fullFilePath, ($u = CModel_User::getCurrentUser()) ? $u->username : $_POST['author']);
					$mapper->save($w);

				} else {
					$this->_helper->redirector($a, $as);
				}
			}

			$this->view->form = $form;
		
		}
		
		
		/**
		 * Generates the code for a domain model class and its mapper
		 */
		public function codeAction()
		{
			$table = $this->getRequest()->getParam('table');
			if (is_null($table))
				die('"table" param required!');
			
			$db = Zend_Registry::get('db');
			
			$describe = $this->view->describe = $db->fetchAll('DESCRIBE `'.$table.'`');
			
			$this->view->table = strtolower($table);
			$this->view->tableCamelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
			
			$fks = array();
			$enums = array();
			foreach ($describe as $row)
			{
				if (
					substr($row['Field'], -3) == '_id'
					&& $row['Type'] == 'int(10) unsigned'
				)
				{
					$fks[$row['Field']] = array( //for example: $fks['plan_id'] = array('table' => 'plan', 'tableCamelCase' => 'Plan');
						'table' => substr($row['Field'], 0, -3)
					);
					
					$fks[$row['Field']]['tableCamelCase'] = str_replace(' ', '', ucwords(str_replace('_', ' ', $fks[$row['Field']]['table'])));
					
					$fks[$row['Field']]['Null'] = $row['Null'];
				}
				
				if (substr($row['Type'], 0, 4) == 'enum')
				{
					$enums[$row['Field']] = array();
					foreach (explode(',', substr($row['Type'], 5, -1)) as $value)
						$enums[$row['Field']][trim($value, '\'')] = strtoupper($row['Field'].'_'.trim($value, '\''));
				}
			}
			
			$this->view->fks = $fks;
			$this->view->enums = $enums;
		}

		/**
		 * Shows the session and links for dev actions.
		 */
		public function indexAction()
		{
			echo '<div class="span-24 last">';
			CModel_Dev::d($_SESSION, 'SESSION:');
			echo '</div>';
		}

		/**
		 * Destroys the session.
		 */
		public function resetSessionAction()
		{
			Zend_Session::destroy(TRUE);
			die('Session destroyed. <a href="/dev">Dev tools</a> | <a href="/">Back to home</a>');
		}
		
		/**
		 * Run arbitrary php code.
		 */
		public function phpAction()
		{
			//load scripts from db
			Zend_View_Helper_PaginationControl::setDefaultViewPartial('pagination.phtml');
			$db = Zend_Registry::get('db');
			$select = $db->select()
				->from('dev_scripts')
				->order('create_dt DESC');
			$paginator = Zend_Paginator::factory($select);
			$paginator->setCurrentPageNumber($this->_getParam('page', 1));
			$this->view->paginator = $paginator;
			
			$request = $this->getRequest();

			$form = new Zend_Form();

			$form->addElement('text', 'name', array('label' => 'Script Name'));
			$form->addElement('textarea', 'code', array('label' => 'Code'));
			$form->addElement('submit', 'run', array('label' => 'Run!'));
			$form->addElement('submit', 'save', array('label' => 'Save'));

			if ($id = $request->getParam('id'))
			{
				$form->addElement('submit', 'new', array('label' => 'Save as New'));
				$form->addElement('submit', 'delete', array('label' => 'Delete'));

				$row = $db->fetchRow(
					$db->select()
						->from('dev_scripts')
						->where('id = ?', $id)
					);
				$form->setDefaults($row);
			}
			
			if ($request->isPost())
			{
				$post = $request->getPost();
				$form->setDefaults($post);
				
				if (isset($post['delete']))
				{
					$db->delete('dev_scripts', 'id = '.$id);
					$this->_helper->redirector('php', 'dev');
				}
				elseif (isset($post['save']))
				{
					if ($id)
					{
						$db->update('dev_scripts', array('name' => $post['name'], 'code' => $post['code']), 'id = '.$id);
					}
					else
					{
						$db->insert('dev_scripts', array('name' => $post['name'], 'code' => $post['code']));
						$this->_helper->redirector('php', 'dev', NULL, array('id' => $db->lastInsertId()));
					}
				}
				elseif (isset($post['new']))
				{
					$db->insert('dev_scripts', array('name' => $post['name'], 'code' => $post['code']));
					$this->_helper->redirector('php', 'dev', NULL, array('id' => $db->lastInsertId()));
				}
				elseif (isset($post['run']))
				{
					//the view runs the code
					$this->view->code = 'try {'.$post['code'].'} catch (Exception $e) {CModel_Dev::d($e, \'Error caught!\');}';
				}
			}
			
			$this->view->form = $form;
		}
		
		public function testsAction()
		{
			$m = new CModel_Message(array('text' => 'hey, this is an error message!', 'type' => CModel_Message::TYPE_ERROR));
			CModel_Dev::d($m);
			$this->addMessage($m);
		}
	}