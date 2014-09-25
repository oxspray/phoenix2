<?php
/* Phoenix2
** Project Lead: Martin-Dietrich Glessgen, University of Zurich
** Code by: Samuel Läubli, University of Zurich
** Contact: samuel.laeubli@uzh.ch
** ===
** This is the ph2 basic ajax handler for saving form data. This site can be called directly, echoing json data (etc.)
** requested by $_GET['action'] and further params.
** ---
*/
session_start();
require_once('../../../settings.php');
require_once('../../framework/php/framework.php');
isset($_SESSION[PH2_SESSION_KEY]) ? $ps = unserialize($_SESSION[PH2_SESSION_KEY]) : $ps = new PH2Session();

if($_GET['action']) {
	call_user_func($_GET['action'], fixBoolArray($_GET), $_POST);
}

// helpers

function expand_array_to_fields ( $array, $asJSON=TRUE ) {
// expands a number of key=>value pairs to an array of arrays (format for loading arrays of fields/values)
	$result = array();
	foreach($array as $field_name=>$field_value) {
		$result[] = array('name'=>$field_name, 'value'=>$field_value);
	}
	if ($asJSON) {
		return json_encode($result);
	} else {
		return $result;
	}
}

// end helpers

function ann_gra_gra_details_load ($get, $post) { global $ps;

	$graph_id = (int)$get['graphID'];
	assert($graph_id);
	
	$graph = new Graph($graph_id);
	
	$fields = array( 'Name'=>$graph->getName(), 'Description'=>$graph->getDescription(), 'Comment'=>$graph->getComment() );
	echo expand_array_to_fields($fields);

}

function ann_gra_gra_details_save ($get, $post) { global $ps;

	$graph_id = (int)$get['graphID'];
	assert($graph_id);
	
	$graph = new Graph($graph_id);
	
	$graph->setName($post['Name']);
	$graph->setDescription($post['Description']);
	$graph->setComment($post['Comment']);

}

function ann_gra_grp_variants_load ($get, $post) { global $ps;

	$graphgroup_id = (int)$get['graphgroupID'];
	assert($graphgroup_id);
	
	$graphgroup = new Graphgroup($graphgroup_id);
	
	$fields = array( 'Name'=>$graphgroup->getName(), 'Number'=>$graphgroup->getNumber() );
	echo expand_array_to_fields($fields);

}

function ann_gra_grp_variants_save ($get, $post) { global $ps;
	
	$graphgroup_id = (int)$get['graphgroupID'];
	assert($graphgroup_id);
	
	$graphgroup = new Graphgroup($graphgroup_id);
	
	$graphgroup->setName($post['Name']);
	$graphgroup->setNumber($post['Number']);
	
	echo 'Variant details saved successfully.';

}

?>