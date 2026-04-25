-- Company / Business setting (singleton row, id = 1) used as letterhead on PO PDFs.
CREATE TABLE IF NOT EXISTS `company_setting` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `business_name` VARCHAR(255) NOT NULL DEFAULT '',
    `business_register_no` VARCHAR(100) NOT NULL DEFAULT '',
    `address_line1` VARCHAR(255) NOT NULL DEFAULT '',
    `address_line2` VARCHAR(255) NOT NULL DEFAULT '',
    `address_line3` VARCHAR(255) NOT NULL DEFAULT '',
    `tel_no` VARCHAR(50) NOT NULL DEFAULT '',
    `email` VARCHAR(150) NOT NULL DEFAULT '',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `company_setting`
    (`id`, `business_name`, `business_register_no`, `address_line1`, `address_line2`, `address_line3`, `tel_no`, `email`)
VALUES
    (1,
     'PARKWAY DEPARTMENTAL STORE SDN BHD',
     '1016088-D',
     'LOT 338, JALAN PENGHULU DURIN,',
     '94000 BAU, SARAWAK.',
     '',
     '082-764677',
     'parkwaydepartmentalstore@gmail.com')
ON DUPLICATE KEY UPDATE `id` = `id`;
