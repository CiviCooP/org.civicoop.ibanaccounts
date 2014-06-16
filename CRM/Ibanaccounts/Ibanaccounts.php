<?php

/* 
 * Retrieve and save IBAN accounts
 * 
 */

class CRM_Ibanaccounts_Ibanaccounts {
  
  public static function IBANForContact($contactId) {
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanCustomGroupValue('table_name');
    $iban_field = $config->getIbanCustomFieldValue('column_name');
    $bic_field = $config->getBicCustomFieldValue('column_name');
    $sql = "SELECT * FROM `".$table."`  WHERE `entity_id`  = %1";
    $dao = CRM_Core_DAO::executeQuery($sql, array(
      '1' => array($contactId, 'Integer'),
    ));
    
    $return = array();
    while($dao->fetch()) {
      $account['id'] = $dao->id;
      $account['contact_id'] = $dao->entity_id;
      $account['iban'] = $dao->$iban_field;
      $account['bic'] = $dao->$bic_field;
      $return[$dao->id] = $account;
    }
    return $return;
  }
  
  public static function saveIBANForContact($iban, $bic, $contactId) {
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanCustomGroupValue('table_name');
    $iban_field = $config->getIbanCustomFieldValue('column_name');
    $bic_field = $config->getBicCustomFieldValue('column_name');
    $sql = "INSERT INTO `".$table."` (`entity_id`, `".$iban_field."`, `".$bic_field."`) VALUES (%1, %2, %3);" ;
    $dao = CRM_Core_DAO::executeQuery($sql, array(
      '1' => array($contactId, 'Integer'),
      '2' => array($iban, 'String'),
      '3' => array($bic, 'String'),
    ));
  }
  
}
