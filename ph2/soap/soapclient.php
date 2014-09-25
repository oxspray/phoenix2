<?php
try{
  
  ini_set("soap.wsdl_cache_enabled", "0");
  
  $sClient = new SoapClient('http://www.rose.uzh.ch/phoenix/workspace/live/ph2/soap/ph2deafel.wsdl', array('trace'=>true));

  $response = $sClient->getOccurrenceDetails( array(123458) );
  //$response = $sClient->getOccurrences( array('abbés') );
  
  echo "Request:\n<br/>---\n<br/>";
  echo htmlentities($sClient->__getLastRequest(), ENT_QUOTES, 'UTF-8');
  echo "\n<br/>\n<br/>Resonse:\n<br/>---\n<br/>";
  echo htmlentities($sClient->__getLastResponse(), ENT_QUOTES, 'UTF-8');
  
  #var_dump($response);
  
  
} catch(SoapFault $e){
  var_dump($e);
}
?>