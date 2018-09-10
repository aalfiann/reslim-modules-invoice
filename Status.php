<?php
namespace modules\invoice;
use \classes\Auth as Auth;
use \classes\JSON as JSON;
use \classes\CustomHandlers as CustomHandlers;
use PDO;
    /**
     * A class for status invoice
     *
     * @package    modules/invoice
     * @author     M ABD AZIZ ALFIAN <github.com/aalfiann>
     * @copyright  Copyright (c) 2018 M ABD AZIZ ALFIAN
     * @license    https://github.com/aalfiann/reslim-modules-invoice/blob/master/LICENSE.md  MIT License
     */
	class Status {

        // for multi language
        var $lang;

		protected $db;
        
        function __construct($db=null) {
			if (!empty($db)) 
	        {
    	        $this->db = $db;
        	}
		}
		
		/** 
		 * Get all data option Status for invoice
		 * Note, list status in invoice:
		 * - 7 = canceled
		 * - 34 = paid
		 * - 35 = pending
		 * - 37 = rejected
		 * @return array
		 */
		public function optionStatus() {
			$sql = "SELECT a.StatusID,a.Status
					FROM core_status a
					WHERE a.StatusID = '7' OR a.StatusID = '34' OR a.StatusID = '35' OR a.StatusID = '37'
					ORDER BY a.Status ASC";
				
			$stmt = $this->db->prepare($sql);

			if ($stmt->execute()) {	
	    	    if ($stmt->rowCount() > 0){
        	   		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$data = [
			            'results' => $results, 
    			        'status' => 'success', 
		           	    'code' => 'RS501',
    		        	'message' => CustomHandlers::getreSlimMessage('RS501',$this->lang)
					];
			    } else {
        		    $data = [
        		    	'status' => 'error',
	        		    'code' => 'RS601',
    		    	    'message' => CustomHandlers::getreSlimMessage('RS601',$this->lang)
					];
	    	    }          	   	
			} else {
				$data = [
        			'status' => 'error',
					'code' => 'RS202',
	        		'message' => CustomHandlers::getreSlimMessage('RS202',$this->lang)
				];
			}		
        
			return $data;
	        $this->db= null;
        }

		/** 
		 * Show data option Status for user
		 * @return json
		 */
		public function showOptionStatus() {
			if (Auth::validToken($this->db,$this->token)){
				$data = $this->optionStatus();
			} else {
				$data = [
	    			'status' => 'error',
					'code' => 'RS401',
        	    	'message' => CustomHandlers::getreSlimMessage('RS401',$this->lang)
				];
			}		
        
			return JSON::safeEncode($data,true);
	        $this->db= null;
        }
    }