<?php

namespace App\Utility\Exceptions;

class Exception400 extends ControlledException
{
    public $httpCode = 400;
}