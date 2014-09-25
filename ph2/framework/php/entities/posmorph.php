<?php
/*/
Phoenix2
Version 0.6 Alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: POS-Morphology
Framework File Signature: com.ph2.framework.php.entities.posmorph
Description:
Classes for handling POS-Morphology (tagset management and assignments)
---
/*/

//+
class POSMorphManager
{
	// INSTANCE VARS
	// -------------
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function addPOS ( $symbol , $name , $description=NULL )
	/*/
	Adds a POS-Tag to the system's morphology, which is subsequently available for tagging.
	---
	@param symbol: the symbol for the new tag, consisting of up to 10 characters; e.g. "vfin".
	@type  symbol: string
	@param name: the name for the new tag, consisting of up to 45 characters; i.e. "finite 
	verb"
	@type  name: string
	@param description: the tag's description
	@type  description: string
	-
	@return: the ID of the newly generated entry (MorphPOSID)
	@rtype:  int
	/*/
	{
		$dao = new Table("MORPHPOS");
		return $dao->checkAdd( array( "Symbol"=>$symbol, "Name"=>$name, "Descr"=>$description ) );
	} //addPOS
	
	//+ 
	function addAttribute ( $name , $description=NULL )
	/*/
	Adds an attribute to the system (= set of all available attributes), i.e. a slot to be 
	filled by a valid attribvalue. E.g. for a verb, attributes could be "numerus", "tempus", 
	etc.
	If the attribute allready exists, the ID of the existing entry will be returned.
	---
	@param name: the name for the new tag, consisting of up to 45 characters; e.g. "numerus"
	@type  name: string
	@param description: the attribute's description
	@type  description: string
	-
	@return: the ID of the newly generated (or existing) entry (MorphAttribID)
	@rtype:  int
	/*/
	{
		$dao = new Table("MORPHATTRIB");
		return $dao->checkAdd( array( "Name"=>$name, "Descr"=>$description ) );
	} //addAttribute
	
	//+ 
	function addValue ( $value )
	/*/
	Adds an attribvalue to the system (= set of all available values), i.e. a slot-filler like 
	"3", "person3" or the like.
	If the attribvalue allready exists, the ID of the existing entry will be returned.
	---
	@param value: the attribvalue, consisting of up to 45 characters; e.g. "3"
	@type  value: string
	-
	@return: the ID of the newly generated (or existing) entry (MorphValueID)
	@rtype:  int
	/*/
	{
		$dao = new Table("MORPHVALUE");
		return $dao->checkAdd( array( "Value"=>$value ) );
	} //addValue
	
