<?php

namespace Wintech\HotelLocks\Service;

use Wintech\HotelLocks\Exception\HotelLockException;
use Wintech\HotelLocks\Exception\XeederLockException;
use Wintech\HotelLocks\Object\Guest;
use Wintech\HotelLocks\Object\RoomDoor;
use Wintech\HotelLocks\Validator\DdssAddressValidator;

class XeederKeyService extends KeyService implements KeyServiceInterface
{
    /**
     * @var string
     */
    private $serverAddress;

    /**
     * XeederKeyService constructor.
     * @param string $serverAddress
     */
    public function __construct(?string $serverAddress)
    {
        $this->serverAddress = $serverAddress;
    }

    const FIELD_GUEST_CHECK_IN    = '0I';
    const FIELD_GUEST_CHECK_OUT   = '0B';
    const FIELD_ROOM_NUMBER       = 'R';
    const FIELD_GUEST_NAME        = 'N';
    const FIELD_CARD_TYPE         = 'T';

    const RESPONSE_OK             = '00';

    public function guestCheckIn($ddssAddress, RoomDoor $room, Guest $guest, $isNew = true)
    {
        DdssAddressValidator::validate($ddssAddress);

        $command =
            $ddssAddress  . ($isNew ? self::FIELD_GUEST_CHECK_IN : 'G')
            . chr(124) . self::FIELD_ROOM_NUMBER . $room->getNumber()
            . chr(124) . self::FIELD_CARD_TYPE . '04'
            . chr(124) . self::FIELD_GUEST_NAME . $guest->getFullName()
            . chr(124) . 'D' . $guest->getCheckInTime()->format('YmdHi')
            . chr(124) . 'O' . $guest->getCheckOutTime()->format('YmdHi')
        ;
        return $this->sendCommand($command);
    }

    public function guestCheckOut($ddssAddress, RoomDoor $room)
    {
        DdssAddressValidator::validate($ddssAddress);

        $str =
            $ddssAddress  . self::FIELD_GUEST_CHECK_OUT
            . chr(124) . self::FIELD_ROOM_NUMBER . $room->getNumber()
        ;

        return $this->sendCommand($command);
    }

    public function sendCommand(string $commandString)
    {
        $stream = stream_socket_client($this->serverAddress, $errno, $errstr, 10);

        if (!$stream) {
            throw new HotelLockException();
        }

        fwrite($stream, chr(2).$commandString.chr(3));
        stream_socket_shutdown($stream, STREAM_SHUT_WR); /* This is the important line */

        $contents = stream_get_contents($stream);

        fclose($stream);

        if ($contents == chr(2).'0000'.self::RESPONSE_OK.chr(3)) {
            return true;
        }

        throw new XeederLockException("Card encoding error: $contents");
    }
}