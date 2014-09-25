<?php
/* Phoenix2
** Project Lead: Martin-Dietrich Glessgen, University of Zurich
** Code by: Samuel Läubli, University of Zurich
** Contact: samuel.laeubli@uzh.ch
** ===
** This is the ph2 login page. Successful login results in a redirection to index.php
** after the regular login procedures (session start etc.).
*/

// Load the PHP framework
require_once('../settings.php');
require_once('framework/php/framework.php');

// Session
session_start();
isset($_SESSION[PH2_SESSION_KEY]) ? $ps = unserialize($_SESSION[PH2_SESSION_KEY]) : $ps = new PH2Session();

// If user is allready logged in, redirect to main module
// Check whether User is logged in and has rights to view this page (#TODO:refine)
if($ps->isLoggedIn()) {
	$ps->redirect(PH2_REF_USERHOME);
}

// Check Login Fields
if($_POST) {
	$ps->logIn($_POST['nick'], $_POST['pass']);
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Phoenix2</title>
<link href="framework/css/ph2.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div class="overall">
	
    <div class="page">
    	<div id="login_header">
        	<h1>Phoenix2</h1>
            <h2>Web-Based Annotation</h2>
        </div>
        <div id="login_box">
        <?php // unpack all login notifications
		foreach ($ps->notifications->popScope('login') as $note) {
			echo('<p id="loginmessage">'. $note->getText() . "</p>\n");
		}
		?>
        	<form name="login" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            	<fieldset>
                	<label for="nick">Username</label>
                    <input type="text" class="text normal" name="nick" />
                    <label for="pass">Password</label>
                    <input type="password" class="text normal" name="pass" />
                </fieldset>
                <input type="submit" class="button" name="login" value="Sign In" />
            </form>
        </div>     
    </div>
    
</div>
</body>
</html><?php /* Save ph2session */ $ps->save(); ?>