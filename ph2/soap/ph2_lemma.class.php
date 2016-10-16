<?php

class PH2Lemma{

	public $id;
    public $mainLemma;
    public $lemma;
	public $concept;
	public $projectID;

    /**
     * @param $lemma lemma to copy some values from.
     */
	public function __construct( $lemma) {
        $this->mainLemma = $lemma->getMainLemmaIdentifier();
        $this->lemma = $lemma->getIdentifier();
        $this->id= $lemma->getID();
        $this->concept = $lemma->getConcept();
        $this->projectID = $lemma->getProjectID();
    }
}
?>