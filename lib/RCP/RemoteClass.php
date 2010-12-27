<?php

class RCP_RemoteClass {
    private $_hash = null;
    private $_rcpClient = null;
    
    public function __construct($sHash) {
        $this->_hash = $sHash;
    }
    
    public function getRCPHash() {
        return $this->_hash;
    }
    
    public function setRCPClient(RCP_RemoteClassClient $oClient) {
        $this->_rcpClient = $oClient;
    }
    
    public function __call($sMethodName, $aArguments) {
        return $this->_rcpClient->callMethod($this, $sMethodName, $aArguments);
    }
    
    public function __get($sPropName) {
        return $this->_rcpClient->getProperty($this, $sPropName);
    }
    
    public function __set($sPropName, $mValue) {
        $this->_rcpClient->setProperty($this, $sPropName, $mValue);
    }
    
    public function __isset($sPropName) {
        return $this->_rcpClient->issetProperty($this, $sPropName);
    }
    
    public function __unset($sPropName) {
        $this->_rcpClient->unsetProperty($this, $sPropName);
    }
}