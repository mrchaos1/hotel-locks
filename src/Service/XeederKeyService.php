<?php

namespace Wintech\HotelLocks\Service;

use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string $serverAddress
     * @param LoggerInterface $logger
     */
    public function __construct(?string $serverAddress, LoggerInterface $logger)
    {
        $this->serverAddress = $serverAddress;
        $this->logger = $logger;
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
            chr(2).'000001'.chr(3) => '(01): No card',
            chr(2).'000002'.chr(3) => '(02): No encoder found',
            chr(2).'000003'.chr(3) => '(03): Invalid card',
            chr(2).'000004'.chr(3) => '(04): Card type error',
            chr(2).'000005'.chr(3) => '(05): Card read/write error',
            chr(2).'000006'.chr(3) => '(06): Com port is not open',
            chr(2).'000007'.chr(3) => '(07): Read Query card ok',
            chr(2).'000008'.chr(3) => '(08): Invalid parameter',
            chr(2).'000009'.chr(3) => '(09): Operating not support',
            chr(2).'000010'.chr(3) => '(10): Other error',
            chr(2).'000011'.chr(3) => '(11): Port is in using',
            chr(2).'000012'.chr(3) => '(12): Communication error',
            chr(2).'000013'.chr(3) => '(13): Card is not empty, revoke it firstly',
            chr(2).'000014'.chr(3) => '(14): Failed! Card Encryption is unknown',
            chr(2).'000015'.chr(3) => '(15): Operating failed',
            chr(2).'000016'.chr(3) => '(16): Unknown error',
            chr(2).'000017'.chr(3) => '(17): Card count over limit',
            chr(2).'000018'.chr(3) => '(18): Invalid room number',
            chr(2).'000019'.chr(3) => '(19): Please input one room number',
            chr(2).'000020'.chr(3) => '(20): Empty card',
            chr(2).'000023'.chr(3) => '(23): Not Guest Card',
        ];

        if(isset($errors[$code])) {
            return 'Xeeder encoding error ' . $errors[$code];
        }

        return 'Xeeder encoding: Unknown encoding error';
    }

    /**
     * @param string $ddssAddress
     * @param RoomDoor $room
     * @param Guest $guest
     * @param bool $isNew
     *
     * @return bool|string
     */
    public function guestCheckIn($ddssAddress, RoomDoor $room, Guest $guest, $isNew = true)
    {
        DdssAddressValidator::validate($ddssAddress);

        $guestName = strtr($guest->getFullName(), [
            'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü' => 'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ҡ' => 'k',
        ]);

        $guestName = preg_replace('/[^А-Яа-яA-Za-z0-9,.;:"!@№$%^&*#()_+-=* ]+/u', '?', $guestName);

        $command =
            $ddssAddress  . self::FIELD_GUEST_CHECK_IN
            . chr(124) . self::FIELD_ROOM_NUMBERS . implode(',', $room->getDoorCodes())
            . chr(124) . self::FIELD_CARD_TYPE . '04'
            . chr(124) . self::FIELD_GUEST_NAME . $guestName
            . chr(124) . 'D' . $guest->getCheckInTime()->format('YmdHi')
            . chr(124) . 'O' . $guest->getCheckOutTime()->format('YmdHi')
            . chr(124) . ($isNew ? 'VN' : '')
        ;

        if ($room->getCommonDoorCodes()) {
            $command .= chr(124) . self::FIELD_COMMON_ROOM_NUMBERS . implode('', $room->getCommonDoorCodes());
        }

        return $this->sendCommand($command);
    }

    /**
     * @param string $ddssAddress
     * @param RoomDoor $room
     *
     * @return bool|string
     */
    public function guestCheckOut($ddssAddress, RoomDoor $room)
    {
        DdssAddressValidator::validate($ddssAddress);

        $command =
            $ddssAddress  . self::FIELD_GUEST_CHECK_OUT
            . chr(124) . self::FIELD_ROOM_NUMBERS . implode(',', $room->getDoorCodes())
        ;

        return $this->sendCommand($command);
    }

    /**
     * @param string $commandString
     *
     * @return bool
     */
    public function sendCommand(string $commandString)
    {
        $this->logger->info('Sending command', [
            'command' => $commandString,
        ]);

        $stream = stream_socket_client($this->serverAddress, $errno, $errstr, 10);

        if (!$stream) {
            throw new HotelLockException();
        }

        fwrite($stream, iconv("UTF-8", "CP1251", (chr(2).$commandString.chr(3))));
        stream_socket_shutdown($stream, STREAM_SHUT_WR); /* This is the important line */

        $contents = stream_get_contents($stream);

        $this->logger->info('Sending command response', [
            'contents' => $contents,
        ]);

        fclose($stream);

        if ($contents == chr(2).'0000'.self::RESPONSE_OK.chr(3)) {
            return true;
        }

        throw new XeederLockException(self::getErrorMessageByCode($contents) . '. Response: '. $contents);
    }
}