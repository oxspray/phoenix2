<?php
/* Phoenix2
** Project Lead: Martin-Dietrich Glessgen, University of Zurich
** Code by: Samuel Läubli, University of Zurich
** Contact: samuel.laeubli@uzh.ch
** ===
** This is an action method collection aimed at jquery AJAX requests.
*/

/*
SETTERS
*/
function setSessionShowHeader ( $get, $post ) { global $ps;
/* sets the corresponding session value */
	
	// ASSERTIONS
	assert(!empty($post['visible']));
	
	// ROUTINE
	$ps->setGUIShowHeader($post['visible']);
}

/*
GETTERS
-
Getters die with a result page to be handled by jQuery (AJAX get requests)
*/
function getSessionShowHeader ( $get, $post ) { global $ps;
/* get the corresponding session value */
	
	die(
		print(json_encode(array( "showHeader"=>$ps->getGUIShowHeader())))
	);

}

function getXMLText ( $get, $post ) { global $ps;
/* gets an xml text and returns it within a <code> oder <pre><code>-block */
// quasi-wrapper for printXML();
	
	// ASSERTIONS
	assert($get['textID']);
	assert($get['elemID']); // the id of the <code> element in the html code to be returned
	if (!isset($get['part'])) {
		$get['part'] = 'ALL';
	}
	
	// load text
	$text = new Text( (int) $get['textID'] );
	
	// return result
	die(
		printXML($text->getXML(), $get['elemID'], $get['prettyPrint'], $get['tags'], $get['compact'], $get['colors'], $get['part'])
	);

}

function getSessionActiveLemma ($get, $post) { global $ps;
/* Returns the LemmaID of the Lemma that is currently active in the user session (PH2Session)
   Format: Int */
   
   # TEST STUB
   die('5');
   //die($ps->getActiveLemma());

}
?>