<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Authentication
Framework File Signature: com.ph2.framework.php.core.auth
Description:
User authentication routines (login, logout) including password
encryption.
---
/*/

//+ 
function encodePassword ( $pw )
/*/
Generates an sh1-encrypted password hash
alongside with a random salt and returns them for storage in a user
table.
---
@param pw: the password to be encoded
@type  pw: string
-
@return: 'pw_hash' => password hash value, 'salt_hash' =>
salt hash value
@rtype:  array
/*/
{
	// create salt
	$salt = '';
	$maxlen = strlen($pw);
	assert($maxlen > 0); // throws error if string is ''
	for ($i=0; $i < 3; $i++) {
		// salt is composed of three random substrings of the original password string plus a random number (each)
		$substr_start = mt_rand(0, $maxlen - 1);
		$substr_end   = mt_rand($substr_start, $maxlen);
		$salt .= substr($pw, $substr_start, $substr_end);
		$salt .= mt_rand();
	}
	
	// encode salt
	$salt_hash = hash(PH2_ENCRYPTION, $salt);
	
	// encode pw
	$pure_pw_hash = hash(PH2_ENCRYPTION, $pw);
	$pw_hash = composePasswordSalt($pure_pw_hash, $salt_hash);
	
	return array($pw_hash, $salt_hash);
	
} //encodePassword

//+ 
function checkPassword ( $pw , $pw_hash , $salt_hash )
/*/
Checks whether a submitted password ($pw)
validates against a given pw- and salt-hash.
---
@param pw: the password to check against the hashes
@type  pw: string
@param pw_hash: the password
hash
@type  pw_hash: hash string (ph2 std encoding)
@param salt_hash: the salt
hash
@type  salt_hash: hash string (ph2 std encoding)
-
@return: 1 if the password validates, 0 otherwise
@rtype:  bool
/*/
{
	// encode pw
	$pure_pw_hash = hash(PH2_ENCRYPTION, $pw);
	
	// compose password with given $pw string
	$pw_hash_candidate = composePasswordSalt($pure_pw_hash, $salt_hash);
	
	return $pw_hash_candidate == $pw_hash;
	
} //checkPassword

//+ 
function composePasswordSalt ( $pw_hash , $salt_hash )
/*/
Composes a password hash with a salt
hash.
---
@param pw_hash: the
password hash
@type  pw_hash: hash string (ph2 std encoding)
@param salt_hash: the salt
hash
@type  salt_hash: hash string (ph2 std encoding)
-
@return: the hash string composed
of the submitted password- and salt hash
@rtype:  hash string (ph2 std encoding)
/*/
{
	// composition: salt + pw + first half of salt-string
	$composed_string = $salt_hash . $pw_hash . substr($salt_hash, 0, (strlen($salt_hash) / 2));
	return hash(PH2_ENCRYPTION, $composed_string);
	
} //composePasswordSalt

?>