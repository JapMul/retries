<?php

namespace Retries;

use RuntimeException;

class RetryFailureException extends RuntimeException {

    private $_suppressedExceptions = [];

    public function __construct(array $suppressedExceptions = []) {
        $this->_suppressedExceptions = $suppressedExceptions;
    }

    public function getOriginalExceptions(): array {
        return $this->_suppressedExceptions;
    }

}
