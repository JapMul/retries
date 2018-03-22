<?php

namespace Retries;

use Exception;
use RuntimeException;

class Retry {

    private $_procedure;

    private $_tryAmount = 3;
    private $_waitTime = 1;
    private $_acceptedException = 'RuntimeException';

    private $_try;
    private $_suppressedExceptions;

    public function __construct(callable $procedure) {
        $this->_procedure = $procedure;
    }

    public function setTryAmount(int $tryAmount): self {
        $this->_tryAmount = $tryAmount;
        return $this;
    }

    public function setWaitTime(int $seconds): self {
        $this->_waitTime = $seconds;
        return $this;
    }

    public function setAcceptedException(string $exceptionClass): self {
        $this->_acceptedException = $exceptionClass;
        return $this;
    }

    public function run() {
        $this->_reset();
        return $this->_runTries();
    }

    private function _reset() {
        $this->_try = 0;
        $this->_suppressedExceptions = [];
    }

    private function _runTries() {
        try {
            return $this->_tryProcedure();
        } catch (Exception $exception) {
            return $this->_tryAgainIfPossible($exception);
        }
    }

    private function _tryProcedure() {
        $this->_try++;
        return call_user_func($this->_procedure);
    }

    private function _tryAgainIfPossible(Exception $exception) {
        $this->_handleException($exception);
        if ($this->_shouldTryAgain()) {
            $this->_wait();
            return $this->_runTries();
        } else {
            throw new RetryFailureException($this->_suppressedExceptions);
        }
    }

    private function _handleException(Exception $exception) {
        if (!$this->_isAcceptedException($exception)) {
            throw $exception;
        }
        $this->_suppressedExceptions[] = $exception;
    }

    private function _shouldTryAgain(): bool {
        return $this->_try < $this->_tryAmount;
    }

    private function _isAcceptedException(Exception $exception): bool {
        return is_a($exception, $this->_acceptedException);
    }

    private function _wait() {
        sleep($this->_waitTime);
    }

}
