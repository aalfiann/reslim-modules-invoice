<?php
namespace modules\invoice;
use \classes\Auth as Auth;
use \classes\JSON as JSON;
use \classes\CustomHandlers as CustomHandlers;
use \classes\Validation as Validation;
use PDO;
    /**
     * A class for data invoice
     *
     * @package    modules/invoice
     * @author     M ABD AZIZ ALFIAN <github.com/aalfiann>
     * @copyright  Copyright (c) 2018 M ABD AZIZ ALFIAN
     * @license    https://github.com/aalfiann/reslim-modules-invoice/blob/master/LICENSE.md  MIT License
     */
	class Data {

        protected $db;
        
        //data var
        var $invoiceid,
            $from_name,$from_name_company,$from_address,$from_phone,$from_fax,$from_email,$from_website,
            $to_name,$to_name_company,$to_address,$to_phone,$to_fax,$to_email,$to_website,
            $custom_id,$custom_field,$data_table,
            $total_sub,$total,$term,$signature,
            $statusid,$created_at,$created_by,$updated_at,$updated_by;

        //fixed data var
        var $prefix = 'INV';

        //search var
        var $search,$firstdate,$lastdate;

        //pagination var
		var $page,$itemsPerPage;
        
        // for multi language
        var $lang;

        function __construct($db=null) {
			if (!empty($db)) 
	        {
    	        $this->db = $db;
        	}
		}
		
		private function generateID(){
            return Auth::uniqidNumeric($this->prefix);
        }

        /**
         * Modify json data string in some field array to be nice json data structure
		 * 
		 * Note:
		 * - When you put json into database, then you load it with using PDO:fetch() or PDO::fetchAll() it will be served as string inside some field array.
		 * - So this function is make you easier to modify the json string to become nice json data structure automatically.
		 * - This function is well tested at here >> https://3v4l.org/G8Jaa
         * 
         * @var data is the data array
         * @var jsonfield is the field which is contains json string
         * @var setnewfield is to put the result of modified json string in new field
         * @return mixed array or string (if the $data is string then will return string)
         */
		private function modifyJsonStringInArray($data,$jsonfield,$setnewfield=""){
			if (is_array($data)){
				if (count($data) == count($data, COUNT_RECURSIVE)) {
					foreach($data as $value){
						if(!empty($setnewfield)){
							if (is_array($jsonfield)){
								for ($i=0;$i<count($jsonfield);$i++){
									if (isset($data[$jsonfield[$i]])){
										$data[$setnewfield[$i]] = json_decode($data[$jsonfield[$i]]);
									}
								}
							} else {
								if (isset($data[$jsonfield])){
									$data[$setnewfield] = json_decode($data[$jsonfield]);
								}
							}
						} else {
							if (is_array($jsonfield)){
								for ($i=0;$i<count($jsonfield);$i++){
									if (isset($data[$jsonfield[$i]])){
										if (is_string($data[$jsonfield[$i]])) {
                                            $decode = json_decode($data[$jsonfield[$i]]);
                                            if (!empty($decode)) $data[$jsonfield[$i]] = $decode;
                                        }
									}
								}
							} else {
								if (isset($data[$jsonfield])){
                                    $decode = json_decode($data[$jsonfield]);
                                    if (!empty($decode)) $data[$jsonfield] = $decode;
								}
							}
						}
					}
				} else {
					foreach($data as $key => $value){
						$data[$key] = $this->modifyJsonStringInArray($data[$key],$jsonfield,$setnewfield);
					}
				}
			}
			return $data;
        }
        
        private function searchKeyword($columndb,$text,$options='or',$paramkey=':keywords',$textdelimiter=','){
            $datakey = explode($textdelimiter,$text);
			$listkeys = "";
            $n=0;
            if(!empty($datakey[0])){
                foreach($datakey as $value){
                    if(!empty(trim($value))){ 
                        $listkeys .= 'INSTR('.$columndb.','.$paramkey.$n.') '.trim($options).' ';
                        $n++;
                    }
                }
            }

            $listkeys = rtrim($listkeys," ".trim($options)." ");
            return $listkeys;
        }

        private function selectKeyword($columndb,$text,$options='or',$paramkey=':keywords',$textdelimiter=','){
            $datakey = explode($textdelimiter,$text);
			$listkeys = "";
            $n=0;
            if(!empty($datakey[0])){
                foreach($datakey as $value){
                    if(!empty(trim($value))){ 
                        $listkeys .= $columndb.'='.$paramkey.$n.' '.trim($options).' ';
                        $n++;
                    }
                }
            }

            $listkeys = rtrim($listkeys," ".trim($options)." ");
            return $listkeys;
        }

        private function paramKeyword($columndb,$text,$paramkey=':keywords',$textdelimiter=','){
            $datakey = explode($textdelimiter,$text);
            $listdata = array();
            $n=0;
            if(!empty($datakey[0])){
                foreach($datakey as $value){
                    if(!empty(trim($value))){ 
                        $listdata[$paramkey.$n] = trim($value);
                        $n++;
                    }
                }
            }
            return $listdata;
        }

        public function createData(){
            $newusername = strtolower($this->username);
            $newtotalsub = Validation::numericOnly($this->total_sub);
            $newtotal = Validation::numericOnly($this->total);
            $newterm = Validation::integerOnly($this->term);
            $newfromphone = Validation::integerOnly($this->from_phone);
            $newfromfax = Validation::integerOnly($this->from_fax);
            $newtophone = Validation::integerOnly($this->to_phone);
            $newtofax = Validation::integerOnly($this->to_fax);
            $newinvoiceid = $this->generateID();
            try {
                $this->db->beginTransaction();
                $sql = "INSERT INTO invoice_data (
                        InvoiceID,
                        From_name,From_name_company,From_address,From_phone,From_fax,From_email,From_website,
                        To_name,To_name_company,To_address,To_phone,To_fax,To_email,To_website,
                        Custom_id,Custom_field,Data_table,
                        Total_sub,Total,Term,Signature,
                        StatusID,Created_at,Created_by
                    ) 
                    VALUES (
                        :invoiceid,
                        :from_name,:from_name_company,:from_address,:from_phone,:from_fax,:from_email,:from_website,
                        :to_name,:to_name_company,:to_address,:to_phone,:to_fax,:to_email,:to_website,
                        :custom_id,:custom_field,:data_table,
                        :total_sub,:total,:term,:signature,
                        '35',current_timestamp,:username
                    );";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':invoiceid', $newinvoiceid, PDO::PARAM_STR);
                
                $stmt->bindParam(':from_name', $this->from_name, PDO::PARAM_STR);
                $stmt->bindParam(':from_name_company', $this->from_name_company, PDO::PARAM_STR);
                $stmt->bindParam(':from_address', $this->from_address, PDO::PARAM_STR);
                $stmt->bindParam(':from_phone', $newfromphone, PDO::PARAM_STR);
                $stmt->bindParam(':from_fax', $newfromfax, PDO::PARAM_STR);
                $stmt->bindParam(':from_email', $this->from_email, PDO::PARAM_STR);
                $stmt->bindParam(':from_website', $this->from_website, PDO::PARAM_STR);

                $stmt->bindParam(':to_name', $this->to_name, PDO::PARAM_STR);
                $stmt->bindParam(':to_name_company', $this->to_name_company, PDO::PARAM_STR);
                $stmt->bindParam(':to_address', $this->to_address, PDO::PARAM_STR);
                $stmt->bindParam(':to_phone', $newtophone, PDO::PARAM_STR);
                $stmt->bindParam(':to_fax', $newtofax, PDO::PARAM_STR);
                $stmt->bindParam(':to_email', $this->to_email, PDO::PARAM_STR);
                $stmt->bindParam(':to_website', $this->to_website, PDO::PARAM_STR);

                $stmt->bindParam(':custom_id', $this->custom_id, PDO::PARAM_STR);
                $stmt->bindParam(':custom_field', $this->custom_field, PDO::PARAM_STR);
                $stmt->bindParam(':data_table', $this->data_table, PDO::PARAM_STR);

                $stmt->bindParam(':total_sub', $newtotalsub, PDO::PARAM_STR);
                $stmt->bindParam(':total', $newtotal, PDO::PARAM_STR);
                $stmt->bindParam(':term', $newterm, PDO::PARAM_STR);
                $stmt->bindParam(':signature', $this->signature, PDO::PARAM_STR);

                $stmt->bindParam(':username', $newusername, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $data = [
                        'status' => 'success',
                        'invoiceid' => $newinvoiceid,
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
            return $data;
        }

        public function updateData(){
            $newusername = strtolower($this->username);
            $newtotalsub = Validation::numericOnly($this->total_sub);
            $newtotal = Validation::numericOnly($this->total);
            $newterm = Validation::integerOnly($this->term);
            $newfromphone = Validation::integerOnly($this->from_phone);
            $newfromfax = Validation::integerOnly($this->from_fax);
            $newtophone = Validation::integerOnly($this->to_phone);
            $newtofax = Validation::integerOnly($this->to_fax);
            $newstatusid = Validation::integerOnly($this->statusid);
            try {
                $this->db->beginTransaction();
                
                $sql = "UPDATE invoice_data a 
                    SET
                        a.From_name=:from_name,a.From_name_company=:from_name_company,a.From_address=:from_address,a.From_phone=:from_phone,a.From_fax=:from_fax,
                            a.From_email=:from_email,a.From_website=:from_website,
                        a.To_name=:to_name,a.To_name_company=:to_name_company,a.To_address=:to_address,a.To_phone=:to_phone,a.To_fax=:to_fax,
                            a.To_email=:to_email,a.To_website=:to_website,
                        a.Custom_id=:custom_id,a.Custom_field=:custom_field,a.Data_table=:data_table,
                        a.Total_sub=:total_sub,a.Total=:total,a.Term=:term,a.Signature=:signature,
                        a.StatusID=:statusid,a.Updated_at=current_timestamp,Updated_by=:username
                    WHERE
                        a.InvoiceID=:invoiceid;";
                
                $stmt = $this->db->prepare($sql);
                
                $stmt->bindParam(':from_name', $this->from_name, PDO::PARAM_STR);
                $stmt->bindParam(':from_name_company', $this->from_name_company, PDO::PARAM_STR);
                $stmt->bindParam(':from_address', $this->from_address, PDO::PARAM_STR);
                $stmt->bindParam(':from_phone', $newfromphone, PDO::PARAM_STR);
                $stmt->bindParam(':from_fax', $newfromfax, PDO::PARAM_STR);
                $stmt->bindParam(':from_email', $this->from_email, PDO::PARAM_STR);
                $stmt->bindParam(':from_website', $this->from_website, PDO::PARAM_STR);

                $stmt->bindParam(':to_name', $this->to_name, PDO::PARAM_STR);
                $stmt->bindParam(':to_name_company', $this->to_name_company, PDO::PARAM_STR);
                $stmt->bindParam(':to_address', $this->to_address, PDO::PARAM_STR);
                $stmt->bindParam(':to_phone', $newtophone, PDO::PARAM_STR);
                $stmt->bindParam(':to_fax', $newtofax, PDO::PARAM_STR);
                $stmt->bindParam(':to_email', $this->to_email, PDO::PARAM_STR);
                $stmt->bindParam(':to_website', $this->to_website, PDO::PARAM_STR);

                $stmt->bindParam(':custom_id', $this->custom_id, PDO::PARAM_STR);
                $stmt->bindParam(':custom_field', $this->custom_field, PDO::PARAM_STR);
                $stmt->bindParam(':data_table', $this->data_table, PDO::PARAM_STR);

                $stmt->bindParam(':total_sub', $newtotalsub, PDO::PARAM_STR);
                $stmt->bindParam(':total', $newtotal, PDO::PARAM_STR);
                $stmt->bindParam(':term', $newterm, PDO::PARAM_STR);
                $stmt->bindParam(':signature', $this->signature, PDO::PARAM_STR);

                $stmt->bindParam(':username', $newusername, PDO::PARAM_STR);
                $stmt->bindParam(':invoiceid', $this->invoiceid, PDO::PARAM_STR);
                $stmt->bindParam(':statusid', $this->statusid, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $data = [
                        'status' => 'success',
                        'code' => 'RS103',
                        'message' => CustomHandlers::getreSlimMessage('RS103',$this->lang)
                    ];	
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 'RS203',
                        'message' => CustomHandlers::getreSlimMessage('RS203',$this->lang)
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
            return $data;
        }

        public function setDataStatus(){
            $newusername = strtolower($this->username);
            $newstatusid = Validation::integerOnly($this->statusid);
            try {
                $this->db->beginTransaction();
                
                $sql = "UPDATE invoice_data a SET a.StatusID=:statusid,a.Updated_at=current_timestamp,a.Updated_by=:username WHERE InvoiceID=:invoiceid;";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':invoiceid', $this->invoiceid, PDO::PARAM_STR);
                $stmt->bindParam(':username', $newusername, PDO::PARAM_STR);
                $stmt->bindParam(':statusid', $newstatusid, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $data = [
                        'status' => 'success',
                        'code' => 'RS103',
                        'message' => CustomHandlers::getreSlimMessage('RS103',$this->lang)
                    ];	
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 'RS203',
                        'message' => CustomHandlers::getreSlimMessage('RS203',$this->lang)
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
            return $data;
        }

        public function deleteData(){
            $newusername = strtolower($this->username);
            try {
                $this->db->beginTransaction();
                
                $sql = "DELETE FROM invoice_data WHERE InvoiceID=:invoiceid;";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':invoiceid', $this->invoiceid, PDO::PARAM_STR);

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
            return $data;
        }

        public function readData(){
            $sql = "SELECT 
                    a.InvoiceID,
                    a.From_name,a.From_name_company,a.From_address,a.From_phone,a.From_fax,a.From_email,a.From_website,
                    a.To_name,a.To_name_company,a.To_address,a.To_phone,a.To_fax,a.To_email,a.To_website,
                    a.Total_sub,a.Total,a.Term,date_add(a.Created_at, INTERVAL a.Term DAY) as Due_date,
                    a.Signature,a.StatusID,b.Status,a.Created_at,a.Created_by,a.Updated_at,a.Updated_by,a.Updated_sys,
                    a.Custom_id,a.Custom_field,a.Data_table 
				FROM invoice_data a
                INNER JOIN core_status b ON a.StatusID = b.StatusID
				WHERE a.InvoiceID = :invoiceid LIMIT 1;";
				
			$stmt = $this->db->prepare($sql);		
			$stmt->bindParam(':invoiceid', $this->invoiceid, PDO::PARAM_STR);
			if ($stmt->execute()) {	
	    	    if ($stmt->rowCount() > 0){
                    $results = $this->modifyJsonStringInArray($stmt->fetchAll(PDO::FETCH_ASSOC),['Custom_id','Custom_field','Data_table']);
					$data = [
			            'result' => $results, 
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
        }

        public function indexData() {
            $search = "%$this->search%";
			//count total row
			$sqlcountrow = "SELECT count(a.InvoiceID) AS TotalRow 
				FROM invoice_data a
				WHERE 
                    ".(!empty($this->firstdate) && !empty($this->lastdate)?'date(a.Created_at) BETWEEN :firstdate and :lastdate and ':'')."
                    (a.InvoiceID LIKE :search OR a.From_name LIKE :search OR a.From_name_company LIKE :search OR a.To_name LIKE :search OR a.To_name_company LIKE :search)
				ORDER BY a.Created_at DESC;";
			$stmt = $this->db->prepare($sqlcountrow);		
            $stmt->bindParam(':search', $search, PDO::PARAM_STR);
            if (!empty($this->firstdate) && !empty($this->lastdate)){
                $stmt->bindParam(':firstdate', $this->firstdate, PDO::PARAM_STR);
                $stmt->bindParam(':lastdate', $this->lastdate, PDO::PARAM_STR);
            }
				
			if ($stmt->execute()) {	
    			if ($stmt->rowCount() > 0){
					$single = $stmt->fetch();
						
					// Paginate won't work if page and items per page is negative.
					// So make sure that page and items per page is always return minimum zero number.
					$newpage = Validation::integerOnly($this->page);
					$newitemsperpage = Validation::integerOnly($this->itemsPerPage);
					$limits = (((($newpage-1)*$newitemsperpage) <= 0)?0:(($newpage-1)*$newitemsperpage));
					$offsets = (($newitemsperpage <= 0)?0:$newitemsperpage);
					// Query Data
					$sql = "SELECT 
                            a.InvoiceID,
                            a.From_name,a.From_name_company,a.From_address,a.From_phone,a.From_fax,a.From_email,a.From_website,
                            a.To_name,a.To_name_company,a.To_address,a.To_phone,a.To_fax,a.To_email,a.To_website,
                            a.Total_sub,a.Total,a.Term,date_add(a.Created_at, INTERVAL a.Term DAY) as Due_date,
                            a.Signature,a.StatusID,b.Status,a.Created_at,a.Created_by,a.Updated_at,a.Updated_by,a.Updated_sys,
                            a.Custom_id,a.Custom_field,a.Data_table 
                        FROM invoice_data a
                        INNER JOIN core_status b ON a.StatusID = b.StatusID
                        WHERE 
                            ".(!empty($this->firstdate) && !empty($this->lastdate)?'date(a.Created_at) BETWEEN :firstdate and :lastdate and ':'')."
                            (a.InvoiceID LIKE :search OR a.From_name LIKE :search OR a.From_name_company LIKE :search OR a.To_name LIKE :search OR a.To_name_company LIKE :search)
						ORDER BY a.Created_at DESC LIMIT :limpage , :offpage;";
					$stmt2 = $this->db->prepare($sql);
					$stmt2->bindParam(':search', $search, PDO::PARAM_STR);
					$stmt2->bindValue(':limpage', (INT) $limits, PDO::PARAM_INT);
                    $stmt2->bindValue(':offpage', (INT) $offsets, PDO::PARAM_INT);
                    if (!empty($this->firstdate) && !empty($this->lastdate)){
                        $stmt2->bindParam(':firstdate', $this->firstdate, PDO::PARAM_STR);
                        $stmt2->bindParam(':lastdate', $this->lastdate, PDO::PARAM_STR);
                    }
					
					if ($stmt2->execute()){
						$pagination = new \classes\Pagination();
						$pagination->totalRow = $single['TotalRow'];
						$pagination->page = $this->page;
						$pagination->itemsPerPage = $this->itemsPerPage;
						$pagination->fetchAllAssoc = $this->modifyJsonStringInArray($stmt2->fetchAll(PDO::FETCH_ASSOC),['Custom_id','Custom_field','Data_table']);
						$data = $pagination->toDataArray();
					} else {
						$data = [
        		    		'status' => 'error',
		    		    	'code' => 'RS202',
				    	    'message' => CustomHandlers::getreSlimMessage('RS202',$this->lang)
						];	
					}			
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

        public function indexDataWithKey() {
            $search = "%$this->search%";
            $listkeys = $this->searchKeyword('a.Custom_id',$this->custom_id,'and');
            $listdata = $this->paramKeyword('a.Custom_id',$this->custom_id);
            $listdata[':search'] = $search;
            if (!empty($this->firstdate) && !empty($this->lastdate)){
                $listdata[':firstdate'] = $this->firstdate;
                $listdata[':lastdate'] = $this->lastdate;
            }
			//count total row
			$sqlcountrow = "SELECT count(a.InvoiceID) AS TotalRow 
				FROM invoice_data a
				WHERE 
                    ".(!empty($this->firstdate) && !empty($this->lastdate)?'date(a.Created_at) BETWEEN :firstdate and :lastdate and ':'')."
                    (a.InvoiceID LIKE :search OR a.From_name LIKE :search OR a.From_name_company LIKE :search OR a.To_name LIKE :search OR a.To_name_company LIKE :search)
                    ".(!empty($this->custom_id)?' and '.$listkeys:'')."
				    ORDER BY a.Created_at DESC;";
			$stmt = $this->db->prepare($sqlcountrow);
				
			if ($stmt->execute($listdata)) {	
    			if ($stmt->rowCount() > 0){
					$single = $stmt->fetch();
						
					// Paginate won't work if page and items per page is negative.
					// So make sure that page and items per page is always return minimum zero number.
					$newpage = Validation::integerOnly($this->page);
					$newitemsperpage = Validation::integerOnly($this->itemsPerPage);
					$limits = (((($newpage-1)*$newitemsperpage) <= 0)?0:(($newpage-1)*$newitemsperpage));
                    $offsets = (($newitemsperpage <= 0)?0:$newitemsperpage);
					// Query Data
					$sql = "SELECT 
                            a.InvoiceID,
                            a.From_name,a.From_name_company,a.From_address,a.From_phone,a.From_fax,a.From_email,a.From_website,
                            a.To_name,a.To_name_company,a.To_address,a.To_phone,a.To_fax,a.To_email,a.To_website,
                            a.Total_sub,a.Total,a.Term,date_add(a.Created_at, INTERVAL a.Term DAY) as Due_date,
                            a.Signature,a.StatusID,b.Status,a.Created_at,a.Created_by,a.Updated_at,a.Updated_by,a.Updated_sys,
                            a.Custom_id,a.Custom_field,a.Data_table 
                        FROM invoice_data a
                        INNER JOIN core_status b ON a.StatusID = b.StatusID
                        WHERE 
                            ".(!empty($this->firstdate) && !empty($this->lastdate)?'date(a.Created_at) BETWEEN :firstdate and :lastdate and ':'')."
                            (a.InvoiceID LIKE :search OR a.From_name LIKE :search OR a.From_name_company LIKE :search OR a.To_name LIKE :search OR a.To_name_company LIKE :search)
                            ".(!empty($this->custom_id)?' and '.$listkeys:'')."
		    				ORDER BY a.Created_at DESC LIMIT ".$limits." , ".$offsets."";
			    		$stmt2 = $this->db->prepare($sql);
					
					if ($stmt2->execute($listdata)){
						$pagination = new \classes\Pagination();
						$pagination->totalRow = $single['TotalRow'];
						$pagination->page = $this->page;
						$pagination->itemsPerPage = $this->itemsPerPage;
						$pagination->fetchAllAssoc = $this->modifyJsonStringInArray($stmt2->fetchAll(PDO::FETCH_ASSOC),['Custom_id','Custom_field','Data_table']);
						$data = $pagination->toDataArray();
					} else {
						$data = [
        		    		'status' => 'error',
		    		    	'code' => 'RS202',
				    	    'message' => CustomHandlers::getreSlimMessage('RS202',$this->lang)
						];	
					}			
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

        //Below here is for use in router =========================

        public function create() {
            if (Auth::validToken($this->db,$this->token,$this->username)){
                $roles = Auth::getRoleID($this->db,$this->token);
                if ($roles != 5){
                    $data = $this->createData();
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

        public function update() {
            if (Auth::validToken($this->db,$this->token,$this->username)){
                $roles = Auth::getRoleID($this->db,$this->token);
                if ($roles != 5){
                    $data = $this->updateData();
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

        public function delete() {
            if (Auth::validToken($this->db,$this->token,$this->username)){
                $roles = Auth::getRoleID($this->db,$this->token);
                if ($roles == 1){
                    $data = $this->deleteData();
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

        public function setStatus() {
            if (Auth::validToken($this->db,$this->token,$this->username)){
                $roles = Auth::getRoleID($this->db,$this->token);
                if ($roles != 5){
                    $data = $this->setDataStatus();
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

        public function read() {
            if (Auth::validToken($this->db,$this->token,$this->username)){
				$data = $this->readData();
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
		
		public function readPublic() {
			return JSON::safeEncode($this->readData(),true);
        }

        public function index() {
            if (Auth::validToken($this->db,$this->token)){
				$data = $this->indexData();
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

        public function indexKey() {
            if (Auth::validToken($this->db,$this->token)){
				$data = $this->indexDataWithKey();
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