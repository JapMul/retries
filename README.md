[![Build Status](https://travis-ci.org/JapMul/retries.svg?branch=master)](https://travis-ci.org/JapMul/retries)

Retries
============

A small library that helps retrying a procedure after an exception has been thrown.

### Basic usage

```php
<?php
use Retries\Retry;
use Retries\RetryFailureException;

$procedure = function() {
    // This is the function that might produce an error.
};

try {
    $retry = new Retry($procedure);
    $retry->setTryAmount(5); // Optional. Defaults to 3.
    $retry->run();
} catch (RetryFailureException $exception) {
    // All 5 times failed.
}
```

### Waiting between tries

It's possible to specify a waiting duration. After every failed try it will wait the specified amount of seconds before trying again. Defaults to `1` second.

```php
<?php
$retry = new Retry(function() {
    // Your function.
});
$retry->setWaitTime(5);
$retry->run();
```

### Specifying the exception to suppress

The default exception that will be suppressed is a `RuntimeException`. When your procedure throws this exception it will silently continue and try again until you reach the try amount. Other exceptions will be thrown as usual. It is possible to change what exception is expected.

```php
<?php
$retry = new Retry(function() {
    throw new LogicException;
});
$retry->setAcceptedException(LogicException::class);
$retry->run();
```

### Retrieving the original exceptions

If all tries fail, and you get a `RetryFailureException`, it is possible to retrieve the exceptions that were suppressed. The `RetryFailureException` has a `getOriginalExceptions()` function which will return all exceptions that were suppressed.

```php
<?php
use Retries\Retry;
use Retries\RetryFailureException;

$procedure = function() {
    // This is the function that might produce an error.
};

try {
    $retry = new Retry($procedure);
    $retry->setTryAmount(5); // Optional. Defaults to 3.
    $retry->run();
} catch (RetryFailureException $exception) {
    $originalExceptions = $exception->getOriginalExceptions();
}
```
