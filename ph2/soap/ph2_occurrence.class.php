<?php

require_once('../../settings.php');
require_once('../framework/php/framework.php');

class PH2Occurrence {
	
	public $occurrenceID;
	public $surface;
    public $mainLemma;
	public $lemma;
	public $lemmaPOS;
	public $morphology;
	public $divisio;
	public $sigel;
	public $year;
	public $date;
	public $scripta;
	public $scriptorium;
	public $url;
	
	public function __construct( $occurrenceID, $withContext = FALSE , $empty = FALSE) {
		
	}
}
?>