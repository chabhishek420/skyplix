<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\KeitaroDb\DBCA;

class DbReader
{
    private $dbStream = NULL;
    private $header = NULL;
    private $structVersion = NULL;
    private $buildVersion = NULL;
    private $dateTime = NULL;
    private $count = NULL;
    private $contentPtrs = [];
    private $hashPtrDeclared = [];
    private $hashStartReal = NULL;
    private $hashMin = NULL;
    private $hashMax = NULL;
    private $hashStep = NULL;
    private $curContent = NULL;
    private $curHashPos = NULL;
    private $curHashListCount = NULL;
    private $curHashListPos = NULL;
    const HASH_ELEM_START_IP_POS = 0;
    const HASH_ELEM_END_IP_POS = 1;
    const HASH_ELEM_CODE_PTR_POS = 2;
    const CONTENT_PTR_POS = 0;
    const CONTENT_VAL_POS = 1;
    const HASH_START_IP_KEY = "startIp";
    const HASH_END_IP_KEY = "endIp";
    const HASH_PTR_KEY = "ptr";
    private function readHeader()
    {
        $this->dbStream->seek(0);
        $this->header = $this->dbStream->read(4);
        $this->structVersion = $this->readInt();
        $this->buildVersion = $this->readInt();
        $this->dateTime = $this->readInt();
        $this->count = $this->readInt();
    }
    private function readContentPtrs()
    {
        for ($i = 0; $i < $this->count; $i++) {
            $this->contentPtrs[] = $this->dbStream->tell();
            $this->readVarChar();
        }
    }
    private function readHashParams()
    {
        $this->dbStream->seek(-12, SEEK_END);
        $this->hashMin = $this->readInt();
        $this->hashMax = $this->readInt();
        $this->hashStep = $this->readInt();
        $this->readHashPtrDeclared();
    }
    private function readHashPtrDeclared()
    {
        $count = ($this->hashMax - $this->hashMin) / $this->hashStep;
        $this->dbStream->seek(-12 - $count * 4, SEEK_END);
        $buf = $this->dbStream->read($count * 4);
        for ($i = 0; $i < $count; $i++) {
            $this->hashPtrDeclared[] = \Core\KeitaroDb\Common\BinaryPacker::unpackInt(substr($buf, $i * 4, 4));
        }
    }
    private function readHashIndexPtrs()
    {
        $this->hashStartReal = $this->dbStream->tell();
        $this->readHashParams();
    }
    private function readInt()
    {
        $buf = $this->dbStream->read(4);
        return \Core\KeitaroDb\Common\BinaryPacker::unpackInt($buf);
    }
    private function readVarChar()
    {
        $len = $this->readInt();
        return $this->dbStream->read($len);
    }
    private function resetContentPtrs()
    {
        $this->curContent = 0;
    }
    private function resetHashListPtrs()
    {
        $this->curHashPos = 0;
        $this->curHashListPos = 0;
        $this->curHashListCount = 0;
        $this->getNextHashListPtrs();
    }
    private function getNextContentPtr()
    {
        if (count($this->contentPtrs) <= $this->curContent) {
            return false;
        }
        $result = $this->contentPtrs[$this->curContent];
        $this->curContent++;
        return $result;
    }
    private function getNextHashListPtrs()
    {
        while (count($this->hashPtrDeclared) <= $this->curHashPos) {
            $this->dbStream->seek($this->hashPtrDeclared[$this->curHashPos]);
            $this->curHashListCount = $this->readInt();
            $this->curHashPos++;
            if ($this->curHashListCount == 0) {
                return true;
            }
        }
        return false;
    }
    private function getNextHashElem()
    {
        if ($this->curHashListCount <= $this->curHashListPos) {
            $result = $this->getNextHashListPtrs();
            if (!$result) {
                return false;
            }
            $this->curHashListPos = 0;
        }
        $result = [];
        $result[HASH_START_IP_KEY] = $this->readInt();
        $result[HASH_END_IP_KEY] = $this->readInt();
        $result[HASH_PTR_KEY] = $this->readInt();
        $this->curHashListPos++;
        return $result;
    }
    public function __construct($dbStream)
    {
        $this->dbStream = $dbStream;
        $this->readAll();
    }
    public function readAll()
    {
        $this->readHeader();
        $this->readContentPtrs();
        $this->readHashIndexPtrs();
    }
    public function getHeader()
    {
        return ["header" => $this->header, "structVersion" => $this->structVersion, "buildVersion" => $this->buildVersion, "dateTime" => $this->dateTime, "count" => $this->count];
    }
    public function getContentByPos($pos)
    {
        if (count($this->contentPtrs) <= $pos) {
            return false;
        }
        $ptrs = $this->contentPtrs[$pos];
        $this->dbStream->seek($ptrs);
        $str = $this->readVarChar();
        return $str;
    }
    public function getHashListByPos($pos)
    {
        if (count($this->hashPtrDeclared) <= $pos) {
            return false;
        }
        $ptrs = $this->hashPtrDeclared[$pos];
        $this->dbStream->seek($ptrs);
        $count = $this->readInt();
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $row = [];
            $row[] = $this->readInt();
            $row[] = $this->readInt();
            $row[] = $this->readInt();
            $result[] = $row;
        }
        return $result;
    }
    public function getFullList()
    {
        $this->resetHashListPtrs();
        $hashElem = $this->getNextHashElem();
        $result = [];
        $prevStr = "undef";
        while ($hashElem) {
            $oldPos = $this->dbStream->tell();
            $this->dbStream->seek($hashElem[HASH_PTR_KEY]);
            $str = $this->readVarChar();
            if ($str != $prevStr) {
                $result[] = [$hashElem[HASH_START_IP_KEY], $hashElem[HASH_END_IP_KEY], $str];
                $prevStr = $str;
            }
            $this->dbStream->seek($oldPos);
            $hashElem = $this->getNextHashElem();
        }
        return $result;
    }
    public function checkConsistency()
    {
        if ($this->header !== "DBCA") {
            throw new Exception("Не корректный заголовок. Получен " . $this->header);
        }
        if ($this->hashStartReal != $this->hashPtrDeclared[0]) {
            throw new Exception("Не совпадает адрес начала хеш индекса. Должен быть " . $this->hashPtrDeclared[0] . ", а соджимое заканчивается на " . $this->hashStartReal);
        }
        $this->resetContentPtrs();
        $this->resetHashListPtrs();
        $contentPtr = $this->getNextContentPtr();
        $hashElem = $this->getNextHashElem();
        while ($contentPtr !== false && $hashElem) {
            flush();
            if ($contentPtr < $hashElem[HASH_PTR_KEY]) {
                throw new Exception("В индексе отсутствует код " . $contentPtr);
            }
            if ($hashElem[HASH_PTR_KEY] == $contentPtr) {
                $prevHashElem = $hashElem;
                while ($prevHashElem[HASH_PTR_KEY] == $hashElem[HASH_PTR_KEY]) {
                    $hashElem = $this->getNextHashElem();
                }
                $contentPtr = $this->getNextContentPtr();
            } else {
                if ($hashElem[HASH_PTR_KEY] < $contentPtr) {
                    throw new Exception("В индексе присутствуют записи отсутствующие в данных");
                }
            }
        }
        if ($contentPtr !== false) {
            $contentPtr = $this->getNextContentPtr();
        }
        if ($contentPtr !== false) {
            $this->dbStream->seek($contentPtr);
            throw new Exception("Часть данных не проиндексирована (адрес " . $contentPtr . "):" . $this->readVarChar());
        }
        if ($hashElem !== false && $this->getNextHashElem() !== false) {
            throw new Exception("В индексе присутствуют записи отсутствующие в данных");
        }
    }
}

?>