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
    $tnv_field = $config->getTnvCustomFieldValue('column_name');
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
      $account['tnv'] = $dao->$tnv_field;
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
    $tnv_field = $config->getTnvCustomFieldValue('column_name');
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
      $account['tnv'] = $dao->$tnv_field;
      $return[$dao->id] = $account;
    }
    return $return;
  }
  
  /**
   * Saves an IBAN Number for a contact
   * 
   * @param string $iban
   * @param string $bic
   * @param string $tnv
   * @param int $contactId
   * @return int the id of the IBAN
   */
  public static function saveIBANForContact($iban, $bic, $tnv, $contactId) {
    if (empty($iban)) {
      return;
    }
    
    $iban_class = new IBAN($iban);
    $iban_system = $iban_class->MachineFormat();

    //only save when IBAN number doesn't exist yet

    $config = CRM_Ibanaccounts_Config::singleton();
    $iban_field = 'custom_'.$config->getIbanCustomFieldValue('id');
    $bic_field = 'custom_'.$config->getBicCustomFieldValue('id');
    $tnv_field = 'custom_'.$config->getTnvCustomFieldValue('id');

    $id = self::getIdByIBANAndContactId($iban_system, $contactId);
    if ($id) {
      //iban number already exist
      $iban_field = 'custom_'.$config->getIbanCustomFieldValue('id').'_'.$id;
      $bic_field = 'custom_'.$config->getBicCustomFieldValue('id').'_'.$id;
      $tnv_field = 'custom_'.$config->getTnvCustomFieldValue('id').'_'.$id;
    }

    $params = array();
    $params['entityID'] = $contactId;
    $params[$iban_field] = $iban_system;
    $params[$bic_field] = $bic;
    $params[$tnv_field] = $tnv;
    if (empty($params[$bic_field])) {
      $params[$bic_field] = CRM_Ibanaccounts_Utils_IbanToBic::getBic($params[$iban_field]);
    }

    CRM_Core_BAO_CustomValueTable::setValues($params);
    //civicrm_api3('CustomValue', 'Create', $params);

    return self::getIdByIBANAndContactId($iban, $contactId);
  }

  /**
   * Get the ID of an IBAN Number
   *
   * @param type $iban
   * @param int|bool $contactId
   * @return false|int
   */
  public static function getIdByIBANAndContactId($iban, $contactId = false) {
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanCustomGroupValue('table_name');
    $iban_field = $config->getIbanCustomFieldValue('column_name');
    
    $iban_class = new IBAN($iban);
    $iban_system = $iban_class->MachineFormat();
    
    $sql = "SELECT * FROM `".$table."` WHERE `".$iban_field."` = %1";
    $params[1] = array($iban_system, 'String');
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
  public static function getIBANUsages($iban, $contactId = false) {
    
    $iban_class = new IBAN($iban);
    $iban_system = $iban_class->MachineFormat();
    
    $hooks = CRM_Ibanaccounts_Utils_HookInvoker::singleton();
    $usages = $hooks->hook_civicrm_iban_usages($iban_system, $contactId);
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
    
    $hooks = CRM_Ibanaccounts_Utils_HookInvoker::singleton();
    $hooks->hook_civicrm_remove_iban($iban_system, $contactId);
    
    $config = CRM_Ibanaccounts_Config::singleton();
    $iban_field_id = $config->getIbanCustomFieldValue('id');
    
    //fetch id
    $contactParams = array();
    $contactParams['id'] = $contactId;
    $contactParams['custom_'.$iban_field_id] = $iban;
    $contactParams['return.custom_'.$iban_field_id] = 1;
    $contact = civicrm_api3('Contact', 'getsingle', $contactParams);
    //extract the custom value table id
    $objectId = $contact[$config->getIbanCustomGroupValue('table_name').'_id'];
    
    CRM_Core_BAO_CustomValue::deleteCustomValue($objectId, $config->getIbanCustomGroupValue('id'));
  }
  
  public static function saveIbanForMembership($mid, $contactId, $iban, $bic, $tnv, $iban_account_id = false) {
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanMembershipCustomGroupValue('table_name');
    $iban_field = $config->getIbanMembershipCustomFieldValue('column_name');
    $bic_field = $config->getBicMembershipCustomFieldValue('column_name');
    $tnv_field = $config->getTnvMembershipCustomFieldValue('column_name');
    
    //remove the current bank account
    CRM_Core_DAO::executeQuery("DELETE FROM `" . $table . "` WHERE `entity_id` = %1", array(1 => array($mid, 'Integer')));

    $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactId);

    if ($iban_account_id == -1 || !isset($accounts[$iban_account_id])) {
      $iban_account_id = CRM_Ibanaccounts_Ibanaccounts::saveIBANForContact($iban, $bic, $tnv, $contactId);
      $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactId);
    }

    if (isset($accounts[$iban_account_id])) {
      $_iban = $accounts[$iban_account_id]['iban'];
      $_bic = $accounts[$iban_account_id]['bic'];
      $_tnv = $accounts[$iban_account_id]['tnv'];

      $sql = "INSERT INTO `" . $table . "` (`entity_id`, `" . $iban_field . "`, `" . $bic_field . "`, `".$tnv_field."`) VALUES (%1, %2, %3, %4);";
      CRM_Core_DAO::executeQuery($sql, array(
            '1' => array($mid, 'Integer'),
            '2' => array($_iban, 'String'),
            '3' => array($_bic, 'String'),
            '4' => array($_tnv, 'String'),
      ));
      
      //also record iban information on the contribution record
      $membership_payment = new CRM_Member_BAO_MembershipPayment();
      $membership_payment->membership_id = $mid;
      $membership_payment->find(false);
      while($membership_payment->fetch()) {
        $postMembershipPayment = new CRM_Ibanaccounts_Post_MembershipPayment();
        $isFutureContribution = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM `civicrm_contribution` WHERE `id` = %1", array(1=>array($membership_payment->contribution_id, 'Integer')));
        if ($isFutureContribution) {
          $postMembershipPayment->clearIban($membership_payment->contribution_id);
          $postMembershipPayment->saveIban($membership_payment->contribution_id, $_iban, $_bic, $_tnv);
        }
      }
    }
  }
  
}
