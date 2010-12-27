<?php

class RCP_TcpServer {
    private $_ip;
	private $_port;
	
	private $_isListening = false;
	
	private $_socket;
    private $_connection;
	
	public function __construct($sIP, $iPort) {
		$this->_ip = $sIP;
		$this->_port = $iPort;
	}
	
	public function __destruct() {
		if ($this->_isListening) {
			$this->disconnect();
		}
	}
    
    public function __wakeup() {
        if ($this->_isListening) {
            $this->listen();            
        }    
    }        
	
	public function listen($iTimeout = 30) {
		$this->_socket = @stream_socket_server('tcp://'.$this->_ip . ':' . $this->_port, $errorCode, $errorString, $iTimeout);
		
		if ($this->_socket === false) {
			throw new Exception('Listen failed!');
		}
		
		$this->_isListening = true;
	}
    
    public function waitForConnection(&$sPeerName, $iTimeout = 30) {
        $this->_connection = @stream_socket_accept($this->_socket, $iTimeout, $sPeerName);
        
        if ($this->_connection !== false) {
            return true;
        }
        else {
            return false;
        }
    }
	
	public function disconnect() {
        if ($this->_connection) {
			fclose($this->_connection);
		}
	}
    
    public function setTimeout($iTimeoutSeconds) {
        if ($this->_isListening) {
            stream_set_timeout($this->_connection, $iTimeoutSeconds);
        }
    }
    
    public function flush() {
        if ($this->_isListening) {
            return fflush($this->_connection);
        }
    }
    
    public function isClosed() {
        return feof($this->_connection);
    }
    
    public function isReady() {
        $aReadStreams = array($this->_connection);
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
       
        $mData = fread($this->_connection, $length);
        
        if ($mData === false) {
            throw new Exception('Read failure!');
        }
       
        return $mData;
    }
    
    public function readLine($length = -1) {
    	if ($length > -1) {
    		return rtrim(fgets($this->_connection, $length));
    	}
    	else {
    		return rtrim(fgets($this->_connection));
    	}
    }
    
    public function write($data) {
    	return fwrite($this->_connection, $data);
    } 
    
    public function writeLine($data) {
    	return fwrite($this->_connection, $data . PHP_EOL);
    }
}