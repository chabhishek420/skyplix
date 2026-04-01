<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\KeitaroDb\GDBC;

class GDBCClient
{
    private $_file = NULL;
    private $_meta = NULL;
    const START_POS = 0;
    const END_POS = 1;
    const PTR_POS = 2;
    const NOT_FOUND_PTR = -1;
    public function __construct(\Core\KeitaroDb\Common\DbStream $file)
    {
        $this->_file = $file;
        $this->_parseMeta();
    }
    public function getMeta()
    {
        return $this->_meta;
    }
    public function getRecord($ip)
    {
        $ipV4 = \Core\KeitaroDb\Common\BinaryPacker::toV4($ip);
        if (!empty($ipV4)) {
            return $this->_getRecordV4($ipV4);
        }
        return $this->_getRecordV6($ip);
    }
    private function _getMetaLength()
    {
        return 108;
    }
    private function _readInt()
    {
        $buf = $this->_file->read(4);
        return \Core\KeitaroDb\Common\BinaryPacker::unpackInt($buf);
    }
    private function _parseMeta()
    {
        $length = $this->_getMetaLength() + 4;
        $this->_file->seek(0, SEEK_SET);
        $buf = $this->_file->read($length);
        if (substr($buf, 0, 4) != "GDBC") {
            throw new Exception("Invalid meta header");
        }
        $this->_meta = new GDBCMeta();
        $this->_meta->structVersion = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 4, 4));
        $this->_meta->buildVersion = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 8, 4));
        $this->_meta->countV4 = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 12, 4));
        $this->_meta->countV6 = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 16, 4));
        $this->_meta->contentPtr = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 20, 4));
        $this->_meta->regionPtr = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 24, 4));
        $this->_meta->cityPtr = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 28, 4));
        $this->_meta->ispPtr = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 32, 4));
        $this->_meta->hashV4Pos = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 36, 4));
        $this->_meta->hashV4Min = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 40, 4));
        $this->_meta->hashV4Max = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 44, 4));
        $this->_meta->hashV4Step = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 48, 4));
        $this->_meta->hashV6Pos = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 52, 4));
        $this->_meta->hashV6Min = \Core\KeitaroDb\Common\BinaryPacker::unpackBigInt(substr($buf, 56, 16));
        $this->_meta->hashV6Max = \Core\KeitaroDb\Common\BinaryPacker::unpackBigInt(substr($buf, 72, 16));
        $this->_meta->hashV6Step = \Core\KeitaroDb\Common\BinaryPacker::unpackBigInt(substr($buf, 88, 16));
        $this->_meta->hashV4PtrPos = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 104, 4));
        $this->_meta->hashV6PtrPos = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, 108, 4));
    }
    private function _hashFuncV4($val)
    {
        if ($this->_meta->hashV4Max < $val) {
            $val = $this->_meta->hashV4Max;
        }
        return floor(($val - $this->_meta->hashV4Min) / $this->_meta->hashV4Step);
    }
    private function _hashFuncV6($val)
    {
        if (0 < bccomp($val, $this->_meta->hashV6Max)) {
            $val = $this->_meta->hashV6Max;
        }
        $tmp = bcsub($val, $this->_meta->hashV6Min);
        return bcdiv($tmp, $this->_meta->hashV6Step);
    }
    private function _getHashValsV4($buf, $pos)
    {
        $startPos = $pos * 12;
        return [START_POS => \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, $startPos, 4)), END_POS => \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, $startPos + 4, 4)), PTR_POS => \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, $startPos + 8, 4))];
    }
    private function _getLeafPtrV4($buf, $searchIPInt)
    {
        $low = 0;
        $high = strlen($buf) / 12 - 1;
        $hashLow = $this->_getHashValsV4($buf, $low);
        if ($hashLow[START_POS] <= $searchIPInt && $searchIPInt <= $hashLow[END_POS]) {
            return $hashLow[PTR_POS];
        }
        if ($high == $low || $searchIPInt < $hashLow[START_POS]) {
            return NOT_FOUND_PTR;
        }
        $hashHigh = $this->_getHashValsV4($buf, $high);
        if ($hashHigh[START_POS] <= $searchIPInt && $searchIPInt <= $hashHigh[END_POS]) {
            return $hashHigh[PTR_POS];
        }
        if ($hashHigh[END_POS] < $searchIPInt) {
            return NOT_FOUND_PTR;
        }
        while (1) {
            $nextApprox = round($low + ($high - $low) * ($searchIPInt - $hashLow[END_POS]) / ($hashHigh[START_POS] - $hashLow[END_POS]));
            if ($nextApprox == $low) {
                $nextApprox = $low + 1;
            }
            if ($nextApprox == $high) {
                $nextApprox = $high - 1;
            }
            $hashCur = $this->_getHashValsV4($buf, $nextApprox);
            if ($hashCur[START_POS] <= $searchIPInt && $searchIPInt <= $hashCur[END_POS]) {
                return $hashCur[PTR_POS];
            }
            if ($hashCur[END_POS] < $searchIPInt) {
                $low = $nextApprox;
            } else {
                if ($searchIPInt < $hashCur[START_POS]) {
                    $high = $nextApprox;
                }
            }
            if ($high > $low + 1) {
            }
        }
        return NOT_FOUND_PTR;
    }
    private function _getHashListV4($searchIPInt)
    {
        $block = $this->_hashFuncV4($searchIPInt);
        $hashAddrPos = $this->_meta->hashV4PtrPos + $block * 4;
        $this->_file->seek($hashAddrPos, SEEK_SET);
        $hashListPtr = $this->_readInt();
        $this->_file->seek($this->_meta->hashV4Pos + $hashListPtr, SEEK_SET);
        $hashLen = $this->_readInt();
        if ($hashLen == 0) {
            return "";
        }
        $buf = $this->_file->read($hashLen);
        return $buf;
    }
    private function _readDic($ptr)
    {
        $this->_file->seek($ptr, SEEK_SET);
        $buf = $this->_file->read(256);
        return substr($buf, 1, ord($buf[0]));
    }
    private function _getLeaf($ptr)
    {
        $this->_file->seek($this->_meta->contentPtr + $ptr, SEEK_SET);
        $leaf = new GDBCLeaf();
        $leaf->countryCode = $this->_file->read(2);
        $regionPtr = $this->_readInt();
        $pos = $this->_file->tell();
        $leaf->region = $this->_readDic($this->_meta->regionPtr + $regionPtr);
        $this->_file->seek($pos, SEEK_SET);
        $cityPtr = $this->_readInt();
        $pos = $this->_file->tell();
        $leaf->city = $this->_readDic($this->_meta->cityPtr + $cityPtr);
        $this->_file->seek($pos, SEEK_SET);
        $ispPtr = $this->_readInt();
        $pos = $this->_file->tell();
        $leaf->ISP = $this->_readDic($this->_meta->ispPtr + $ispPtr);
        $this->_file->seek($pos, SEEK_SET);
        return $leaf;
    }
    private function _getRecordV4($ip)
    {
        $searchIPInt = \Core\KeitaroDb\Common\BinaryPacker::ipV4ToInt($ip);
        if ($searchIPInt < $this->_meta->hashV4Min || $this->_meta->hashV4Max < $searchIPInt) {
            return NULL;
        }
        $buf = $this->_getHashListV4($searchIPInt);
        if (empty($buf)) {
            return NULL;
        }
        $leafPtr = $this->_getLeafPtrV4($buf, $searchIPInt);
        if ($leafPtr == NOT_FOUND_PTR) {
            return NULL;
        }
        return $this->_getLeaf($leafPtr);
    }
    private function _getRecordV6($ip)
    {
        $searchIPInt = \Core\KeitaroDb\Common\BinaryPacker::ipV6ToBigInt($ip);
        if (bccomp($searchIPInt, $this->_meta->hashV6Min) < 0 || bccomp($this->_meta->hashV6Max, $searchIPInt) < 0) {
            return NULL;
        }
        $buf = $this->_getHashListV6($searchIPInt);
        if (empty($buf)) {
            return NULL;
        }
        $leafPtr = $this->_getLeafPtrV6($buf, $searchIPInt);
        if ($leafPtr == NOT_FOUND_PTR) {
            return NULL;
        }
        return $this->_getLeaf($leafPtr);
    }
    private function _getHashValsV6($buf, $pos)
    {
        $startPos = $pos * 36;
        return [START_POS => \Core\KeitaroDb\Common\BinaryPacker::unpackBigInt(substr($buf, $startPos, 16)), END_POS => \Core\KeitaroDb\Common\BinaryPacker::unpackBigInt(substr($buf, $startPos + 16, 16)), PTR_POS => \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, $startPos + 32, 4))];
    }
    private function _getLeafPtrV6($buf, $searchIPInt)
    {
        $low = 0;
        $high = strlen($buf) / 36 - 1;
        $hashLow = $this->_getHashValsV6($buf, $low);
        if (bccomp($hashLow[START_POS], $searchIPInt) <= 0 && bccomp($searchIPInt, $hashLow[END_POS]) <= 0) {
            return $hashLow[PTR_POS];
        }
        if ($high == $low || bccomp($searchIPInt, $hashLow[START_POS]) < 0) {
            return NOT_FOUND_PTR;
        }
        $hashHigh = $this->_getHashValsV6($buf, $high);
        if (bccomp($hashHigh[START_POS], $searchIPInt) <= 0 && bccomp($searchIPInt, $hashHigh[END_POS]) <= 0) {
            return $hashHigh[PTR_POS];
        }
        if (bccomp($hashHigh[END_POS], $searchIPInt) < 0) {
            return NOT_FOUND_PTR;
        }
        while (1) {
            $fullInterval = bcsub($hashHigh[START_POS], $hashLow[END_POS]);
            $nextApprox = bcsub($searchIPInt, $hashLow[END_POS]);
            $nextApprox = bcmul($nextApprox, $high - $low);
            $nextApprox = bcdiv($nextApprox, $fullInterval);
            $nextApprox = bcadd($nextApprox, $low);
            $nextApprox = \Core\KeitaroDb\Common\BinaryPacker::bcFloor($nextApprox);
            if ($nextApprox == $low) {
                $nextApprox = $low + 1;
            }
            if ($nextApprox == $high) {
                $nextApprox = $high - 1;
            }
            $hashCur = $this->_getHashValsV6($buf, $nextApprox);
            if (bccomp($hashCur[START_POS], $searchIPInt) <= 0 && bccomp($searchIPInt, $hashCur[END_POS]) <= 0) {
                return $hashCur[PTR_POS];
            }
            if (0 < bccomp($searchIPInt, $hashCur[END_POS])) {
                $low = $nextApprox;
            } else {
                if (bccomp($searchIPInt, $hashCur[START_POS]) < 0) {
                    $high = $nextApprox;
                }
            }
            if ($high > $low + 1) {
            }
        }
        return NOT_FOUND_PTR;
    }
    private function _getHashListV6($searchIPInt)
    {
        $block = $this->_hashFuncV6($searchIPInt);
        $hashAddrPos = $this->_meta->hashV6PtrPos + $block * 4;
        $this->_file->seek($hashAddrPos, SEEK_SET);
        $hashListPtr = $this->_readInt();
        $this->_file->seek($this->_meta->hashV6Pos + $hashListPtr, SEEK_SET);
        $hashLen = $this->_readInt();
        if (empty($hashLen)) {
            return "";
        }
        $buf = $this->_file->read($hashLen);
        return $buf;
    }
}

?>