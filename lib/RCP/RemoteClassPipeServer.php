<?php

class RCP_RemoteClassPipeServer extends RCP_AbstractRemoteClassServer {
    private $_inPipe = null;
    private $_outPipe = null;
    private $_errPipe = null;
    
    private $_secretKey = null;
    
    private $_instances = array();
    
    public function __construct($sKey) {
        $this->_inPipe = new RCP_Pipe('php://stdin', 'r');
        $this->_outPipe = new RCP_Pipe('php://stdout', 'w');
        $this->_errPipe = new RCP_Pipe('php://stderr', 'w');
        
        $this->_secretKey = $sKey;
    }
    
    public function __destruct() {
        unset($this->_inPipe);
        unset($this->_outPipe);
        unset($this->_errPipe);
    }
    
    public function processRequest($iTimeout = 10) {            
        $fLastCommand = microtime(true);
        while (!$this->_inPipe->isClosed() && $fLastCommand > (time() - $iTimeout)) {
            $iCommandId = null;
            $iSeqId = null;
            
            if ($this->_inPipe->isReady() && !$this->_inPipe->isClosed()) {
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
    
    protected function _info($sMsg) {
        $this->_errPipe->write($sMsg);
    }
    
    protected function _readCommand(&$iCommandId, &$iSeqId) {
        $sLength = '';
        while (($cLength = $this->_inPipe->read(1)) != ' ') {
            $sLength .= $cLength;
        }
        
        $iLength = (int)$sLength;
        
        
        //data
        $sEncodedRawData = $this->_inPipe->read($sLength);
        
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
        
        $this->_outPipe->write(strlen($sPacketData) . ' ' . $sPacketData);
    }
    
    private function _encodeData($sData) {
        return mcrypt_ecb(MCRYPT_3DES, $this->_secretKey, $sData, MCRYPT_ENCRYPT);
    }
    
    private function _decodeData($sData) {
        return mcrypt_ecb(MCRYPT_3DES, $this->_secretKey, $sData, MCRYPT_DECRYPT);
    }
}