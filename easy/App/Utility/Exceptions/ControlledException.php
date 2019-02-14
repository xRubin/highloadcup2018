<?php
namespace App\Utility\Exceptions;

class ControlledException extends \RuntimeException
{
    public $httpCode = 500;
}