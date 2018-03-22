<?php

use PHPUnit\Framework\TestCase;
use Retries\Retry;
use Retries\RetryFailureException;

class RetryTest extends TestCase {

    public function testRetryFailureExceptionHoldsOriginalExceptions() {
        $procedure = function() {
            throw new Exception('This is my exception.');
        };

        try {
            (new Retry($procedure))
                ->setTryAmount(2)
                ->setAcceptedException(Exception::class)
                ->run();
        } catch (RetryFailureException $exception) {
            $originalExceptions = $exception->getOriginalExceptions();
            $this->assertSame(2, sizeof($originalExceptions));
            foreach ($originalExceptions as $original) {
                $this->assertSame('This is my exception.', $original->getMessage());
            }
        }
    }

    public function testRunShouldResetTheCounters() {
        $count = 0;
        $counter = function () use (&$count) {
            $count++;
            throw new RuntimeException;
        };
        $retry = new Retry($counter);

        $this->_safelyRunProcedure($retry);
        $this->assertEquals(3, $count);

        $this->_safelyRunProcedure($retry);
        $this->assertEquals(6, $count);
    }

    public function testRetryShouldCallItsCallableOnceIfNoExceptionIsThrown() {
        $count = 0;
        $procedure = function() use (&$count) {
            $count++;
        };
        $retry = new Retry($procedure);
        $retry->run();
        $this->assertEquals(1, $count);
    }

    /** @dataProvider retryProvider */
    public function testRetryShouldCallProcedureUntilTryCountIsReachedWhenProcedureThrows(int $tryAmount) {
        $count = 0;
        $procedure = function() use (&$count) {
            $count++;
            throw new RuntimeException;
        };
        $retry = new Retry($procedure);
        $retry->setTryAmount($tryAmount);
        $this->_safelyRunProcedure($retry);
        $this->assertEquals($tryAmount, $count);
    }

    public function retryProvider() {
        return [
            [
                3,
            ],
            [
                1,
            ],
        ];
    }

    /** @expectedException Retries\RetryFailureException */
    public function testRetryShouldThrowRetryFailureExceptionIfAllTrialFailed() {
        $retry = new Retry(function () {
            throw new RuntimeException;
        });
        $retry->run();
    }

    public function testRetryShouldWaitSpecifiedSecondsBeforeNextTrial() {
        $retry = new Retry(function () {
            throw new RuntimeException;
        });
        $retry->setWaitTime(2);
        $begin = time();
        $this->_safelyRunProcedure($retry);
        $end = time();
        $this->assertEquals(2 * 2, $end - $begin);
    }

    /** @expectedException LogicException */
    public function testRetryShouldThrowExceptionThrownIfItIsNotAcceptedException() {
        $retry = new Retry(function() {
            throw new LogicException;
        });
        $retry->run();
    }

    public function testRetryShouldAcceptProvidedException() {
        $count = 0;
        $retry = new Retry(function() use (&$count) {
            $count++;
            throw new LogicException;
        });
        $retry->setAcceptedException(LogicException::class);
        $this->_safelyRunProcedure($retry);
        $this->assertSame(3, $count);
    }

    public function testSetterChain() {
        $procedure = function() {
            throw new RuntimeException;
        };
        $retry = (new Retry($procedure))
            ->setTryAmount(3)
            ->setWaitTime(4)
            ->setAcceptedException(RuntimeException::class);
        $begin = time();
        $this->_safelyRunProcedure($retry);
        $end = time();
        $this->assertEquals(2 * 4, $end - $begin);
    }

    private function _safelyRunProcedure(Retry $retry) {
        try {
            $retry->run();
        } catch (Exception $exception) {
            // Ignore the catch for this test.
        }
    }

}
