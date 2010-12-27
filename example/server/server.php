<?php

/**************************/
// Config
/**************************/

$sListenServerHost = '127.0.0.1';
$iListenServerPort = 1667;
$sEncryptionKey = 'secretKey';

/**************************/

require_once dirname(__FILE__) . '/../../lib/RCP.php';

$oServer = new RCP_RemoteClassTcpServer($sListenServerHost, $iListenServerPort, $sEncryptionKey);

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