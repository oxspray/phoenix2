<?php
/* Phoenix2
** Project Lead: Martin-Dietrich Glessgen, University of Zurich
** Code by: Samuel Läubli, University of Zurich
** Contact: samuel.laeubli@uzh.ch
** ===
** This is the ph2 index main file. All modules are loaded here, except for the login
** procedure, which is outsourced in login.php.
*/

// Load the PHP framework
require_once('../settings.php');
require_once('framework/php/framework.php');

// Session
session_start();
isset($_SESSION[PH2_SESSION_KEY]) ? $ps = unserialize($_SESSION[PH2_SESSION_KEY]) : $ps = new PH2Session();

// Check whether User is logged in and has rights to view this page (#TODO:refine)
if(!$ps->isLoggedIn()) {
	if ($_GET['user'] == 'guest') {
		// visit main.php?user=guest to use Phoenix2 as a guest (readonly)
		$ps->logIn('guest', 'guest');
	} else {
		$ps->redirect(PH2_REF_LOGIN);
	}
}

#$ps->logout();
#print_r($ps);

// Action Handler
include_once('actions/php/actionhandler.php');

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Phoenix2</title>
<!-- CSS Framework -->
<?php
	if ($ps->getNickname() == 'guest') {
		echo '<link href="framework/css/ph2_external.css" rel="stylesheet" type="text/css" />';
	} else {
		echo '<link href="framework/css/ph2.css" rel="stylesheet" type="text/css" />';	
	}
?>
<!-- JS/jQuery Framework -->
<script type="text/javascript" src="framework/js/jquery/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="framework/js/jquery/jquery-ui-1.8.16.custom.min.js"></script>
<script type="text/javascript" src="framework/js/jquery/jquery-scrollTo-min.js"></script>
<script type="text/javascript" src="framework/js/jquery/jquery.ba-resize.min.js"></script>
<script type="text/javascript" src="framework/js/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<script type="text/javascript" src="framework/js/jquery/framework.js"></script>
<script type="text/javascript" src="framework/js/jquery/ph2components.js"></script>
<script type="text/javascript" src="framework/js/jquery/ph2controllers.js"></script>
</head>
<body>
<div class="overall">
	
    <div class="page">
    	
        <div id="top">
        	<span>
				<?php htmlUserTopBar($ps); ?>
            </span>
            <div id="top_right">
				<?php htmlTopCorpusSelection(); ?>
            </div>
            <a id="header_show" href="#" title="show header"<?php if($ps->getGUIShowHeader()) echo ' class="hidden"'; ?>></a>
        </div>
        
        <div id="header"<?php if(!$ps->getGUIShowHeader()) echo ' class="hidden"'; ?>>
        	<div class="w80">
            	<div class="inner10">
                	<a href="?action=redirect&module=home" title="Exit module and return to dashboard (home screen)">
                        <h1>Phoenix2</h1>
                        <h2>Web-Based Annotation</h2>
                   	</a>
                </div>
        	</div>
            <div class="w20">
            	<div class="inner10 rightbound">
					<?php $version = getVersionInfo() ?>
                    <p>Version <?php echo $version['version'] . ' ' . $version['status']; ?>
                    <br />Build <?php echo $version['build']; if ($_SESSION['isGuest']) echo '(read only)'; ?></p>
                </div>
        	</div>
            <a id="header_hide" href="#" title="hide header"<?php if(!$ps->getGUIShowHeader()) echo ' class="hidden"'; ?>></a>
        </div>
        
        <div id="module">
        	<!-- module -->
            <?php include $ps->getCurrentModulePath(); ?>
            <!-- end module -->
        </div>
        
    </div>
    
</div>
</body>
</html><?php /* Save ph2session */ $ps->save(); ?>