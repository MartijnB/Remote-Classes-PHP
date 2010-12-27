<?php

/**************************/
// Config
/**************************/

//See run.sh for the listen port

$sEncryptionKey = 'secretKey';

/**************************/

/**
 * 
 * WARNING
 * 
 * Echo, print, printf etc will output to stdout! The stdout output is
 * written to the client als response data. Use stderr to output to the
 * console!
 * 
 */

require_once dirname(__FILE__) . '/../../lib/RCP.php';

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

//open the pipe and process the data
$oServer = new RCP_RemoteClassPipeServer($sEncryptionKey);
$oServer->processRequest();