	//+ 
	function linkAttributeToPOS ( $attrib_id , $pos_id , $order , $isObligatory=TRUE , $isCiteFormRelevant=TRUE )
	/*/
	Links an attribute to a POS-tag, transitively linking the corresponding attribvalues; e.g. 
	"numerus" to "verb".
	---
	@param attrib_id: the ID of the attribute to be linked to the POS-tag (MorphAttribID)
	@type  attrib_id: int
	@param pos_id: the ID of the POS-tag (MorphPOSID)
	@type  pos_id: int
	@param order: the order of the attribute. all attributes are sorted in an ascending 
	fashion, i.e. 0 = leftmost attribute.
	@type  order: int
	@param isObligatory: whether a corresponding quality must be specified for an entry
	@type  isObligatory: bool
	@param isCiteFormRelevant: whether this attribute should be included in the citation form 
	of morphological information
	@type  isCiteFormRelevant: bool
	/*/
	{
		/* Note: the set af all attribvalues transitively linked to a pos-tag via an attribute must be unique,
		** i.e. if numerus => {1, 2, 3} and genus => {1, 2}, only one of the two attribs can be linked; this would
		** work, however: numerus => {1, 2, 3} and genus => {m, f} etc.
		*/
		
		$dao = new Table('MORPHATTRIB_MORPHVALUE');
		$dao_pos = new Table("MORPHPOS_MORPHATTRIB");
		
		// check if the entry allready exists
		if ($dao_pos->get(array("MorphAttribID"=>$attrib_id, "MorphPOSID"=>$pos_id))) {
			echo ("Warning: Attribute $attrib_id is allready linked to POS-tag $pos_id. No new link is added.");
			return;
		}
		
		// first, get the set of all attribvalues connected to the submitted attribute
		$dao->select = 'MorphAttribID';
		$dao->from = 'MORPHATTRIB_MORPHVALUE natural join MORPHVALUE';
		$new_value_ids = $dao->get( array( "MorphAttribID"=>$attrib_id ) );
		
		// then, get all AttribValueIDs of the values allready transitively connected to the POS-Tag
		$dao_pos->select = "MorphAttribID";
		$pos_attrib_ids = $dao_pos->get( array( "MorphPOSID"=>$pos_id) );
		
		$pos_value_ids = array();
		foreach ($pos_attrib_ids as $id) {
			foreach ($dao->get(array("MorphAttribID"=>$id['MorphAttribID'] )) as $pos_value_id) {
				$pos_value_ids[] = $pos_value_id;
			}
		}
		
		// abort if any of the values of the new attribute are allready (transitively) connected to the POS-tag
		foreach ($new_value_ids as $new_id) {
			if (in_array($new_id, $pos_value_ids)) {
				// #TODO: refine (useful exception handling)
				die("Cannot add attribute $new_id to POS $pos_id. The new attribute is linked to values that are allready transitively linked to the POS-tag via another attribute.");
				return; //useless, of course
			}
		}
		
		
		// otherwise, link the attribute to the pos
		$dao_pos->insert( array( "MorphPOSID"=>$pos_id, "MorphAttribID"=>$attrib_id, "ORDER"=>$order, "isObligatory"=>$isObligatory, "isCiteFormRelevant"=>$isCiteFormRelevant ) );
		return TRUE;
		
	} //linkAttributeToPOS
	
	//+ 
	function linkValueToAttribute ( $value_id , $attrib_id )
	/*/
	Links an attribvalue to an attribute; e.g. "3" to "numerus".
	---
	@param value_id: the ID of the attribvalue to be linked to the attribute (MorphValueID)
	@type  value_id: int
	@param attrib_id: the ID of the attribute (MorphAttribID)
	@type  attrib_id: int
	-
	@return: the ID of the linked pair (MorphAttrib_MorphValueID)
	@rtype:  int
	/*/
	{
		$dao = new Table("MORPHATTRIB_MORPHVALUE");
		return $dao->checkAdd( array( "MorphAttribID"=>$attrib_id, "MorphValueID"=>$value_id ) );
	} //linkValueToAttribute
	
	//+ 
	function addTagset ( $tagset )
	/*/
	Adds a whole tagset to the System. The format is (in Arrays):
	(POS1 => ("attribute1"=>("value1", "value2", ...), "attribute2"=> ...), POS2 => ...)
	Note: Sets Name=Symbol (MOPRPHPOS)
	$example = array( 'n'=>array( 'genus'=>array( 'm', 'f'), 'numerus'=>array('sg', 'pl')), 
	'adj'=>array());
	---
	@param tagset: the array representation of the tagset
	@type  tagset: arrays (see above)
	/*/
	{
		foreach ($tagset as $pos_symbol=>$pos_content) {
			$pos_id = $this->addPOS($pos_symbol, $pos_symbol);
			foreach ($pos_content as $attrib_name=>$attrib_values) {
				$order = 1;
				$attrib_id = $this->addAttribute($attrib_name);
				foreach ($attrib_values as $value) {
					$val_id = $this->addValue($value);
					$this->linkValueToAttribute($val_id, $attrib_id);
				}
				$this->linkAttributeToPOS($attrib_id, $pos_id, $order);
				$order++;
			}
		}
		
	} //addTagset
	
	// PRIVATE FUNCTIONS
	// -----------------
	
}

//+
class POSMorphAssigner
{
	// INSTANCE VARS
	// -------------
	public $input_xml; /// helau
	
	// PUBLIC FUNCTIONS
	// ----------------
	// PRIVATE FUNCTIONS
	// -----------------
	
}

?>