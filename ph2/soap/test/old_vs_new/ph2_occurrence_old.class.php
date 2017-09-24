<?php

require_once('../../../../settings.php');
require_once('../../../framework/php/framework.php');

class PH2OccurrenceOld {

	public $occurrenceID;
	public $surface;
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

		if ($empty) {
			return;
		}

		$this->occurrenceID = $occurrenceID;

		if ($withContext) {
			// get occurrence details / context
			$details = getOccurrenceContext( $this->occurrenceID );

			$this->surface = $details['match'][0]['surface'];
			$this->lemma = $details['meta'][0]['lemma']; #TODO: change (separate)
			// trim() for comparison with new getOccurrence() version. (Fields are trimmed by soap anyway.)
			$this->contextLeft = trim($details['match'][0]['leftContext']);
			$this->contextRight = trim($details['match'][0]['rightContext']);
			$this->lemmaPOS = $details['meta'][0]['lemma_pos']; #TODO: change (separate)
			$this->morphology = ''; #TODO
			$this->divisio = $details['meta'][0]['divID'];
			$this->sigel = $details['meta'][0]['zitfFull'];
			$this->year = $details['meta'][0]['d0'];
			$this->date = $details['meta'][0]['d0Full'];
			$this->type = mb_substr($details['meta'][0]['type'], 0, 255);
			$this->scripta = $details['meta'][0]['scripta'];
			$this->scriptorium = $details['meta'][0]['rd0Full'];
			$this->url = 'http://www.rose.uzh.ch/docling/charte.php?t=' . $details['meta'][0]['textID'] . '&occ_order_number=' . $details['meta'][0]['order'];
		} else {
			// faster loading without occurrence context, directly from the DB
			$dao = new Table('OCCURRENCE');
			$dao->select = "O.OccurrenceID, O.TextID, O.Order, O.Div, T.Surface, TE.CiteID, XMLTagName as Descriptor, TD.Value as DescriptorValue, LemmaIdentifier, M.Value as MorphValue";
			$dao->from = "OCCURRENCE as O join TOKEN as T on O.TokenID=T.TokenID
							join TEXT as TE on O.TextID=TE.TextID
							join TEXT_DESCRIPTOR as TD on O.TextID=TD.TextID
							join DESCRIPTOR as D on TD.DescriptorID=D.DescriptorID
							left join LEMMA_OCCURRENCE as LO on O.OccurrenceID=LO.OccurrenceID
							left join LEMMA as L on LO.LemmaID=L.LemmaID
							left join LEMMA_MORPHVALUE as LM on L.LemmaID=LM.LemmaID
							left join MORPHVALUE as M on LM.MorphvalueID = M.MorphvalueID";
			$dao->where = "O.OccurrenceID=" . $this->occurrenceID;
			$rows = $dao->get();
			$morphvalues = array();
			$descriptors = array();
			for ($i=0; $i<count($rows); $i++) {
				if ($i==0) {
					$this->surface = $rows[$i]['Surface'];
					$this->lemma = $rows[$i]['LemmaIdentifier'];
					$this->morphology = ''; #TODO
					$this->divisio = $rows[$i]['Div'];
					$this->sigel = $rows[$i]['CiteID'];
					$this->url = 'http://www.rose.uzh.ch/docling/charte.php?t=' . $rows[$i]['TextID'] . '&occ_order_number=' . $rows[$i]['Order'];
				}
				if ( ! in_array($rows[$i]['MorphValue'], $morphvalues) ) {
					$morphvalues[] = $rows[$i]['MorphValue'];
				}
				if ( ! array_key_exists($rows[$i]['Descriptor'], $descriptors) ) {
					$descriptors[ $rows[$i]['Descriptor'] ] = $rows[$i]['DescriptorValue'];
				}
			}
			$this->year = substr($descriptors['d0'],0,4);
			$this->date = $descriptors['d0'];
			$this->type = mb_substr($descriptors['type'], 0, 255);
			$this->scripta = $descriptors['scripta'];
			$this->scriptorium = $descriptors['rd0'];
			foreach ($morphvalues as $morphvalue) {
				$this->morphology .= $morphvalue . '';
			}
		}

	}
}
?>