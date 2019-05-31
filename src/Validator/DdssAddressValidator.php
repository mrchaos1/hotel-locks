<?php

namespace Wintech\HotelLocks\Validator;

use Wintech\HotelLocks\Exception\HotelLockException;

class DdssAddressValidator
{
    public static function validate($ddssAddress)
    {
        if (!preg_match('/\d{4}/', $ddssAddress)) {
            throw new HotelLockException(sprintf('ddss -(target address, source address) parameter is invalid. Expected 4 digit value, but got %s', $ddssAddress));
        }
    }
}