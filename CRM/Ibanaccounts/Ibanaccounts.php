<?php

require_once(__DIR__.'./../../php-iban/oophp-iban.php');

/* 
 * Retrieve and save IBAN accounts
 * 
 */

class CRM_Ibanaccounts_Ibanaccounts {
  
  /*
   * Returns an array with all IBAN numbers belonging to a contact
   * 
   * Return array consist of the following details per IBAN
   * id => id of the IBAN
   * contact_id => contact ID
   * iban => the IBAN number
   * bic => the BIC number
   */
  public static function IBANForContact($contactId) {        
    if (empty($contactId)) {
      return array();
    }
    
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
      $iban = new IBAN($dao->$iban_field);
      
      $account['id'] = $dao->id;
      $account['contact_id'] = $dao->entity_id;
      $account['iban'] = $iban->MachineFormat();
      $account['iban_human'] = $iban->HumanFormat();
      $account['bic'] = $dao->$bic_field;
      $return[$dao->id] = $account;
    }
    return $return;
  }
  
  /*
   * Returns an array with all IBAN number
   * 
   * Return array consist of the following details per IBAN
   * id => id of the IBAN
   * contact_id => contact ID
   * iban => the IBAN number
   * bic => the BIC number
   */
  public static function findIBANByIban($iban) {
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanCustomGroupValue('table_name');
    $iban_field = $config->getIbanCustomFieldValue('column_name');
    $bic_field = $config->getBicCustomFieldValue('column_name');
    $sql = "SELECT * FROM `".$table."`  WHERE `".$iban_field."`  = %1";
    $dao = CRM_Core_DAO::executeQuery($sql, array(
      '1' => array($iban, 'String'),
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
  
  /**
   * Saves an IBAN Number for a contact
   * 
   * @param type $iban
   * @param type $bic
   * @param type $contactId
   * @return int the id of the IBAN
   */
  public static function saveIBANForContact($iban, $bic, $contactId) {
    if (empty($iban)) {
      return;
    }
    
    $iban_class = new IBAN($iban);
    $iban_system = $iban_class->MachineFormat();
    
    $id = self::getIdByIBANAndContactId($iban, $contactId);
    if ($id) {
      //iban number already exist
      return $id;
    }
    //only save when IBAN number doesn't exist yet
    
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanCustomGroupValue('table_name');
    $iban_field = $config->getIbanCustomFieldValue('column_name');
    $bic_field = $config->getBicCustomFieldValue('column_name');
    $sql = "INSERT INTO `".$table."` (`entity_id`, `".$iban_field."`, `".$bic_field."`) VALUES (%1, %2, %3);" ;
    $dao = CRM_Core_DAO::executeQuery($sql, array(
      '1' => array($contactId, 'Integer'),
      '2' => array($iban_system, 'String'),
      '3' => array($bic, 'String'),
    ));
    
    return self::getIdByIBANAndContactId($iban, $contactId);
  }
  
  /**
   * Get the ID of an IBAN Number 
   * 
   * @param type $iban
   * @param optional $contactId
   * @return false|int
   */
  public static function getIdByIBANAndContactId($iban, $contactId = false) {
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanCustomGroupValue('table_name');
    $iban_field = $config->getIbanCustomFieldValue('column_name');
    
    $sql = "SELECT * FROM `".$table."` WHERE `".$iban_field."` = %1";
    $params[1] = array($iban, 'String');
    if ($contactId) {
      $sql .= " AND `entity_id` = %2";
      $params[2] = array($contactId, 'Integer');
    }
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    if ($dao->fetch()) {
      return $dao->id;
    }
    return false;
  }
  
  /**
   * Returns an array with usages for the IBAN account.
   * 
   * The array contains description for uses of the IBAN account e.g.:
   * array(
   *  'IBAN account in use by membership',
   * )
   * 
   * If the iban account is not in use by any entity then the array should be empty and the IBAN could be deleted safely
   * 
   * @param type $iban
   * @return array
   */
  public static function getIBANUsages($iban) {
    
    $iban_class = new IBAN($iban);
    $iban_system = $iban_class->MachineFormat();
    
    $hooks = CRM_Utils_Hook::singleton();
    $usages = $hooks->invoke(1, $iban_system, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, 'civicrm_iban_usages');
    if (!is_array($usages)) {
      $usages = array();
    }
    $messages = array();
    foreach($usages as $entities) {
      foreach($entities as $entity) {
        $messages[] = $entity;
      }
    }
    return $messages;
  }
  
  public static function removeIban($iban, $contactId) {
    $iban_class = new IBAN($iban);
    $iban_system = $iban_class->MachineFormat();
    
    $hooks = CRM_Utils_Hook::singleton();
    $hooks->invoke(2, $iban_system, $contactId, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, 'civicrm_remove_iban');
    
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanCustomGroupValue('table_name');
    $iban_field = $config->getIbanCustomFieldValue('column_name');
    $sql = "DELETE FROM `".$table."` WHERE `entity_id` = %1 AND `".$iban_field."` = %2;" ;
    $dao = CRM_Core_DAO::executeQuery($sql, array(
      '1' => array($contactId, 'Integer'),
      '2' => array($iban_system, 'String'),
    ));
  }
  
}