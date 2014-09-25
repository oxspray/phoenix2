<?php
/* Phoenix2
** Project Lead: Martin-Dietrich Glessgen, University of Zurich
** Code by: Samuel Läubli, University of Zurich
** Contact: samuel.laeubli@uzh.ch
** ===
** This is the ph2 basic action handler. It is called upon each site call where $get['action'] != NULL and
** calls the corresponding function stored in actions.php.
** ---
** In the ph2 site call routine, the action handler is involved as follows:
** 1. Include the php framework
** 2. Unserialize or create the PH2_SESSION
** 3. Call the action handler (this file)
**    -> the action handler may manipulate session entries etc. and thus influence which site is loaded
** 4. Regular follow-up (loading module, etc.)
*/

if($_GET['action']) {
	require_once 'actions.php';
	if ($_GET['action'] == 'jq') {
		// jquery AJAX requests
		require_once 'jq_actions.php';
		$_GET['action'] = $_GET['task'];
	}
	call_user_func($_GET['action'], fixBoolArray($_GET), $_POST);
}

?>