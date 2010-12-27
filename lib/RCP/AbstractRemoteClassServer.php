<?php

abstract class RCP_AbstractRemoteClassServer {
    private $_instances = array();
    
    protected abstract function _readCommand(&$iCommandId, &$iSeqId);
    protected abstract function _writeResponse($iSeqId, $mData);
    
    protected abstract function _info($sMsg);
    
    protected function _processCommand() {
        $this->_info('Read Command...' . PHP_EOL);
        $mData = $this->_readCommand($iCommandId, $iSeqId);
        
        switch($iCommandId) {
            case RCP_CMD_WELCOME:
                $this->_info('CMD: Welcome' . PHP_EOL);
                $this->_writeResponse($iSeqId, RCP_PROTOCOL_WELCOME);
                break;
                
            case RCP_CMD_SPAWN_OBJECT:
                $this->_info('CMD: Spawn ' . $mData['class'] . PHP_EOL);
                
                if (!class_exists($mData['class'])) {
                    spl_autoload_call($mData['class']);
                }
                
                if (class_exists($mData['class'])) {
                    $oClassReflection = new ReflectionClass($mData['class']);
                    
                    if ($oClassReflection->implementsInterface('RCP_IRemoteCallable')) {
                        try {
                            if ($oClassReflection->hasMethod('__construct')) {
                                $oInstance = $oClassReflection->newInstanceArgs($mData['arguments']);
                            }
                            else {
                                $oInstance = $oClassReflection->newInstance ();
                            }
                            
                            $this->_info('Object hash: ' . spl_object_hash($oInstance) . PHP_EOL);
                            
                            $this->_instances[spl_object_hash($oInstance)] = $oInstance;
                            
                            $this->_writeResponse($iSeqId, array(
                                'status' => RCP_STATUS_OK,
                                'hash' => spl_object_hash($oInstance)
                            ));
                        }
                        catch (Exception $e) {
                            $this->_writeResponse($iSeqId, array(
                                'status' => RCP_STATUS_EXCEPTION,
                                'exception' => $e
                            ));
                        }
                    }
                    else {
                        $this->_writeResponse($iSeqId, array(
                            'status' => RCP_STATUS_ERROR,
                            'error' => 'Class doesn\'t implement RCP_IRemoteCallable!'
                        ));
                    }
                }
                else {
                    $this->_writeResponse($iSeqId, array(
                        'status' => RCP_STATUS_ERROR,
                        'error' => 'Class doesn\'t exists!'
                    ));
                }
                
                break;
                
            case RCP_CMD_CALL_METHOD:
                $this->_info('CMD: Call method ' . PHP_EOL);
                
                if (isset($this->_instances[$mData['objectHash']])) {
                    if (method_exists($this->_instances[$mData['objectHash']], $mData['method'])) {
                        $this->_info(get_class($this->_instances[$mData['objectHash']]) . '->' . $mData['method'] . '()' . PHP_EOL);
                        
                        try {
                            $mReturnValue = call_user_func_array(array($this->_instances[$mData['objectHash']], $mData['method']), $mData['arguments']);
                            
                            //filter objects out
                            $mReturnValue = $this->_filterObjects($mReturnValue);
                            
                            $this->_writeResponse($iSeqId, array(
                                'status' => RCP_STATUS_OK,
                                'returnValue' => $mReturnValue
                            ));
                        }
                        catch (Exception $e) {
                            $this->_writeResponse($iSeqId, array(
                                'status' => RCP_STATUS_EXCEPTION,
                                'exception' => $e
                            ));
                        }
                    }
                    else {
                        $this->_writeResponse($iSeqId, array(
                            'status' => RCP_STATUS_ERROR,
                            'error' => 'Method doesn\'t exist!'
                        ));
                    }
                }
                else {
                    $this->_writeResponse($iSeqId, array(
                        'status' => RCP_STATUS_ERROR,
                        'error' => 'Unknown object!'
                    ));
                }
                
                break;
                
            case RCP_CMD_GET_PROPERTY:
                $this->_info('CMD: Get property ' . PHP_EOL);
            
                if (isset($this->_instances[$mData['objectHash']])) {
                    if (property_exists($this->_instances[$mData['objectHash']], $mData['property'])) {
                        $this->_info('Get property ' . get_class($this->_instances[$mData['objectHash']]) . '->' . $mData['property'] . PHP_EOL);
                        
                        try {
                            $oInstance = $this->_instances[$mData['objectHash']];
                            $mReturnValue = $oInstance->{$mData['property']};
                            
                            //filter objects out to prevent the transfering of them to the client
                            $mReturnValue = $this->_filterObjects($mReturnValue);
                            
                            $this->_writeResponse($iSeqId, array(
                                'status' => RCP_STATUS_OK,
                                'returnValue' => $mReturnValue
                            ));
                        }
                        catch (Exception $e) {
                            $this->_writeResponse($iSeqId, array(
                                'status' => RCP_STATUS_EXCEPTION,
                                'exception' => $e
                            ));
                        }
                    }
                    else {
                        $this->_writeResponse($iSeqId, array(
                            'status' => RCP_STATUS_ERROR,
                            'error' => 'Property doesn\'t exist!'
                        ));
                    }
                }
                else {
                    $this->_writeResponse($iSeqId, array(
                        'status' => RCP_STATUS_ERROR,
                        'error' => 'Unknown object!'
                    ));
                }
                
                break;
                
            case RCP_CMD_SET_PROPERTY:
                $this->_info('CMD: Set property ' . PHP_EOL);
            
                if (isset($this->_instances[$mData['objectHash']])) {
                    //if (property_exists($this->_instances[$mData['objectHash']], $mData['property'])) {
                        $this->_info('Set property ' . get_class($this->_instances[$mData['objectHash']]) . '->' . $mData['property'] . PHP_EOL);
                        
                        try {
                            $oInstance = $this->_instances[$mData['objectHash']];
                            $oInstance->{$mData['property']} = $mData['value'];
                            
                            $this->_writeResponse($iSeqId, array(
                                'status' => RCP_STATUS_OK
                            ));
                        }
                        catch (Exception $e) {
                            $this->_writeResponse($iSeqId, array(
                                'status' => RCP_STATUS_EXCEPTION,
                                'exception' => $e
                            ));
                        }
                    /*}
                    else {
                        $this->_writeResponse($iSeqId, array(
                            'status' => RCP_STATUS_ERROR,
                            'error' => 'Property doesn\'t exist!'
                        ));
                    }*/
                }
                else {
                    $this->_writeResponse($iSeqId, array(
                        'status' => RCP_STATUS_ERROR,
                        'error' => 'Unknown object!'
                    ));
                }
                
                break;
                
            case RCP_CMD_ISSET_PROPERTY:
                $this->_info('CMD: Isset property ' . PHP_EOL);
            
                if (isset($this->_instances[$mData['objectHash']])) {
                    $this->_info('Isset property ' . get_class($this->_instances[$mData['objectHash']]) . '->' . $mData['property'] . PHP_EOL);
                        
                    $oInstance = $this->_instances[$mData['objectHash']];
                    
                    if (isset($oInstance->{$mData['property']})) {
                        $mReturnValue = true;
                    }
                    else {
                        $mReturnValue = false;
                    }
                    
                    $this->_writeResponse($iSeqId, array(
                        'status' => RCP_STATUS_OK,
                        'returnValue' => $mReturnValue
                    ));
                }
                else {
                    $this->_writeResponse($iSeqId, array(
                        'status' => RCP_STATUS_ERROR,
                        'error' => 'Unknown object!'
                    ));
                }
                
                break;
                
            case RCP_CMD_UNSET_PROPERTY:
                $this->_info('CMD: Unset property ' . PHP_EOL);
            
                if (isset($this->_instances[$mData['objectHash']])) {
                    $this->_info('Unset property ' . get_class($this->_instances[$mData['objectHash']]) . '->' . $mData['property'] . PHP_EOL);
                        
                    $oInstance = $this->_instances[$mData['objectHash']];
                    
                    unset($oInstance->{$mData['property']});
                    
                    $this->_writeResponse($iSeqId, array(
                        'status' => RCP_STATUS_OK
                    ));
                }
                else {
                    $this->_writeResponse($iSeqId, array(
                        'status' => RCP_STATUS_ERROR,
                        'error' => 'Unknown object!'
                    ));
                }
                
                break;
                
            default:
                throw new Exception('Unknown command: '.$iCommandId.'!');
        }
    }
    
    private function _filterObjects($mValue) {
        if (is_array($mValue)) {
            foreach($mValue as $sAKey => $mAValue) {
                if (is_array($mAValue)) {
                    $mValue[$sAKey] = $this->_filterObjects($mAValue);
                }
                else if (is_object($mAValue)) {
                    if (!$mAValue instanceof RCP_RemoteClass) {
                        $sObjectHash = spl_object_hash($mAValue);
                        
                        if (!isset($this->_instances[$sObjectHash])) {
                            $this->_instances[$sObjectHash] = $mAValue;
                        }
                        
                        $mValue[$sAKey] = new RCP_RemoteClass($sObjectHash);
                    }
                }
            }
        }
        else if (is_object($mValue)) {
            if (!$mValue instanceof RCP_RemoteClass) {
                $sObjectHash = spl_object_hash($mValue);
                
                if (!isset($this->_instances[$sObjectHash])) {
                    $this->_instances[$sObjectHash] = $mValue;
                }
                
                $mValue = new RCP_RemoteClass($sObjectHash);
            }
        }
        
        return $mValue;
    }
}