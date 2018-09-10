SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for invoice_data
-- ----------------------------
DROP TABLE IF EXISTS `invoice_data`;
CREATE TABLE `invoice_data` (
  `InvoiceID` varchar(20) NOT NULL,
  `From_name` varchar(50) NOT NULL,
  `From_name_company` varchar(50) DEFAULT NULL,
  `From_address` varchar(255) NOT NULL,
  `From_phone` varchar(15) NOT NULL,
  `From_fax` varchar(15) DEFAULT NULL,
  `From_email` varchar(50) DEFAULT NULL,
  `From_website` varchar(50) DEFAULT NULL,
  `To_name` varchar(50) NOT NULL,
  `To_name_company` varchar(50) DEFAULT NULL,
  `To_address` varchar(255) NOT NULL,
  `To_phone` varchar(15) NOT NULL,
  `To_fax` varchar(15) DEFAULT NULL,
  `To_email` varchar(50) DEFAULT NULL,
  `To_website` varchar(50) DEFAULT NULL,
  `Custom_id` varchar(1000) DEFAULT NULL,
  `Custom_field` text,
  `Data_table` text NOT NULL,
  `Total_sub` varchar(10) NOT NULL,
  `Total` decimal(10,2) NOT NULL,
  `Term` int(11) NOT NULL,
  `Signature` varchar(50) DEFAULT NULL,
  `StatusID` int(11) NOT NULL,
  `Created_at` datetime NOT NULL,
  `Created_by` varchar(50) NOT NULL,
  `Updated_at` datetime DEFAULT NULL,
  `Updated_by` varchar(50) DEFAULT NULL,
  `Updated_sys` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`InvoiceID`),
  KEY `From` (`From_name`,`From_name_company`),
  KEY `To` (`To_name`,`To_name_company`),
  KEY `StatusID` (`StatusID`),
  KEY `Created_at` (`Created_at`),
  KEY `Created_by` (`Created_by`),
  KEY `Custom_id` (`Custom_id`(767))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET FOREIGN_KEY_CHECKS=1;
