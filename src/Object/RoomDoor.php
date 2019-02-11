<?php

namespace Wintech\HotelLocks\Object;

/**
 * Class Room
 * @package Entities
 */
class RoomDoor
{
    /**
     * @var
     */
    private $number;

    /**
     * Room constructor.
     * @param $roomNumber
     */
    function __construct($roomNumber)
    {
        $this->number = $roomNumber;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

}