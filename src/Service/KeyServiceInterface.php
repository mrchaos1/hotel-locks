<?php
namespace Wintech\HotelLocks\Service;

use Wintech\HotelLocks\Object\RoomDoor;
use Wintech\HotelLocks\Object\Guest;

interface KeyServiceInterface
{
    /**
     * @param string $ddssAddress
     * @param RoomDoor $room
     * @param Guest $guest
     * @param bool $isNew
     *
     * @return string
     */
    public function guestCheckIn($ddssAddress, RoomDoor $room, Guest $guest, $isNew = true);

    /**
     * @param string $ddss
     * @param RoomDoor $room
     *
     * @return string
     */
    public function guestCheckOut($ddss, RoomDoor $room);

}