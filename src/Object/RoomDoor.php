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
     * @var array
     */
    private $commonDoorCodes;

    /**
     * @param array $doorCodes
     * @param array $commonDoorCodes
     */
    function __construct(array $doorCodes, array $commonDoorCodes)
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

    /**
     * @return array
     */
    public function getCommonDoorCodes(): array
    {
        return $this->commonDoorCodes;
    }

}