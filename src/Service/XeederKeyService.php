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

    const FIELD_GUEST_CHECK_IN           = '0I';
    const FIELD_GUEST_CHECK_OUT          = '0B';
    const FIELD_ROOM_NUMBERS             = 'R';
    const FIELD_COMMON_ROOM_NUMBERS      = 'C';
    const FIELD_GUEST_NAME               = 'N';
    const FIELD_CARD_TYPE                = 'T';

    const RESPONSE_OK                    = '00';

    /**
     * @param $code
     *
     * @return null|string
     */
    private static function getErrorMessageByCode($code): ?string
    {
        $errors = [
            chr(2).'000001'.chr(3) => 'No card',
            chr(2).'000002'.chr(3) => 'No encoder found',
            chr(2).'000003'.chr(3) => 'Invalid card',
            chr(2).'000004'.chr(3) => 'Card type error',
            chr(2).'000005'.chr(3) => 'Card read/write error',
            chr(2).'000006'.chr(3) => 'Com port is not open',
            chr(2).'000007'.chr(3) => 'Read Query card ok',
            chr(2).'000008'.chr(3) => 'Invalid parameter',
            chr(2).'000009'.chr(3) => 'Operating not support',
            chr(2).'000010'.chr(3) => 'Other error',
            chr(2).'000011'.chr(3) => 'Port is in using',
            chr(2).'000012'.chr(3) => 'Communication error',
            chr(2).'000013'.chr(3) => 'Card is not empty, revoke it firstly',
            chr(2).'000014'.chr(3) => 'Failed! Card Encryption is unknown',
            chr(2).'000015'.chr(3) => 'Operating failed',
            chr(2).'000016'.chr(3) => 'Unknown error',
            chr(2).'000017'.chr(3) => 'Card count over limit',
            chr(2).'000018'.chr(3) => 'Invalid room number',
            chr(2).'000019'.chr(3) => 'Please input one room number',
            chr(2).'000020'.chr(3) => 'Empty card',
            chr(2).'000023'.chr(3) => 'Not Guest Card',
        ];

        if(isset($errors[$code])) {
           return $errors[$code];
        }

        return 'Unknown encoding error';
    }

    public function guestCheckIn($ddssAddress, RoomDoor $room, Guest $guest, $isNew = true)
    {
        DdssAddressValidator::validate($ddssAddress);

        $command =
            $ddssAddress  . ($isNew ? self::FIELD_GUEST_CHECK_IN : 'G')
            . chr(124) . self::FIELD_ROOM_NUMBERS . implode(',', $room->getDoorCodes())
            . chr(124) . self::FIELD_CARD_TYPE . '04'
            . chr(124) . self::FIELD_GUEST_NAME . $guest->getFullName()
            . chr(124) . 'D' . $guest->getCheckInTime()->format('YmdHi')
            . chr(124) . 'O' . $guest->getCheckOutTime()->format('YmdHi')
        ;

        if($room->getCommonDoorCodes())
        {
            $command .= chr(124) . self::FIELD_COMMON_ROOM_NUMBERS . implode(',', $room->getCommonDoorCodes());
        }

        return $this->sendCommand($command);
    }

    public function guestCheckOut($ddssAddress, RoomDoor $room)
    {
        DdssAddressValidator::validate($ddssAddress);

        $command =
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

        throw new XeederLockException(self::getErrorMessageByCode($contents));
    }
}