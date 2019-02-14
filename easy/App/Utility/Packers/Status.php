<?php
namespace App\Utility\Packers;

class Status
{
    public static function unpack($value): string
    {
        switch ($value) {
            case 0:
                return STATUS_1;
            case 1:
                return STATUS_2;
            case 2:
                return STATUS_3;
        }
    }

    public static function pack($value): int
    {
        switch ($value) {
            case STATUS_1:
                return 0;
            case STATUS_2:
                return 1;
            case STATUS_3:
                return 2;
        }
    }
}
