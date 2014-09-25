<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Database Resultset Transformer
Framework File Signature: com.ph2.framework.php.core.dbresultsettransformer
Description:
This class handles result sets retrieved by a get()-call on a TABLE.
For example, results can be converted to a dropbox selection, a table
body or similar.
---
/*/

//+
class ResultSetTransformer
{
	// INSTANCE VARS
	// -------------
	protected $_result_set;
	private $_is_empty;
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $resultset )
	/*/
	A ResultSetTransformer is constructed with a result set.
	---
	@param resultset: the resultset yielded by
	TABLE->get()
	@type  resultset: array
	/*/
	{
		assert(is_array($resultset));
		if (empty($resultset)) {
			$this->_is_empty = TRUE;
		} else {
			$this->_is_empty = FALSE;
			$this->_result_set = $resultset;
		}
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function toDropdownSelection ( $names , $values , $preselection=FALSE , $none_selection=FALSE )
	/*/
	Produces a dropdown selection body
	(name/value) with two specified columns of the resultset.
	---
	@param names: the name of the column where the
	names should be extracted
	@type  names: string
	@param values: the name of the column where the
	values should be extracted
	@type  values: string
	@param preselection: if
	provided, the default selection is applied to the item where
	value==preselection
	@type  preselection: string
	@param none_selection: if TRUE, a 'none'-option will be included at first place.
	@type  none_selection: bool
	-
	@return: the dorpdown body (list of
	option-tags)
	@rtype:  string
	/*/
	{
		// return empty list indicator if resultset is empty
		if (empty($this->_result_set)) {
			return '<option value="0" disabled="disabled" selected="selected">(empty)</option>' . "\n";
		}
		
		// otherwise, compose list body
		$dropdown_body = '';
		// include 'none'-option if desired
		if ($none_selection) $dropdown_body .= '<option value="none">(none)</option>\n';
		foreach ($this->_result_set as $row) {
			$selected = '';
			if ($preselection && $row[$values] == $preselection) $selected = ' selected="selected"';
			$dropdown_body .= '<option value="' . $row[$values] . '"' . $selected . '>' . $row[$names] . "</option>\n";
		}
		
		return $dropdown_body;
		
	} //toDropdownSelection
	
	//+ 
	function toHTMLTable ( $columns , $selectorCol=NULL , $selectorName='id' , $id='' , $classes=array() , $hidden_columns=NULL , $select_all=FALSE )
	/*/
	Produces a whole HTML table element with a
	dedicated header, optionally equipped with checkboxes for each row.
	---
	@param columns: the columns that
	should be used of the given resultset. the format is array
	('nameInResultset' => 'Name in Table'). if 'all' is submitted,
	all columns are extracted from the result sets first
	row.
	@type  columns: array/string
	@param selectorCol: the name of
	the row (out of the resultset) that should serve as checkbox ID.
	If Null, no selectors will be attached.
	@type  selectorCol: string
	@param selectorName: the name of
	the selector (form element)
	@type  selectorName: string
	@param id: the id to be assigned to
	the table
	@type  id: string
	@param classes: additional
	classes to add to the table element. format: array ('class1',
	'class2', ...)
	@type  classes: array
	@param hidden_columns: the index 0..n of the columns that should be hidden via 
	display:none.
	@type  hidden_columns: array(int)
	@param select_all: TRUE if a select all checkbox should be inserted; FALSE otherwise.
	@type  select_all: bool
	-
	@return: the html code of the table
	@rtype:  string
	/*/
	{
		
		if ($hidden_columns==NULL) {
			$hidden_columns = array();
		}
		
		if ($select_all) {
			$select_all_class = rand();
		}
		
		// check if resultset is empty
		if ($this->_is_empty) {
			$classes[] = 'rstable';
			$classes[] = 'empty';
			// prepare class and id tags
			if ($id!='') $id = ' id="' . $id . '"';
			$class = ' class="' . expandArray($classes, ' ') . '"';
			return '<table' . $id . $class . '><tr><td>(empty)</td></tr></table>';
		}
		
		// prepare columns if all columns selected
		if ($columns == 'all') {
			$columns = array();
			foreach ($this->_result_set[0] as $field => $value) {
				$columns[$field] = $field;
			}
		}
		
		// add default classes
		$classes[] = 'rstable';
		if ($selectorCol) $classes[] = 'selectors';
		
		// prepare class and id tags
		if ($id!='') $id = ' id="' . $id . '"';
		$class = ' class="' . expandArray($classes, ' ') . '"';
		
		// html header
		$html = "<table$id$class><thead><tr>";
		
		// expand table header
		if ($selectorCol) {
			$html .= '<th>';
			if ($select_all) $html .= '<input type="checkbox" class="select_all" rel="' . $select_all_class . '" name=""/>';
			$html .= '</th>';
		}
		$i = 0;
		foreach ($columns as $col_name) {
			$hidden = '';
			if( in_array($i, $hidden_columns) ) {
				$hidden = ' style="display:none;"';
			}
			$html .= "<th$hidden>$col_name</th>";
			$i++;
		}
		$html .= "</tr>";
		
		// table body
		$html .= "</thead><tbody>";
		foreach ($this->_result_set as $row) {
			$tr = '<tr>';
			if ($selectorCol) {
				$tr .= '<td><input type="checkbox" name="' . $selectorName . '[]" value="' . $row[$selectorCol] . '" class="' . $select_all_class . '" /></td>';
			}
			$i = 0;
			foreach ($columns as $dbname => $tablename) {
				$hidden = '';
				if( in_array($i, $hidden_columns) ) {
					$hidden = ' style="display:none;"';
				}
				$tr .= "<td$hidden>" . $row[$dbname] . '</td>';	
				$i++;		
			}
			$tr .= '</tr>';
			$html .= $tr; // append row to html
		}
		
		// table final footer
		$html .= '</tbody></table>';
		
		return $html;
		
	} //toHTMLTable
	
	//+ 
	function toSelectableHTMLTable ( $columns , $selectorCol=NULL , $selectorName='id' , $id='' , $classes=array() , $hidden_columns=NULL , $select_all=FALSE )
	/*/
	wrapper for toHTMLTable with
	additional class 'clickable' for overall table element.
	---
	@param columns: the columns that should be used of
	the given resultset. the format is array ('nameInResultset' =>
	'Name in Table')
	@type  columns: array
	@param selectorCol: the name of
	the row (out of the resultset) that should serve as checkbox ID.
	If Null, no selectors will be attached.
	@type  selectorCol: string
	@param selectorName: the name of
	the selector (form element)
	@type  selectorName: string
	@param id: the id to be assigned to
	the table
	@type  id: string
	@param classes: additional
	classes to add to the table element. format: array ('class1',
	'class2', ...)
	@type  classes: array
	@param hidden_columns: the index 0..n of the columns that should be hidden via 
	display:none.
	@type  hidden_columns: array(int)
	@param select_all: TRUE if a select all checkbox should be inserted; FALSE otherwise.
	@type  select_all: bool
	-
	@return: the html code of the table
	@rtype:  string
	/*/
	{
		$classes[] = 'selectable';
		return $this->toHTMLTable($columns, $selectorCol, $selectorName, $id, $classes, $hidden_columns);
	} //toSelectableHTMLTable
	
	// PRIVATE FUNCTIONS
	// -----------------
	
}

?>