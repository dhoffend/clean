<?php

namespace clean;
/**
 * Description of CurlResponse
 *
 * @author Adam Jon Richardson
 */
class CurlResponse {
    public $errorNumber;
    public $errorMessage;
    public $info;
    public $headerLines;
    public $code;
    public $body;
    /**
     * Initialize Response with default data.
     */
    function __construct() {
        $this->errorNumber = 0;
        $this->errorMessage = '';
        $this->info = [];
        $this->headerLines = [];
        $this->code = 0;
        $this->body = '';
    }
    
    public function storeBody(string $body) {
        $this->body = $body;
    }
    
    public function storeErrorData(int $errorNumber, string $errorMessage) {
        $this->errorNumber = $errorNumber;
        $this->errorMessage = $errorMessage;
    }
    
    public function storeInfo(int $code, array $info) {
        $this->info = $info;
        $this->code = $code;        
    }
    
    public function addHeaderLine(string $headerLine) {
        $this->headerLines[] = $headerLine;
    }
}