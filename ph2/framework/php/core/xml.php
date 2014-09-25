<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Functions for dealing with XML input
Framework File Signature: com.ph2.framework.php.core.xml
Description:
Various functions to handle XML parsing/transformation
---
/*/

//+ 
function getNodeText ( $xml_node , $clean=TRUE )
/*/
Takes (a node of) an XML DomDocument and
returns its text content. If $clean is set to TRUE, all xml-tags inside
the child will be removed.
---
@param xml_node: the node from which the text should be
extracted
@type  xml_node: XML DomDocument Node
@param clean: if TRUE, all xml-tags
(including their content) inside the text will be removed
@type  clean: bool
/*/
{
	$text = $xml_node->asXML();
	if ($clean) {
		$text = strip_tags($text);
	}
	
	return $text;
} //getNodeText

//+ 
function stripOutermostTag ( $xml_string )
/*/
strips the outermost tag from an xml string
---
@param xml_string: the xml string
@type  xml_string: string
-
@return: the xml string without its outermost tag-pair
@rtype:  string
/*/
{
	$left_position = strpos($xml_string, '>');
	$right_position = strrpos($xml_string, '<');
	$xml_string = substr($xml_string, 0, $right_position);
	$xml_string = substr($xml_string, $left_position + 1);
	return $xml_string;
} //stripOutermostTag

//+ 
function xmlpp ( $xml , $as_html_output=FALSE )
/*/
Prettifies an XML string into a human-readable and indented work of art. Proposed by 
http://www.thedeveloperday.com/xml-beautifier-tool/.
---
@param xml: The XML as a string
@type  xml: string
@param as_html_output: True if the output should be escaped (for use in HTML)
@type  as_html_output: bool
/*/
{
	//$xml_obj = new SimpleXMLElement($xml);  
	//$xml_string = $xml_obj->asXML();
	
    $bc = new BeautyXML();
	$result = $bc->format($xml);
	
    return ($as_html_output) ? htmlspecialchars($result) : $result;  
	
} //xmlpp

//+ 
function printXML ( $xml_string , $id , $pretty_print=TRUE , $tags=TRUE , $compact=FALSE , $colors=FALSE , $part='ALL' )
/*/
Takes an XML string and optional styling parameters. Prints an HTML representation of the 
XML string.
---
@param xml_string: the xml to be prettified and printed
@type  xml_string: string
@param id: the ID that the printet html code element shall receive
@type  id: int
@param pretty_print: if TRUE, the XML will be indented by xmlpp()
@type  pretty_print: bool
@param tags: whether to include the XML tags. If FALSE, only the text of the xml nodes 
will be returned.
@type  tags: bool
@param compact: if TRUE, all new lines, tabs and whitespaces outside of the tags will be 
removed
@type  compact: bool
@param colors: if TRUE, syntax highlighting will be enabled (via gcp)
@type  colors: bool
@param part: the name of the root tag to take from the submitted xml. Default ALL selects 
the whole xml.
@type  part: bool
/*/
{
	// OUTPUT: [<pre>]<code class="xml [highglighted]">(the xml)</code>[</pre>]
	
	/* SEQUENTIAL; the sequence of editing steps is:
	** 1. Convert to DOM Object
	** 2. Take relevant part of the XML ($part)
	** 3. Strip tags if required ($tags)
	** 4. Convert to string
	** 5. Pretty format (indentation)
	** 6. Remove new-lines, tabs and whitespaces ($compact)
	** 7. Replace HTML Special Chars
	** 8. Add color-class ($colors)
	*/
	
	// 0. default variables
	$code_classes = array('xml');
	
	// 1. Convert to DOM Object
	$xml_obj = new SimpleXMLElement($xml_string);
	
	// 2. Take relevant part of the XML ($part)
	if ($part != 'ALL') {
		// deal with default namespace
		$namespaces = $xml_obj->getDocNamespaces();
    	$xml_obj->registerXPathNamespace('__empty_ns', $namespaces['']);
		$xml_obj = $xml_obj->xpath('//__empty_ns:' . $part);
		if (empty($xml_obj)) {
			die ("'$part' is not a valid subnode of the submitted XML.");
		} else {
			$xml_obj = $xml_obj[0]; // only first matching node is used!
		}
	}
	
	// 3. Strip tags if required ($tags), 4. Convert to string
	if (!$tags) {
		$xml_string = getNodeText($xml_obj);
	} else {
		// 5. Pretty format (indentation)
		($pretty_print) ? $xml_string = xmlpp($xml_obj->asXML()) : $xml_string = $xml_obj->asXML();
		//die($pretty_print);
		// 6. Remove new-lines, tabs and whitespaces ($compact)
		if ($compact) {
			// FIX: currently only replaces sequences of n whitespaces by 1 whitepace
			$xml_string = preg_replace('/\s{2,}/', ' ', $xml_string);
		}
	}
	
	// 7. Replace HTML Special Chars
	$xml_string = html_entity_decode( $xml_string, ENT_NOQUOTES, 'UTF-8');
	$xml_string = htmlspecialchars($xml_string);
	
	// 8. Add Color Class
	if ($colors and $tags) {
		$code_classes[] = 'highlighted';
	}
	
	// PRINT
	$html = '<code id="' . $id . '" class="' . expandArray($code_classes, ' ') . '">';
	$html .= $xml_string;
	$html .= '</code>';
	
	if (!$compact && $tags) {
		$html = '<pre>' . $html . '</pre>';
	}
	
	echo $html;
	
} //printXML

?>