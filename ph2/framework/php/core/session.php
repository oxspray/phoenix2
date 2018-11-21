<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: PH2 Session Object
Framework File Signature: com.ph2.framework.php.core.session
Description:
This is the overall class for a PH2 user session. A corresponding
object should be created upon every first page load of the phoenix login
site (or any site, as they will redirect towards that site in case no
user is logged in). The PH2Session object is created or unpacked on each
site just after loading the framework. At the very end of each page
script, it is then serialized and stored in a predefined regular php
session.
---
/*/

//+
class PH2Session
{
	// INSTANCE VARS
	// -------------
	public $notifications;
	protected $_user;
	protected $_timestamp_start;
	protected $_timestamp_login;
	protected $_current_module;
	protected $_active_project;
	protected $_active_corpus;
	protected $_active_lemma;
	protected $_active_grapheme;
	protected $_gui_show_header;
	private $_is_logged_in;
	private $_filters;
	private $_filter_is_active;
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( )
	{
		$this->_timestamp_start = now();
		$this->notifications = new NotificationStack();
		$this->_gui_show_header = 1;
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function logIn ( $nick , $pw )
	/*/
	Attempts to find an entry in the USER table
	matching the given nickname and password (resp. its hash). In case
	of success, a USER object is stored in this session and a
	redirection to $PH2_STD_PAGE_LOGGEDIN is triggered. Otherwise, FALSE
	is returned (meaning $pw and $nick do not match an existing user
	entry).
	---
	@param nick: the user's nickname
	@type  nick: string
	@param pw: the user's password
	@type  pw: string
	/*/
	{
		// get user values from database
		$tb_USER = new Table('sys_USER');
		$users = $tb_USER->get(array('Nickname' => $nick));
		
		// check nickname/password combination
		if ($users[0]) {
			if (checkPassword($pw, $users[0]['Password'], $users[0]['PasswordSalt'])) {
				// successful login
				$this->_user = new User($users[0]['UserID']);
				$this->_is_logged_in = TRUE;
				$this->_timestamp_login = now();
				$this->_filters = array(); //empty = no filters by default
				$this->_filter_is_active = FALSE; //no filter by default = restrictions are not acitvated by default
				$this->setActiveProject(1); // TEMP
				$this->setActiveCorpus(1); // TEMP
				$this->setActiveLemma(NULL); // TEMP
				$this->setActiveGrapheme(NULL); // TEMP
				//$this->backupDatabase();
				// simple identifier for guest
				session_start();
				if ($this->getNickname() == 'guest') {
					$_SESSION['isGuest'] = TRUE;
				} else {
					$_SESSION['isGuest'] = FALSE;
				}
				$this->redirect(PH2_REF_USERHOME);
			}
		}
		
		// login not successful
		$this->notifications->push( new Notification('Username and password are not valid. Please try again.', 'err', 'login') );
		$this->redirect(PH2_REF_LOGIN);
		
	} //logIn
	
	//+ 
	function logout ( )
	/*/
	Destroys this session (thus logging out the
	current user) and redirects to the login page.
	/*/
	{
		session_unset(); 
		session_destroy();
		$this->redirect(PH2_REF_LOGIN);
	} //logout
	
	//+ 
	function save ( )
	/*/
	Serializes this object and saves it in $_SESSION
	in order to be reloaded on another page.
	/*/
	{
		$_SESSION[PH2_SESSION_KEY] = serialize($this);
	} //save
	
	//+ 
	function redirect ( $location )
	/*/
	Sends HTTP headers to redirect the browser to
	another page (url = $location). Redirects should ALWAYS be performed
	by calling this function as it is important to first serialize the
	session object etc.
	---
	@param location: the target page
	@type  location: string
	/*/
	{
		/* TODO:
		function redirectOnNextPageLoad for delayed redirects when page content has 
		allready been loaded. The init-Function of this object should then immediately 
		redirect without performing other actions (?).
		*/
		
		// save all settings (serialize this object)
		$this->save();
		
		// send the headers and exit
		header("Location:$location");
		exit();
		
	} //redirect
	
	//+ 
	function updateUser ( )
	/// updates the user information of the session
	{
		
		$this->_user = new User ($this->_user->getID());
				
	} //updateUser
	
	//+ 
	function getUserID ( )
	/*/
	getter
	-
	@return: this user's
	ID
	@rtype:  string
	/*/
	{
		return $this->_user->getID();
	} //getUserID
	
	//+ 
	function getNickname ( )
	/*/
	getter
	-
	@return: this user's
	nickname
	@rtype:  string
	/*/
	{
		return $this->_user->getNickname();
	} //getNickname
	
	//+ 
	function getFullname ( )
	/*/
	getter
	-
	@return: this user's
	full name
	@rtype:  string
	/*/
	{
		return $this->_user->getFullname();
	} //getFullname
	
	//+ 
	function getPrivilege ( )
	/*/
	getter
	-
	@return: this user's
	privilege type
	@rtype:  string
	/*/
	{
		return $this->_user->getPrivilege();
	} //getPrivilege
	
	//+ 
	function getMail ( )
	/*/
	getter
	-
	@return: this user's mail
	address
	@rtype:  string
	/*/
	{
		return $this->_user->getMail();
	} //getMail
	
	//+ 
	function setCurrentModule ( $signature )
	/*/
	setter
	---
	@param signature: the new signature
	@type  signature: string
	/*/
	{
		$this->_current_module = $signature;
	} //setCurrentModule
	
	//+ 
	function getCurrentModule ( )
	/*/
	getter
	-
	@return: returns
	the signature of the currently loaded module
	@rtype:  string
	/*/
	{
		return $this->_current_module;
	} //getCurrentModule
	
	//+ 
	function getCurrentModulePath ( )
	/*/
	getter
	-
	@return: returns the relative path of the currently loaded
	module
	@rtype:  string
	/*/
	{
		return getPathFromSignature($this->getCurrentModule());
	} //getCurrentModulePath
	
	//+ 
	function setActiveProject ( $project_id )
	/*/
	setter
	---
	@param project_id: the project id of the active project
	@type  project_id: int
	/*/
	{
		$this->_active_project = $project_id;
	} //setActiveProject
	
	//+ 
	function getActiveProject ( )
	/*/
	getter
	-
	@return: returns
	the ID of the active project
	@rtype:  string
	/*/
	{
		return $this->_active_project;
	} //getActiveProject
	
	//+ 
	function setActiveCorpus ( $corpus_id )
	/*/
	setter
	---
	@param corpus_id: the project id of the active corpus
	@type  corpus_id: int
	/*/
	{
		$this->_active_corpus = $corpus_id;
	} //setActiveCorpus
	
	//+ 
	function getActiveCorpus ( )
	/*/
	getter
	-
	@return: returns
	the ID of the active corpus
	@rtype:  string
	/*/
	{
		return $this->_active_corpus;
	} //getActiveCorpus
	
	//+ 
	function setActiveLemma ( $lemma_id )
	/*/
	setter
	---
	@param lemma_id: the LemmaID of the active lemma
	@type  lemma_id: int
	/*/
	{
		$this->_active_lemma = $lemma_id;
	} //setActiveLemma
	
	//+ 
	function getActiveLemma ( )
	/*/
	getter
	-
	@return: returns the ID of the active lemma
	@rtype:  int
	/*/
	{
		return $this->_active_lemma;
	} //getActiveLemma
	
	//+ 
	function setActiveGrapheme ( $graph_id )
	/*/
	setter
	---
	@param graph_id: the GraphID of the active Grapheme
	@type  graph_id: int
	/*/
	{
		$this->_active_grapheme = $graph_id;
	} //setActiveGrapheme
	
	//+ 
	function getActiveGrapheme ( )
	/*/
	getter
	-
	@return: returns the ID of the active Grapheme
	@rtype:  int
	/*/
	{
		return $this->_active_grapheme;
	} //getActiveGrapheme
	
	//+ 
	function setGUIShowHeader ( $value )
	/*/
	setter
	---
	@param value: whether the header is visible or not
	@type  value: bool
	/*/
	{
		assert(is_bool($value));
		$this->_gui_show_header = $value;
	} //setGUIShowHeader
	
	//+ 
	function getGUIShowHeader ( )
	/*/
	getter
	-
	@return: whether
	the header is visible or not
	@rtype:  bool
	/*/
	{
		return $this->_gui_show_header;
	} //getGUIShowHeader
	
	//+ 
	function isLoggedIn ( )
	/*/
	getter
	-
	@return: true if a user
	is logged into the current session, false otherwise
	@rtype:  bool
	/*/
	{
		return $this->_is_logged_in;
	} //isLoggedIn
	
	//+ 
	function filterIsEmpty ( )
	/*/
	returns true if no filter is stored in the session
	-
	@return: whether any filter is stored in the session or not
	@rtype:  bool
	/*/
	{
		if (count( $this->getFilterIncludedTexts() ) == 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	} //filterIsEmpty
	
	//+ 
	function addFilter ( $filter_name , $filter_value )
	/*/
	adds a filter to the session. The corresponding TextIDs are automatically computed
	---
	@param filter_name: the filter's name
	@type  filter_name: string
	@param filter_value: the filter's value (collects criteria that MUST BE MET, i.e., only 
	return texts that have that value)
	@type  filter_value: string
	/*/
	{
		// add base entry for filter (if not exists)
		if (!array_key_exists($filter_name, $this->_filters)) {
			$this->_filters[$filter_name] = array();
		} else {
			if (array_key_exists($filter_value, $this->_filters[$filter_name])) {
				return TRUE; // in this case, the filter is already active
				#TODO: ev. re-load filter due to DB-modifications in the meantime (?)
			}
		}
		$this->_filters[$filter_name][$filter_value] = array();
		// retrieve associated TextIDs
		// find out what kind of filter is applied
		$dao = new Table('DESCRIPTOR');
		$available_descriptors = array();
		foreach ($dao->get() as $descriptor) {
			$available_descriptors[$descriptor['XMLTagName']] = $descriptor['DescriptorID'];
		}
		if ( startsWith($filter_name, 'd0') ) {
			// DATE
			$filter_parts = explode('-', $filter_name);
			$dao = new Table('TEXT_DESCRIPTOR');
			switch ($filter_parts[1]) {
				case 'from': $dao->where = 'DescriptorID = ' . $available_descriptors['d0'] . ' and Value >= ' .$filter_value;
				break;
				case 'to': $dao->where = 'DescriptorID = ' . $available_descriptors['d0'] . ' and Value <= ' .$filter_value;
				break;
				// if there is a problem parsing the value, don't store the filter
				default: return FALSE;
			}
			// delete old entries
			$this->_filters[$filter_name] = array();
			$rows = $dao->get();
			if(!empty($rows) && is_array($rows)){
				foreach ($rows as $row) {
					$this->_filters[$filter_name][$filter_value][] = $row['TextID'];
				}
			}
		
		} else if (array_key_exists($filter_name, $available_descriptors)) {
			// filter of value in DESCRIPTOR Table (see DB)
			$dao = new Table('TEXT_DESCRIPTOR');
			$dao->where = array('DescriptorID' => $available_descriptors[$filter_name], 'Value' => $filter_value);
			foreach ($dao->get() as $row) {
				$this->_filters[$filter_name][$filter_value][] = $row['TextID'];
			}
		} else if ($filter_name == 'corpus') {
			// filter by CorpusID
			$dao = new Table('TEXT');
			$dao->from = 'TEXT natural join CORPUS';
			$dao->where = array('Name' => $filter_value);
			foreach ($dao->get() as $row) {
				$this->_filters[$filter_name][$filter_value][] = $row['TextID'];
			}
			
		} else {
			// filter was not recognized
			return FALSE;
		}
		
		return TRUE;
		
	} //addFilter
	
	//+ 
	function removeFilter ( $filter_name , $filter_value=NULL )
	/*/
	removes a filter from the session
	---
	@param filter_name: the filter's name
	@type  filter_name: string
	@param filter_value: the filter's value
	@type  filter_value: string
	/*/
	{
		if ($filter_value == NULL) {
			// remove a whole filter
			unset($this->_filters[$filter_name]);
		} else {
			// remove a subfilter
			unset($this->_filters[$filter_name][$filter_value]);		
		}
		
	} //removeFilter
	
	//+ 
	function getActiveFilterIDs ( )
	/*/
	returns the IDs (=names) of all active filters
	-
	@return: the active filters
	@rtype:  array(str)
	/*/
	{
		return array_keys($this->_filters);
	} //getActiveFilterIDs
	
	//+ 
	function getFilterIncludedTexts ( )
	/*/
	returns all TextIDs that are currently included, i.e., it iterates to all subfilters and 
	creates a unique list of TextIDs
	-
	@return: the TextIDs included by the filter
	@rtype:  array(int)
	/*/
	{
		$included_text_ids = array();
		$is_first_filter = TRUE;
		
		foreach ($this->_filters as $filter) {
			
			$filter_text_ids = array();
			foreach ($filter as $value) {
				if (is_array($value)) {
					foreach($value as $text_id) {
						if (!in_array($text_id, $filter_text_ids)) {
							$filter_text_ids[] = $text_id;
						}
					}
				}
			}
			if ($is_first_filter) {
				$included_text_ids = $filter_text_ids;
				$is_first_filter = FALSE;
			} else {
				// the set of selected texts is the intersection of all included texts from the previous filters and the current filter's texts
				$included_text_ids = array_intersect( $included_text_ids, $filter_text_ids );
			}
			
		}
		
		return $included_text_ids;
		
	} //getFilterIncludedTexts
	
	//+ 
	function getFilterValues ( $filter )
	/*/
	returns all values that are currently included in a filter
	---
	@param filter: the name of the filter
	@type  filter: string
	-
	@return: the values that are activated for @param filter
	@rtype:  array(str)
	/*/
	{
		if ($this->_filters[$filter]) {
			return array_keys($this->_filters[$filter]);
		}
	} //getFilterValues
	
	function getFilters(){
		return $this->_filters;
	}
	
	//+ 
	function filterIsActive ( )
	/*/
	getter
	-
	@return: whether the filter is actiavted in the session
	@rtype:  bool
	/*/
	{
		return $this->_filter_is_active;
	} //filterIsActive
	
	//+ 
	function setFilterIsActive ( $active )
	/*/
	setter
	---
	@param active: true/false
	@type  active: bool
	/*/
	{
		$this->_filter_is_active = $active;
	} //setFilterIsActive
	
	function backupDatabase (  )
	/*/
	Backup database once a day
	---
	/*/
	{
		$filename = date('d-m-Y').".sql.gz";
		$filepath = BK_FP . "/" . $filename;
		//print $filepath;
		//die();
		$cmd = "mysqldump -u".PH2_DB_USER." -p".PH2_DB_PASSWORD." ".PH2_DB_NAME." --no-create-info --no-create-db | gzip > ".$filepath;
		shell_exec($cmd);
		chmod($filepath, 0777);
	}
	
	
	// PRIVATE FUNCTIONS
	// -----------------
	
	
}

?>