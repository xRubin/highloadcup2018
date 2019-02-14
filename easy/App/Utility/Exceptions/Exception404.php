<?php
namespace App\Utility\Exceptions;

class Exception404 extends ControlledException
{
    public $httpCode = 404;
}