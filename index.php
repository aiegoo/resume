<?php
	
<<<<<<< HEAD
	header('Content-Type: text/html; charset=utf-8');
	
	ob_start("ob_gzhandler");
	
=======
>>>>>>> tmp
	/* LOAD CONFIG */
	require_once('config.inc.php');
	
	/* DEPENDENCIES */
<<<<<<< HEAD
	require_once('libraries/router.class.php');
	require_once('libraries/orm.class.php');
	require_once('libraries/helper.class.php');
=======
	require_once('services/router.class.php');
	require_once('services/orm.class.php');
>>>>>>> tmp
	
	require_once('controllers/main.class.php');
	require_once('controllers/view.class.php');
	
	require_once('routes.inc.php');	
	
	/* MAIN CONTROLLER */
	$loMainController = new \Controllers\Main();
	