<?php

class RCP_RemoteClassClient {
    private $_tcpClient = null;
    
    private $_secretKey = null;
    
    private $_seqID = 0;
    
    private $_instances = array();
    
    public function __construct($sHostname, $iPort, $sKey) {
        $this->_tcpClient = new RCP_TcpClient($sHostname, $iPort);
        $this->_tcpClient->connect();
        
        $this->_secretKey = $sKey;
        
        if (!$this->testConnection()) {
            throw new Exception('Connecting failed! Wrong protocol or secret key?');
        }
    }
    
    public function __destruct() {
        unset($this->_tcpClient);
    }
    
    public function testConnection() {
        $this->_sendCommand(RCP_CMD_WELCOME, RCP_PROTOCOL_WELCOME);
        
        $sOutput = $this->_readResponse();
        
        return ($sOutput == RCP_PROTOCOL_WELCOME);
    }

    public function getObject($sClassName) {
        if (!isset($this->_instances[$sClassName])) {
            $aArguments = func_get_args();
            array_shift($aArguments);
            
            $this->_instances[$sClassName] = $this->getNewObjectWithArg($sClassName, $aArguments);
        }
        
        return $this->_instances[$sClassName];
    }
    
    public function getNewObject($sClassName) {
        $aArguments = func_get_args();
        array_shift($aArguments);
        
        return $this->getNewObjectWithArg($sClassName, $aArguments);
    }
    
    public function getNewObjectWithArg($sClassName, array $aArguments = array()) {
        $this->_sendCommand(RCP_CMD_SPAWN_OBJECT, array(
            'class' => $sClassName,
            'arguments' => $aArguments
        ));
        
        $aResponseData = $this->_readResponse();
        
        switch($aResponseData['status']) {
            case RCP_STATUS_OK:
                $oRemoteClass = new RCP_RemoteClass($aResponseData['hash']);
                $oRemoteClass->setRCPClient($this);
            
                return $oRemoteClass;
                
            case RCP_STATUS_ERROR:
                throw new Exception($aResponseData['error']);
                
            case RCP_STATUS_EXCEPTION:
                throw $aResponseData['exception'];
        }
    }
    
    public function callMethod(RCP_RemoteClass $oRemoteClass, $sMethodName, $aArguments) {
        $this->_sendCommand(RCP_CMD_CALL_METHOD, array(
            'objectHash' => $oRemoteClass->getRCPHash(),
            'method' => $sMethodName,
            'arguments' => $aArguments
        ));
        
        $aResponseData = $this->_readResponse();
        
        switch($aResponseData['status']) {
            case RCP_STATUS_OK:
                $mReturnValue = $aResponseData['returnValue'];
                
                $mReturnValue = $this->_fillRemoteObjects($mReturnValue);
            
                return $mReturnValue;
                
            case RCP_STATUS_ERROR:
                throw new Exception($aResponseData['error']);
                
            case RCP_STATUS_EXCEPTION:
                throw $aResponseData['exception'];
        }
    }
    
    public function getProperty(RCP_RemoteClass $oRemoteClass, $sPropertyName) {
        $this->_sendCommand(RCP_CMD_GET_PROPERTY, array(
            'objectHash' => $oRemoteClass->getRCPHash(),
            'property' => $sPropertyName
        ));
        
        $aResponseData = $this->_readResponse();
        
        switch($aResponseData['status']) {
            case RCP_STATUS_OK:
                $mReturnValue = $aResponseData['returnValue'];
                
                $mReturnValue = $this->_fillRemoteObjects($mReturnValue);
            
                return $mReturnValue;
                
            case RCP_STATUS_ERROR:
                throw new Exception($aResponseData['error']);
                
            case RCP_STATUS_EXCEPTION:
                throw $aResponseData['exception'];
        }
    }
    
