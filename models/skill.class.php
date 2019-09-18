<?php

	namespace Models;
	
	/* LOAD DEPENDECIES */
<<<<<<< HEAD
	require_once('presenters/skill.class.php');
=======
	require_once('models/skilldto.class.php');
>>>>>>> tmp
	
	/**
	 * Skill Class which contains the skill information.
	 */
	class Skill extends BaseModel
	{
		/* CONSTRUCTOR */
		
		public function __construct($poSkill)
		{
			$this->extend($poSkill);	
		}
		
		/* PUBLIC METHODS */
		
		public function get()
		{
			return new \Presenters\Skill($this);
		}
	}