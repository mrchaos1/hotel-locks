<?php

namespace Wintech\HotelLocks;

use Wintech\HotelLocks\Service\KeyServiceInterface;
use Wintech\HotelLocks\Object\RoomDoor;
use Wintech\HotelLocks\Object\Guest;

class Client
{
    private $keyService;

    public function __construct(KeyServiceInterface $keyService)
    {
        $this->keyService = $keyService;
    }

    public function guestCheckIn($ddssAddress, RoomDoor $room, Guest $guest)
    {
        return $this->keyService->guestCheckIn($ddssAddress, $room, $guest);
    }

    public function guestCheckOut($ddss, RoomDoor $room)
    {
        return $this->keyService->guestCheckOut($ddss, $room);
    }
}