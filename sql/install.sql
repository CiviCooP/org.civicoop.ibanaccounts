CREATE TABLE IF NOT EXISTS `civicrm_membership_iban_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `membership_id` int(11) NOT NULL,
  `iban` varchar(255) NOT NULL,
  `bic` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;