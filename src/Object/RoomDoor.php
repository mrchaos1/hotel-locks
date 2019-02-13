<?php

namespace Wintech\HotelLocks\Object;

/**
 * Class Room
 * @package Entities
 */
class RoomDoor
{
    /**
     * @var array
     */
    private $doorCodes;

    /**
     * RoomDoor constructor.
     * @param array $doorCodes
     */
    function __construct(array $doorCodes)
    {
        $this->doorCodes = $doorCodes;
    }

    /**
     * @return array
     */
    public function getDoorCodes(): array
    {
        return $this->doorCodes;
    }

}