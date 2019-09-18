<?php
	
	namespace Controllers;

	class View
	{
		private $iaVariables 			= array();
		private $istrNotFoundTemplate 	= '';
		
		/* CONSTRUCTOR */
		
		public function __construct()
		{
			$this->istrNotFoundTemplate = VIEW_INCLUDE_PATH . '404.php';
		}
		
		/* PUBLIC METHODS */
		
		/**
		 * Show template by template name
		 *
		 * @param string $pstrTemplate
		 */
		public function showTemplate($pstrTemplate)
		{
			if(!@isset($pstrTemplate) || !@file_exists(VIEW_INCLUDE_PATH . $pstrTemplate))
			{
				$this->showPageNotFound();
			}
			
			foreach($this->iaVariables as $lstrVariable => $lmValue)
			{
				$$lstrVariable = $lmValue; 
			}
				
			header('Content-Type: text/html; charset=utf-8');
			@include_once(VIEW_INCLUDE_PATH . $pstrTemplate);
		}
		
		/**
		 * Show template page not found
		 */
		public function showPageNotFound()
		{
			header("HTTP/1.0 404 Not Found");
			
			if(@file_exists($this->istrNotFoundTemplate))
			{
				@include_once($this->istrNotFoundTemplate);
			}
			
			exit();
		}
		
		/**
		 * Assign a variable to the View
		 * 
		 * @param string $pstrVariable
		 * @param mixed $pmValue
		 */
		public function assign($pstrVariable, $pmValue)
		{
			$this->iaVariables[$pstrVariable] = $pmValue;
		}
<<<<<<< HEAD:controllers/view.class.php
		
		/* PRIVATE METHODS */
		
		/**
		 * Check if template file exists
		 *
		 * @param string $pstrTemplate
		 */
		private function templateExists($pstrTemplate)
		{
			return (@file_exists($pstrTemplate) && is_file($pstrTemplate));
		}
		
		/**
		 * Render template
		 * Create assignments and include template 
		 *
		 * @param string $pstrTemplate
		 * @param int $pnStatusCode
		 */
		private function renderTemplate($pstrTemplate, $pnStatusCode = 200)
		{
			foreach($this->iaVariables as $lstrVariable => $lmValue)
			{
				$$lstrVariable = $lmValue; 
			}
				
			$this->setStatusCodeHeader($pnStatusCode);
			
			@include_once($pstrTemplate);
		}
		
		/**
		 * Set Status code Header
		 *
		 * @param int $pnStatusCode
		 */
		private function setStatusCodeHeader($pnStatusCode)
		{
			$lstrStatusCode = (@isset($this->iaStatusCodes[$pnStatusCode])) ? $this->iaStatusCodes[$pnStatusCode] : '200 OK';
			
			header("HTTP/1.0 " . $lstrStatusCode);
		}
=======
>>>>>>> tmp:controllers/viewcontroller.class.php
	}