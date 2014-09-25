<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: XML Text Tokenizer
Framework File Signature: com.ph2.framework.php.entities.xmltexttokenizer
Description:
Class for tokenizing txt-sections of gl-deeds (xml texts). Allows definition of in-token- 
(will be part of a token) and off-token-stream- (text inside those will not be tokenized)  
xml-tags.
---
/*/

//+
class XMLTextTokenizer
{
	// INSTANCE VARS
	// -------------
	protected $INTERPUNCTUATION_CHARS;
	protected $IN_TOKEN_TAGS;
	protected $OFF_STREAM_MARKERS;
	protected $INTER_WORD_SEPARATOR_CHARS;
	protected $REPLACEMENTS;
	public $off_token_stream;
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( )
	{
		//definitions
		$this->INTERPUNCTUATION_CHARS = array( '.', ',', ':', ';', '|', '?' );
		$this->IN_TOKEN_TAGS = array ( "maj", "abr", "zwt", "sup" );
		$this->OFF_STREAM_MARKERS = array ( "fue", "ful" );
		$this->INTER_WORD_SEPARATOR_CHARS = array ( '-', '\'' );
		$this->REPLACEMENTS = array ( "<rest>\s*" => '[', "\s*<\/rest>" => ']' );
		
		//regex parts: expanding
		$exceptions = "";
		$in_token_tags_alternative = "";
		foreach ($this->IN_TOKEN_TAGS as $tag) {
			$exceptions .= '\/?' . $tag . '|';
			$in_token_tags_alternative .= $tag . '|';
		}
		$this->exceptions = rtrim($exceptions, '|');
		$this->in_token_tags_alternative = rtrim($in_token_tags_alternative, '|');
		
		$offstream = "";
		foreach ($this->OFF_STREAM_MARKERS as $tag) {
			$offstream .= '\/?' . $tag . '|';
		}
		$this->offstream = rtrim($offstream, '|');
		
		$separators = "";
		foreach ($this->INTER_WORD_SEPARATOR_CHARS as $sep) {
			$separators .= $sep . '|';
		}
		$this->separators = rtrim($separators, '|');
		
		$interpunctuation = "";
		foreach ($this->INTERPUNCTUATION_CHARS as $punct) {
			$interpunctuation .= "\\" . $punct . '|'; 
		}
		$this->interpunctuation = $interpunctuation . '(?<!<)\/(?!>)'; // special hack: / cannot be preeceeded by < or followed by >
		
		$this->ip_chars = $this->interpunctuation . '|' . $this->separators;
		
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	protected function _preprocess ( $text )
	/*/
	Runs a number of preprocessing routines on the input text. Assembles _pre_-functions of 
	this class. The preprocessing steps result in a text that is splittable by whitespaces and 
	thus passable to the _tokenize-function.
	---
	@param text: the input text
	@type  text: string
	-
	@return: the output text
	@rtype:  string
	/*/
	{
		$text = $this->_pre_replacements($text);
		$text = $this->_pre_seperate_tags($text);
		
		// replace line breaks by whitespaces
		$text = preg_replace("/\s+/", ' ', $text);
		
		// replace whitespaces after opening or before closing IN_TOKEN_TAGS
		$text = preg_replace("/<($this->in_token_tags_alternative)>\s+/", "<$1>", $text);
		$text = preg_replace("/\s+<\/($this->in_token_tags_alternative)>/", "</$1>", $text);
		
		// temporarily replace whitespaces within xml tags (to avoid them to be splitted into seperate token candidates)
		// the replacement sign chain is: <<*>>
		$text = preg_replace_callback("/(<[^>]*>)/", array($this, '_add_replacements'), $text);	
		
		return $text;
	
	} //_preprocess
	
	//+ 
	protected function _add_replacements ( $text )
	/*/
	replaces whitespaces inside xml tags with a given character sequence to avoid splitting
	---
	@param text: the input text
	@type  text: string
	-
	@return: the output text
	@rtype:  string
	/*/
	{
		return str_replace(' ', '<<*>>', $text[0]);
	} //_add_replacements
	
	//+ 
	protected function _remove_replacements ( $text )
	/*/
	reverses the replacements made by $this->_add_replacements
	---
	@param text: the input text
	@type  text: string
	-
	@return: the output text
	@rtype:  string
	/*/
	{
		return str_replace('<<*>>', ' ', $text);
	} //_remove_replacements
	
	//+ 
	protected function _pre_replacements ( $text )
	/*/
	Replaces given character sequences with their provided substitutes (regex; according to 
	REPLACEMENTS).
	---
	@param text: the input text
	@type  text: string
	-
	@return: the output text
	@rtype:  string
	/*/
	{
		foreach ($this->REPLACEMENTS as $orig => $repl) {
			$text = preg_replace('/' . $orig . '/', $repl, $text);
		}
		return $text;
	
	} //_pre_replacements
	
	//+ 
	protected function _pre_seperate_tags ( $text )
	/*/
	Splits word candidates from xml-tags by inserting a whitespace character. Exceptions 
	according to IN_TOKEN_TAGS.
	E.g. abbe(fue) => abbe (fue); (maj)a(/maj)bbe => (maj)a(/maj)bbe (=exception; no change)
	---
	@param text: the input text
	@type  text: string
	-
	@return: the output text
	@rtype:  string
	/*/
	{
		$text = preg_replace("/(<(?!($this->exceptions))[^>]*>)/", " $0 ", $text);
		return trim($text);
	
	} //_pre_seperate_tags
	
	//+ 
	protected function _seperate_word ( $word )
	/*/
	Splits word candidates if they contain seperators (according to 
	INTER_WORD_SEPARATOR_CHARS).
	E.g. l'abbe => l ' abbe
	---
	@param word: the input text
	@type  word: string
	-
	@return: the output text
	@rtype:  string
	/*/
	{
		$word = preg_replace("/($this->separators)/", " $0 ", $word);
		return $word;
		
	} //_seperate_word
	
	//+ 
	protected function _seperate_interpunction ( $word )
	/*/
	Seperates punctuation signs from words by inserting a whitespace character.
	E.g. abbe,/. => abbe ,/.
	---
	@param word: the input text
	@type  word: string
	-
	@return: the output text
	@rtype:  string
	/*/
	{
		/* punctuation signs within IN_TOKEN_TAGS must be handled carefully
		** the according expansion is done here
		** e.g. q(abr)u'i(/abr)l => q(abr)u(/abr) ' (abr)i(/abr)l
		*/
		foreach ($this->IN_TOKEN_TAGS as $tag) {
			$is_expanded=FALSE;
			$expansion_regex = "/<$tag>((.(?!>))*)(($this->ip_chars)+)((.(?!>))*)<\/$tag>/";
			if (preg_match($expansion_regex, $word)) {
				$is_expanded = TRUE;
				$word = preg_replace($expansion_regex, "<$tag>$1</$tag> $3 <$tag>$5</$tag>", $word);
				//clear empty sequences (<abr></abr> => (null))
				$word = preg_replace("/<$tag><\/$tag>/", '', $word);
			}
		}
		
		$word = preg_replace("/(\[?($this->interpunctuation)+\]?)/", " $0 ", $word);
		
		return $word;
	} //_seperate_interpunction
	
	//+ 
	public function tokenize ( $text )
	/*/
	Tokenizes an input text and returns its new (xml-tokenized) representation.
	---
	@param text: the untokenized input xml text
	@type  text: string
	-
	@return: the tokenized xml text
	@rtype:  string
	/*/
	{
		$new_text = ''; //the new xml text string to be assembled
		
		$text = $this->_preprocess($text);
		
		$token_candidates = explode(' ', $text);
		foreach ($token_candidates as $candidate) {
			// a candidate may still need further splitting
			$candidate = $this->_seperate_interpunction($candidate);
			$candidate = $this->_seperate_word($candidate);
			$subcandidates = explode(' ', $candidate);
			foreach ($subcandidates as $token_candidate) {
				if ($this->_isToken($token_candidate)) {
					$new_text .= $this->_makeToken($token_candidate);
				} else {
					$new_text .= $token_candidate;
				}
				$new_text .= ' '; // add a whitespace in the end as the text was previously splitted by whitespaces
			}
		}
		
		$new_text = $this->_remove_replacements($new_text); // reverse the replacements from the preprocessing
		#DEBUG
		#echo "<br /><br />\n\n$new_text\n\n<br /><br />";
		return $new_text;
		
	} //tokenize
	
	//+ 
	protected function _isToken ( $candidate )
	/*/
	Checks if a given string (token candidate) is a token (by definition). This function also 
	invokes the isOffTokenStreamMarker-function to check whether tokenization is currently 
	turned off for this sequence.
	---
	@param candidate: the token candidate
	@type  candidate: string
	-
	@return: TRUE if the token matches the token definition, FALSE otherwise
	@rtype:  bool
	/*/
	{
		if ($this->_isOffTokenStreamMarker($candidate)) {
			// off-stream-marker: adjust tokenizer mode
			$tag_name = $this->_getTagName($candidate);
			if ($this->off_token_stream == $tag_name) {
				// end off-stream mode
				unset($this->off_token_stream);
			} else {
				$this->off_token_stream = $tag_name;
			}
			return FALSE;		
		} else if (empty($this->off_token_stream)) {
			// interpunctuation: if $candidate purely consists of interpunctuation characters
			preg_match("/($this->ip_chars)+/", $candidate, $matches);
			if ($matches[0] == $candidate) {
				return FALSE;
			} else {
				if (preg_match("/<(?!($this->exceptions))/", $candidate)) {
					return FALSE;
				} else {
					// true token
					return TRUE;
				}
			}
		} else {
			return FALSE;
		}
	} //_isToken
	
	//+ 
	protected function _isOffTokenStreamMarker ( $candidate )
	/*/
	Checks if a token candidate marks the beginning or end of an xml tag whose content should 
	not be tokenized. Affects $this->off_token_stream.
	---
	@param candidate: the token candidate
	@type  candidate: string
	-
	@return: TRUE if the candidate is an off token stream marker, FALSE otherwise
	@rtype:  bool
	/*/
	{
		if (preg_match("/<(?=($this->offstream))/", $candidate)) {
			return TRUE;
		}
		return FALSE;
	
	} //_isOffTokenStreamMarker
	
	//+ 
	protected function _makeToken ( $input_string )
	/*/
	Takes a string and frames it with the token-markup.
	E.g. abbe => (wn)abbe(/wn)
	---
	@param input_string: the string to be framed
	@type  input_string: string
	-
	@return: the framed input string (=annotated token)
	@rtype:  string
	/*/
	{
		return "<wn>$input_string</wn>";
	} //_makeToken
	
	//+ 
	function _getTagName ( $input_string )
	/*/
	Extracts the tag name out of an xml tag.
	E.g. (/hans n=23) => hans
	---
	@param input_string: the xml string
	@type  input_string: string
	-
	@return: the name of the xml tag
	@rtype:  string
	/*/
	{
		preg_match( "/<\/?([A-Za-z0-9]+)/", $input_string, $matches);
		return trim($matches[0], '</');
	} //_getTagName
	
	// PRIVATE FUNCTIONS
	// -----------------
	
}

?>