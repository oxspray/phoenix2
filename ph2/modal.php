<?php
/* Phoenix2
** Project Lead: Martin-Dietrich Glessgen, University of Zurich
** Code by: Samuel Läubli, University of Zurich
** Contact: samuel.laeubli@uzh.ch
** ===
** This is the ph2 modal main file. All modal (= layer windows) modules are loaded here.
*/

// Load the PHP framework
require_once('../settings.php');
require_once('framework/php/framework.php');

// Session
session_start();
isset($_SESSION[PH2_SESSION_KEY]) ? $ps = unserialize($_SESSION[PH2_SESSION_KEY]) : $ps = new PH2Session();

// Check whether User is logged in and has rights to view this page (#TODO:refine)
if(!$ps->isLoggedIn()) {
	die('Error displaying modal window: You are not logged in.');
}

// Action Handler
include_once('actions/php/actionhandler.php');

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Phoenix2</title>
<!-- CSS Framework -->
<link href="framework/css/ph2.css" rel="stylesheet" type="text/css" />
<!-- JS/jQuery Framework -->
<script type="text/javascript" src="framework/js/jquery/framework.js"></script>
</head>
<body>
	<div id="modal">
    	<?php
		// Load the modal part here (include).
		assert($_GET['modal']); // says which modal to load
		// this file must be called like 'modal.php?modal=add_corpus' => add_corpus.modal.php is loaded
		include ('includes/modals/' . $_GET['modal'] . '.modal.php');
		?>
    </div>
</body>
</html><?php /* Save ph2session */ $ps->save(); ?>