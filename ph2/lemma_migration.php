<?php
/* Phoenix2
** Project Lead: Martin-Dietrich Glessgen, University of Zurich
** Code by: Samuel Läubli, University of Zurich
** Contact: samuel.laeubli@uzh.ch
** ===
** This is a playground for framework function tests.
*/

// Load the PHP framework
require_once('../settings.php');
require_once('framework/php/framework.php');

// Session
session_start();
isset($_SESSION[PH2_SESSION_KEY]) ? $ps = unserialize($_SESSION[PH2_SESSION_KEY]) : $ps = new PH2Session();

// get a list of all Occurrences in the OLD system who were assigned a Lemma
$occurrences = array();
$dao = new Table('mig_LEMMA_OLD');
foreach ($dao->get() as $row) {
	$occurrences[] = $row;	
}
unset($dao);

// add new OccurrenceID
$dao = new Table('mig_OCCURRENCE');
for ($i=0; $i<count($occurrences); $i++) {
	$rows = $dao->get( array('wn' => $occurrences[$i]['WNR']) );
	$occurrences[$i]['OccurrenceID'] = $rows[0]['OccurrenceID'];
	print_r($occurrences[$i]);
}
unset($dao);



?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Phoenix2 Framework Test Playground</title>
<script type="text/javascript" src="framework/js/jquery/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="framework/js/jquery/jquery-ui-1.8.16.custom.min.js"></script>
<script type="text/javascript" src="framework/js/jquery/jquery-scrollTo-min.js"></script>
<script type="text/javascript" src="framework/js/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<script type="text/javascript" src="framework/js/jquery/framework.js"></script>
<script type="text/javascript" src="framework/js/jquery/ph2components.js"></script>
<script type="text/javascript" src="framework/js/jquery/ph2controllers.js"></script>

<script type="text/javascript">
	
</script>
</head>

<body>

</body>
</html><?php /* Save ph2session */ $ps->save(); ?>