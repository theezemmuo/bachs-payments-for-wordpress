<?php
namespace Bachs\API\Exceptions;

use Exception;

/**
 * Base exception for Bachs API errors.
 */
class ApiException extends Exception {
    protected $http_status;

    public function __construct( $message, $http_status = 0, Exception $previous = null ) {
        $this->http_status = $http_status;
        parent::__construct( $message, 0, $previous );
    }

    public function getHttpStatus() {
        return $this->http_status;
    }
}
