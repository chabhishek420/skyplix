<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Process;

class MockProcess extends \Symfony\Component\Process\Process
{
    private $_stubs = [];
    public function stub($command, $output, $errorOutput = "")
    {
        $this->_stubs[$command] = [$output, $errorOutput];
    }
    public function run(string $run = NULL, $callback = [], $env)
    {
        $this->_throwNotStubbed();
        return 0;
    }
    public function getOutput()
    {
        $this->_throwNotStubbed();
        return $this->_stubs[$this->getCommandLine()][0];
    }
    public function getErrorOutput()
    {
        $this->_throwNotStubbed();
        return $this->_stubs[$this->getCommandLine()][1];
    }
    private function _throwNotStubbed()
    {
        if (!isset($this->_stubs[$this->getCommandLine()])) {
            throw new \Core\Exception("You should stub command for test before running");
        }
    }
}

?>