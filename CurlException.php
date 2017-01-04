<?php

namespace clean;
/**
 * Subclass of Exception to customize clean CURL exceptions.
 *
 * @author Adam Jon Richardson
 */
class CurlException extends Exception {
    const CODE_FAILED_INIT = 1;
    const CODE_FAILED_MULTI_INIT = 2;
    const CODE_FAILED_SETOPT = 4;
    const CODE_FAILED_SETOPT_ARRAY = 8;
    const CODE_FAILED_MULTI_ADD_HANDLE = 16;
    const CODE_FAILED_MULTI_SELECT = 32;
    const CODE_FAILED_INVALID_HTTP_METHOD = 64;
    //const CODE_
    // Redefine the exception so message and code are not optional
    public function __construct($message, $code = 0, Exception $previous = null) {
        // some code
    
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }
    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
