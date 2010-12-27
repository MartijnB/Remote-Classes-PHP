<?php

class RCP_RemoteClassTcpServer extends RCP_AbstractRemoteClassServer {
    private $_tcpServer = null;
    
    private $_secretKey = null;
    
    private $_instances = array();
    
    public function __construct($sHostname, $iPort, $sKey) {
        $this->_tcpServer = new RCP_TcpServer($sHostname, $iPort);
        $this->_tcpServer->listen();
        
        $this->_secretKey = $sKey;
    }
    
    public function __destruct() {
        unset($this->_tcpServer);
    }
    
    public function processRequests($iTimeout = 10) {
        $sClient = null;
        if ($this->_tcpServer->waitForConnection($sClient)) {
            $this->_info('New connection from ' . $sClient . PHP_EOL);
            
            //reset the instances
            $this->_instances = array();
            
            $fLastCommand = microtime(true);
            while (!$this->_tcpServer->isClosed() && $fLastCommand > (time() - $iTimeout)) {
                $iCommandId = null;
                $iSeqId = null;
                
                if ($this->_tcpServer->isReady() && !$this->_tcpServer->isClosed()) {
                    $this->_processCommand();
                }
                
                usleep(100);
            }
            
            if ($fLastCommand > (time() - $iTimeout)) {
                $this->_info('Connection closed!' . PHP_EOL);                    
            }
            else {
                $this->_info('No new command, close connection!' . PHP_EOL);
            }
        }
        
        return true;
    }
    
    protected function _info($sMsg) {
        echo $sMsg;
    }
    
    protected function _readCommand(&$iCommandId, &$iSeqId) {
        $sLength = '';
        while (($cLength = $this->_tcpServer->read(1)) != ' ') {
            $sLength .= $cLength;
        }
        
        $iLength = (int)$sLength;
        
        
        //data
        $sEncodedRawData = $this->_tcpServer->read($sLength);
        
        if (strlen($sEncodedRawData) != $iLength) {
            throw new Exception('Invalid packet length!');
        }
        
        $sRawData = $this->_decodeData($sEncodedRawData);
        
        //command
        $iCommandIdEndPos = strpos($sRawData, ' ');
        $iSeqIdEndPos = strpos($sRawData, ' ', $iCommandIdEndPos + 1);
        
        $iCommandId = (int)substr($sRawData, 0, $iCommandIdEndPos);
        $iSeqId = (int)substr($sRawData, $iCommandIdEndPos + 1, $iSeqIdEndPos);
        $sData = substr($sRawData, $iSeqIdEndPos + 1, strlen($sRawData));
        
        $mData = @unserialize($sData);
        
        if ($mData === false) {
            throw new Exception('Can\'t read command!');
        }
        
        return $mData;
    }
    
    protected function _writeResponse($iSeqId, $mData) {
        $sPacketData = $this->_encodeData($iSeqId . ' ' . serialize($mData));  
        
        $this->_tcpServer->write(strlen($sPacketData) . ' ' . $sPacketData);
    }
    
    private function _encodeData($sData) {
        return mcrypt_ecb(MCRYPT_3DES, $this->_secretKey, $sData, MCRYPT_ENCRYPT);
    }
    
    private function _decodeData($sData) {
        return mcrypt_ecb(MCRYPT_3DES, $this->_secretKey, $sData, MCRYPT_DECRYPT);
    }
}