    public function setProperty(RCP_RemoteClass $oRemoteClass, $sPropertyName, $mValue) {
        $this->_sendCommand(RCP_CMD_SET_PROPERTY, array(
            'objectHash' => $oRemoteClass->getRCPHash(),
            'property' => $sPropertyName,
            'value' => $mValue
        ));
        
        $aResponseData = $this->_readResponse();
        
        switch($aResponseData['status']) {
            case RCP_STATUS_OK:
                return;
                
            case RCP_STATUS_ERROR:
                throw new Exception($aResponseData['error']);
                
            case RCP_STATUS_EXCEPTION:
                throw $aResponseData['exception'];
        }
    }
    
    public function issetProperty(RCP_RemoteClass $oRemoteClass, $sPropertyName) {
        $this->_sendCommand(RCP_CMD_ISSET_PROPERTY, array(
            'objectHash' => $oRemoteClass->getRCPHash(),
            'property' => $sPropertyName
        ));
        
        $aResponseData = $this->_readResponse();
        
        switch($aResponseData['status']) {
            case RCP_STATUS_OK:
                return $aResponseData['returnValue'];
                
            case RCP_STATUS_ERROR:
                throw new Exception($aResponseData['error']);
                
            case RCP_STATUS_EXCEPTION:
                throw $aResponseData['exception'];
        }
    }
    
    public function unsetProperty(RCP_RemoteClass $oRemoteClass, $sPropertyName) {
        $this->_sendCommand(RCP_CMD_UNSET_PROPERTY, array(
            'objectHash' => $oRemoteClass->getRCPHash(),
            'property' => $sPropertyName
        ));
        
        $aResponseData = $this->_readResponse();
        
        switch($aResponseData['status']) {
            case RCP_STATUS_OK:
                return;
                
            case RCP_STATUS_ERROR:
                throw new Exception($aResponseData['error']);
                
            case RCP_STATUS_EXCEPTION:
                throw $aResponseData['exception'];
        }
    }
    
    private function _fillRemoteObjects($mValue) {
        if (is_array($mValue)) {
            foreach($mValue as $sAKey => $mAValue) {
                if (is_array($mAValue)) {
                    $mValue[$sAKey] = $this->_fillRemoteObjects($mAValue);
                }
                else if (is_object($mAValue)) {
                    if ($mAValue instanceof RCP_RemoteClass) {
                        $mValue[$sAKey]->setRCPClient($this);
                    }
                }
            }
        }
        else if (is_object($mValue)) {
            if ($mValue instanceof RCP_RemoteClass) {
                $mValue->setRCPClient($this);
            }
        }
        
        return $mValue;
    }

    private function _sendCommand($iCommand, $mData) {
        $sPacketData = $this->_encodeData($iCommand . ' ' . (++$this->_seqID) . ' ' . serialize($mData));  
        
        $this->_tcpClient->write(strlen($sPacketData) . ' ' . $sPacketData);
    }
    
    private function _readResponse(&$iSeqId = null) {
        $sLength = '';
        while (($cLength = $this->_tcpClient->read(1)) != ' ') {
            $sLength .= $cLength;
        }
        
        $iLength = (int)$sLength;
        
        //data
        $sEncodedRawData = $this->_tcpClient->read($sLength);
        
        if (strlen($sEncodedRawData) != $iLength) {
            throw new Exception('Invalid packet length!');
        }
        
        $sRawData = $this->_decodeData($sEncodedRawData);
        
        //command
        $iSeqIdEndPos = strpos($sRawData, ' ');
        
        $iSeqId = (int)substr($sRawData, 0, $iSeqIdEndPos);
        $sData = substr($sRawData, $iSeqIdEndPos + 1, strlen($sRawData));
        
        $mData = @unserialize($sData);
        
        if ($mData === false) {
            throw new Exception('Can\'t read command!');
        }
        
        return $mData;
    }
    
    private function _encodeData($sData) {
        return mcrypt_ecb(MCRYPT_3DES, $this->_secretKey, $sData, MCRYPT_ENCRYPT);
    }
    
    private function _decodeData($sData) {
        return mcrypt_ecb(MCRYPT_3DES, $this->_secretKey, $sData, MCRYPT_DECRYPT);
    }
}