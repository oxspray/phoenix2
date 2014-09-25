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

/*
$c = new Corpus(3);
$c->delete();
*/

/*
$t = new Text(1505);
$t->checkin($xml);
*/
/*
$t = new Text(3);
print_r($t->getTextDescriptors( array('d0', 'rd0') ));
*/

/*
$t = new Table('Occurrence');
$t->insert( array('OccurrenceID' => 55632, 'TokenID' => 1, 'TextID' => 1, 'Order' => 10000 ) );
$t->insert( array('OccurrenceID' => 55634, 'TokenID' => 1, 'TextID' => 1, 'Order' => 10000 ) );
$rows = array();
$rows[] = array('TokenID' => 1, 'TextID' => 1, 'Order' => 10 );
$rows[] = array('TokenID' => 1, 'TextID' => 1, 'Order' => 11 );
$rows[] = array('TokenID' => 1, 'TextID' => 1, 'Order' => 12 );
$rows[] = array('TokenID' => 1, 'TextID' => 1, 'Order' => 13 );
$rows[] = array('TokenID' => 1, 'TextID' => 1, 'Order' => 14 );
$rows[] = array('TokenID' => 1, 'TextID' => 1, 'Order' => 15 );
$t->insertRowsAtLowestPossibleID('OccurrenceID',$rows);
*/

/*
$l = new Lemma('fragen-1','c', $ps->getActiveProject(), 'fragen');
echo $l->getID() . '<br/>';
echo $l->getIdentifier() . '<br/>';
echo $l->getSurface() . '<br/>';
echo $l->getConcept() . '<br/>';
print_r( $l->getMorphAttributes());
echo ('<br/>');

$l->setMorphAttribute('gen','f');
$l->setMorphAttribute('cas','obl');
print_r( $l->getMorphAttributes());
echo ('<br/>');

$l->removeMorphAttribute('cas');
print_r( $l->getMorphAttributes());
echo ('<br/>');

$l->removeMorphAttributes();
print_r( $l->getMorphAttributes());
echo ('<br/>');
*/

/*
$text = new Text(1655);
$edit_dom = $text->_getEditXML();
echo $edit_dom->saveXML();
*/

/*$hash1 = checkoutTextOrCorpus ( 'text' , 3 );
$hash2 = checkoutTextOrCorpus ( 'text' , 3 );

echo checkinTextOrCorpus($hash1);
echo checkinTextOrCorpus($hash2);

$hash3 = checkoutTextOrCorpus ( 'text' , 4 );
$hash4 = checkoutTextOrCorpus ( 'text' , 6 );*/

//echo validateCheckoutIdentifier('c70a91f49e9f9220c2cc80760645b4ea');
//print_r( analyseXMLFile('data/xml/temp/edit_text.xml') );

//$ps->addFilter('corpus', '1');
//$ps->removeFilter('type', 'vente');

//print_r($ps);

//print_r(findOccurrences(array(1,2), NULL, NULL, NULL, NULL, NULL, NULL, array(1)));

//$m = new POSMorphManager();
//echo $m->addTagset( array( 'n'=>array( 'genus'=>array( 'm', 'f'), 'numerus'=>array('sg', 'pl')), 'adj'=>array()) );

/*
$dao = new Table('TOKEN');
$i = 0;
foreach ($dao->get() as $row) {
	$surf = $row['Surface'];
	if (preg_match("/^.$/u", $surf)) {
		echo($surf . "<br />\n");
		$i++;
	}
}
echo "<br /><br />\n\nCount: $i";
*/

//$g = new Graph(303);
//$g->deleteGraphgroup(319);
//$t->removeOccurrences( array(11078, 12463) );


/*
$g = new Graph('ableeer654', 'lat. blabla', 'the comment is so long as i wish it to be...');
$graphgroup_id = $g->addGraphgroup('2.16.');
$graphgroup = new Graphgroup($graphgroup_id);
$graphgroup->addOccurrence( array(1,2,3,4,5,6) );
*/

/*
$i = new Image(69);
$i->linkToText(100);
print_r( $i->getAssignedTexts() );
$i->removeFromText(100);
print_r( $i->getAssignedTexts() );
echo $i->getID();
*/

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
	<?php print_r(encodePassword('tree-tagger')); ?>
</body>
</html><?php /* Save ph2session */ $ps->save(); ?>