<?php
/*/
Phoenix2
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: settings.php
Description:
Defines base settings for the PHP framework. May be outsourced later.
Note:
This file is NOT managed via XML/ph2repo
---
/*/

// PATHS
define( 'PH2_REF_LOGIN',	"login.php"); // TODO: Define
define( 'PH2_REF_USERHOME',	"main.php?action=redirect&module=home"); // TODO: Define

// FILEPATHS
define( 'PH2_FP_BASE',		dirname(__FILE__) . '/ph2' ); // this is a bit clumsy; the framework file must not be moved!
define( 'PH2_FP_TEXT',		"data/xml/text"); // where xml texts are stored
define( 'PH2_FP_TEMP_TEXT',	"data/xml/temp"); // where temporary xml texts are stored (corpus uploads)
define( 'PH2_FP_MIGRATED_CORPORA',	"data/xml/migrated_corpora"); // where migrated corpora (as a whole) are stored
define( 'PH2_FP_MEDIA',		"data/media"); // where uploaded files are stored

// WEB PATHS to use within html source code
define( 'PH2_WP_BASE',			PH2_FP_BASE); // For the UZH-Server: PH2_FP_BASE
// Ressources
	define( 'PH2_WP_RSC',		PH2_WP_BASE . '/ressources');
	define( 'PH2_WP_RSC_ICON',	PH2_WP_RSC . '/icons');
// Includes
	define( 'PH2_WP_INC',		PH2_WP_BASE . '/includes');
	define( 'PH2_WP_MODAL',		PH2_WP_INC . '/modals');

// Database and encryption settings.
require_once('settings_db.php');

// SESSION
define( 'PH2_SESSION_KEY',	"ph2_session");

// XML URIs (-> XSD)
define( 'PH2_URI_ENTRY', 	'http://www.rose.uzh.ch/phoenix/schema/entry' ); 		//stored in /ressources/xsd/entry_{text|corpus}.xsd
define( 'PH2_URI_STORAGE', 	'http://www.rose.uzh.ch/phoenix/schema/storage' );	//stored in /ressources/xsd/storage_{text|corpus}.xsd
define( 'PH2_URI_EDIT', 	'http://www.rose.uzh.ch/phoenix/schema/edit' );			//stored in /ressources/xsd/edit_{text|corpus}.xsd


?>