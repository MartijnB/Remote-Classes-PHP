<?php

require_once dirname(__FILE__) . '/../../lib/RCP.php';

$oServer = new RCP_RemoteClassTcpServer('0.0.0.0', 1667, 'secretKey');

class RemoteMathClass implements RCP_IRemoteCallable {
    /*public function __construct($iValue = 0) {
        throw new Exception($iValue);
    }*/
    
    public function add($a, $b) {
        return $a + $b;
    }
}

class RemoteClass implements RCP_IRemoteCallable {
    public function getWelcome() {
        return 'Hello World';
    }
}

class RemoteReturnClass implements RCP_IRemoteCallable {
    private $_remoteClass;
    
    public function __construct() {
        $this->_remoteClass = new RemoteClass();
    }
    
    public function returnValue($mValue) {
        return $mValue;
    }
    
    public function getRemoteClass() {
        return $this->_remoteClass;
    }
}

while ($oServer->processRequests()) {
    usleep(100);
}