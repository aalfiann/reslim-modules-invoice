<?php

namespace modules\invoice;                          //Make sure namespace is same structure with parent directory

use \classes\Auth as Auth;                          //For authentication internal user
use \classes\JSON as JSON;                          //For handling JSON in better way
use \classes\CustomHandlers as CustomHandlers;      //To get default response message
use PDO;                                            //To connect with database

	/**
     * Invoice management system
     *
     * @package    modules/invoice
     * @author     M ABD AZIZ ALFIAN <github.com/aalfiann>
     * @copyright  Copyright (c) 2018 M ABD AZIZ ALFIAN
     * @license    https://github.com/aalfiann/reSlim-modules-invoice/blob/master/LICENSE.md  MIT License
     */
    class Invoice {

        // database var
        protected $db;

        //base var
        protected $basepath,$baseurl,$basemod;

        //master var
		var $username,$token;
        
        //construct database object
        function __construct($db=null) {
			if (!empty($db)) $this->db = $db;
            $this->baseurl = (($this->isHttps())?'https://':'http://').$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
            $this->basepath = $_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['PHP_SELF']);
			$this->basemod = dirname(__FILE__);
        }

        //Detect scheme host
        function isHttps() {
            $whitelist = array(
                '127.0.0.1',
                '::1'
            );
            
            if(!in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
                if (!empty($_SERVER['HTTP_CF_VISITOR'])){
                    return isset($_SERVER['HTTPS']) ||
                    ($visitor = json_decode($_SERVER['HTTP_CF_VISITOR'])) &&
                    $visitor->scheme == 'https';
                } else {
                    return isset($_SERVER['HTTPS']);
                }
            } else {
                return 0;
            }
        }

        //Get modules information
        public function viewInfo(){
            return file_get_contents($this->basemod.'/package.json');
        }

        /**
         * Build database table 
         */
        public function install(){
            if (Auth::validToken($this->db,$this->token,$this->username)){
                $role = Auth::getRoleID($this->db,$this->token);
                if ($role == 1){
                    try {
                        $this->db->beginTransaction();
                        $sql = file_get_contents(dirname(__FILE__).'/invoice.sql');
                        $stmt = $this->db->prepare($sql);
                        if ($stmt->execute()) {
                            $data = [
                                'status' => 'success',
                                'code' => 'RS101',
                                'message' => CustomHandlers::getreSlimMessage('RS101',$this->lang)
                            ];	
                        } else {
                            $data = [
                                'status' => 'error',
                                'code' => 'RS201',
                                'message' => CustomHandlers::getreSlimMessage('RS201',$this->lang)
                            ];
                        }
                        $this->db->commit();
                    } catch (PDOException $e) {
                        $data = [
                            'status' => 'error',
                            'code' => $e->getCode(),
                            'message' => $e->getMessage()
                        ];
                        $this->db->rollBack();
                    }
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 'RS404',
                        'message' => CustomHandlers::getreSlimMessage('RS404',$this->lang)
                    ];
                }
            } else {
                $data = [
	    			'status' => 'error',
					'code' => 'RS401',
        	    	'message' => CustomHandlers::getreSlimMessage('RS401',$this->lang)
				];
            }

			return JSON::encode($data,true);
			$this->db = null;
        }

        /**
         * Remove database table 
         */
        public function uninstall(){
            if (Auth::validToken($this->db,$this->token,$this->username)){
                $role = Auth::getRoleID($this->db,$this->token);
                if ($role == 1){
                    try {
                        $this->db->beginTransaction();
                        $sql = "DROP TABLE IF EXISTS invoice_data;";
                        $stmt = $this->db->prepare($sql);
                        if ($stmt->execute()) {
                            $data = [
                                'status' => 'success',
                                'code' => 'RS104',
                                'message' => CustomHandlers::getreSlimMessage('RS104',$this->lang)
                            ];
                        } else {
                            $data = [
                                'status' => 'error',
                                'code' => 'RS204',
                                'message' => CustomHandlers::getreSlimMessage('RS204',$this->lang)
                            ];
                        }
                        $this->db->commit();
                    } catch (PDOException $e) {
                        $data = [
                            'status' => 'error',
                            'code' => $e->getCode(),
                            'message' => $e->getMessage()
                        ];
                        $this->db->rollBack();
                    }
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 'RS404',
                        'message' => CustomHandlers::getreSlimMessage('RS404',$this->lang)
                    ];
                }
            } else {
                $data = [
	    			'status' => 'error',
					'code' => 'RS401',
        	    	'message' => CustomHandlers::getreSlimMessage('RS401',$this->lang)
				];
            }

			return JSON::encode($data,true);
			$this->db = null;
        }

    }