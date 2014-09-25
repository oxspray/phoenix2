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

// PLAYGROUND
$xml = '<txt><maj>C\'</maj>est deniers chascun an, selonc <zw/> ce q<abr>u\'</abr>[ele] C\'est à savoir q<abr>u\'i</abr>l su<abr>n</abr>t</txt>';

$t = new XMLTextTokenizer();
echo $t->tokenize($xml);





?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Phoenix2 Framework Test Playground</title>
</head>

<body>
	<?php //print_r($p); ?>
</body>
</html><?php /* Save ph2session */ $ps->save(); ?>