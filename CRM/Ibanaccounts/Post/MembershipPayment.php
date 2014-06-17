<?php

class CRM_Ibanaccounts_Post_MembershipPayment {
  
  public function __construct() {
    
  }
  
  public function post($op, $object) {
    if ($op == 'create' || $op == 'edit') {
      //remove iban account details from contribution record
      $contribution_id = $object->contribution_id;
      $iban = $this->getIbanFromMembership($object->membership_id);      
      $this->clearIban($contribution_id);
      $this->saveIban($contribution_id, $iban['iban'], $iban['bic']);
    }
  }
  
  public function clearIban($contribution_id) {
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanContributionCustomGroupValue('table_name');
    
    CRM_Core_DAO::executeQuery("DELETE FROM `" . $table . "` WHERE `entity_id` = %1", array(1 => array($contribution_id, 'Integer')));
  }
  
  public function saveIban($contribution_id, $iban, $bic) {
    if (empty($iban) && empty($bic)) {
      return;
    }
    
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanContributionCustomGroupValue('table_name');
    $iban_field = $config->getIbanContributionCustomFieldValue('column_name');
    $bic_field = $config->getBicContributionCustomFieldValue('column_name');
    
    $sql = "INSERT INTO `" . $table . "` (`entity_id`, `" . $iban_field . "`, `" . $bic_field . "`) VALUES (%1, %2, %3);";
    $dao = CRM_Core_DAO::executeQuery($sql, array(
          '1' => array($contribution_id, 'Integer'),
          '2' => array($iban, 'String'),
          '3' => array($bic, 'String'),
    ));
  }
  
  protected function getIbanFromMembership($membership_id) {
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanMembershipCustomGroupValue('table_name');
    $iban_field = $config->getIbanMembershipCustomFieldValue('column_name');
    $bic_field = $config->getBicMembershipCustomFieldValue('column_name');

    $return['iban'] = '';
    $return['bic'] = '';
    if ($membership_id) {
      //set default value
      $sql = "SELECT * FROM `" . $table . "` WHERE `entity_id` = %1";
      $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($membership_id, 'Integer')));
      if ($dao->fetch()) {
        $return['iban'] = $dao->$iban_field;
        $return['bic'] = $dao->$bic_field;
      }
    }
    return $return;
  }
  
}

