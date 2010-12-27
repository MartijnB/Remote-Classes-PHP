<?php

class RCP_Pipe {
    private $_pipe;
    
    public function __construct($sPipeName, $sOptions = 'rw') {
        $this->_pipe = fopen($sPipeName, $sOptions);
    }
    
    public function __destruct() {
    	fclose($this->_pipe); 
    }
    
    public function isClosed() {
        return feof($this->_pipe);
    }
    
    public function isReady() {
        $aReadStreams = array($this->_pipe);
        $aWriteStreams = array();
        
        if (stream_select($aReadStreams, $aWriteStreams, $aWriteStreams, 1) !== false) {
            return true;
        }
        else {
            return false;
        }
    }
    
    public function read($length = 8192) {
        if ($this->isClosed()) {
            throw new Exception('Socket closed!');
        }
       
        $mData = fread($this->_pipe, $length);
        
        if ($mData === false) {
            throw new Exception('Read failure!');
        }
       
        return $mData;
    }
    
    public function readLine($length = -1) {
    	if ($length > -1) {
    		return rtrim(fgets($this->_pipe, $length));
    	}
    	else {
    		return rtrim(fgets($this->_pipe));
    	}
    }
    
    public function write($data) {
    	return fwrite($this->_pipe, $data);
    } 
    
    public function writeLine($data) {
    	return fwrite($this->_pipe, $data . PHP_EOL);
    }
}