<?php

require_once 'abstract_api.php';

require_once('../soap/ph2deafel.php');

class MyAPI extends API {
    public function __construct($request, $origin) {
        parent::__construct($request);
    }

    /*
     * Example of an Endpoint
     */
    protected function example() {
        error_log("example. method: " . $this->verb);
        switch ($this->verb) {
            case "get":
                if ($this->method == 'GET') {
                    return array("status" => "success", "endpoint" => $this->endpoint, "verb" => $this->verb, "args" => $this->args, "request" => $this->request);
                } else {
                    return "Only accepts GET requests";
                }
                break;
            case "post":
                if ($this->method == 'POST') {
                    return array("status" => "success", "endpoint" => $this->endpoint, "verb" => $this->verb, "args" => $this->args, "request" => $this->request);
                } else {
                    return "Only accepts POST requests";
                }
                break;
            default:
                break;
        }

    }

    /*
 * Example of an Endpoint
 */
    protected function occurrenceIDs() {
        $lemma = $_GET["lemma"];
        if ($this->method == 'GET' && ! empty($lemma)) {

            return array(getOccurrenceIDs($lemma), 200);
        } else {
            throw new Exception("bla");
        }
    }

    protected function occurrences() {
        $lemma = $_GET["lemma"];
        $withContext = filter_var($_GET["withContext"], FILTER_VALIDATE_BOOLEAN);
        if ($this->method == 'GET' && ! empty($lemma)) {
            $occs = getOccurrences ($lemma, $withContext);
            return array($occs, 200);
        } else {
            throw new Exception("bla");
        }
    }

    protected function occurrence() {
        $occurrenceID = $_GET["occurrenceID"];
        $withContext = filter_var($_GET["withContext"], FILTER_VALIDATE_BOOLEAN);
        if ($this->method == 'GET' && ! empty($occurrenceID)) {
            $occs = getOccurrenceDetails($occurrenceID, $withContext);
            return array($occs, 200);
        } else {
            throw new Exception("bla");
        }
    }

    protected function allLemmata() {
        if ($this->method == 'GET') {
            return array(getAllLemmata(), 200);
        } else {
            throw new Exception("bla");
        }
    }

    protected function numberOfOccurrenceChunks() {
        $lemma = $_GET["lemma"];
        if ($this->method == 'GET') {
            return array(getNumberOfOccurrenceChunks($lemma), 200);
        } else {
            throw new Exception("bla");
        }
    }

    protected function occurrencesChunk() {
        $lemma = $_GET["lemma"];
        $withContext = filter_var($_GET["withContext"], FILTER_VALIDATE_BOOLEAN);
        $chunk = $_GET["chunk"];
        if ($this->method == 'GET' && ! empty($lemma)) {
            $occs = getOccurrencesChunk($lemma, $withContext, $chunk);
            return array($occs, 200);
        } else {
            throw new Exception("bla");
        }
    }

    protected function assignOccurrencesToLemma() {

        $d = $this->retrieveJsonPostDataAsArray();
        $newMainLemmaIdentifier = $d['newMainLemmaIdentifier'];
        $newLemmaIdentifier = $d['newLemmaIdentifier'];
        $occurrenceIDs = $d['occurrenceIDs'];

        if ($this->method == 'POST' && !empty($newMainLemmaIdentifier) && !empty($newLemmaIdentifier)) {

            try {
                $result = assignOccurrencesToLemma($occurrenceIDs, $newMainLemmaIdentifier, $newLemmaIdentifier);
            } catch (Exception $e) {
                // this will occurr when new lemma is not unique -> error on phoenix2 side
                return array($e->getMessage(), 500);
            }

            return array($result, 200);
        } else {
            throw new Exception("invalid request");
        }
    }

    private function retrieveJsonPostDataAsArray() {
        // get the raw POST data
        $rawData = file_get_contents("php://input");

        // this returns null if not valid json
        return json_decode($rawData, true);
    }
}

?>