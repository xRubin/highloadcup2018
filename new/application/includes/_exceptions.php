<?php declare(strict_types=1);

class ControlledException extends RuntimeException
{
    public $httpCode = 500;
}

class Exception404 extends ControlledException
{
    public $httpCode = 404;
}

class Exception400 extends ControlledException
{
    public $httpCode = 400;
}
