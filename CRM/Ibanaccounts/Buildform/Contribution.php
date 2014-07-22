<?php

class CRM_Ibanaccounts_Buildform_Contribution extends CRM_Ibanaccounts_Buildform_IbanAccounts {
  
  protected function getName() {
    return 'contribution';
  }

  protected function getCurrentIbanAccount($contactId) {
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanContributionCustomGroupValue('table_name');
    $iban_field = $config->getIbanContributionCustomFieldValue('column_name');
    
    $contribution_id = $this->form->getVar('_id');
    if ($contribution_id) {
      $sql = "SELECT * FROM `" . $table . "` WHERE `entity_id` = %1";
      $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($contribution_id, 'Integer')));
      if ($dao->fetch()) {
        $iban = $dao->$iban_field;
        $account_id = CRM_Ibanaccounts_Ibanaccounts::getIdByIBANAndContactId($iban, $contactId);
        if ($account_id) {
          return $account_id;
        }
      }
    }
    return false;
  }
  
  protected function getContactIdForIban($values) {
    $contactId = '';
    if ($this->form && !empty($this->form->getVar('_contactID'))) {
      $contactId = $this->form->getVar('_contactID');
    }
    return $contactId;
  }
  
  public function postProcess() {
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanContributionCustomGroupValue('table_name');
    $iban_field = $config->getIbanContributionCustomFieldValue('column_name');
    $bic_field = $config->getBicContributionCustomFieldValue('column_name');
    
    //retrieve the values and the membershipIDs
    $values = $this->form->controller->exportValues($this->form->getVar('_name'));

    //retrieve the contact ID for the IBAN
    $contactId = $this->getContactIdForIban($values);
    
    $contribution_id = $this->getContributionId($values, $contactId);
    
    if (!$contribution_id) {
      return;
    }
    
    //remove the current bank account
    CRM_Core_DAO::executeQuery("DELETE FROM `" . $table . "` WHERE `entity_id` = %1", array(1 => array($contribution_id, 'Integer')));

    $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactId);
    $iban_account_id = isset($values['iban_account']) ? $values['iban_account'] : false;

    if ($iban_account_id == -1 || !isset($accounts[$iban_account_id])) {
      $iban_account_id = CRM_Ibanaccounts_Ibanaccounts::saveIBANForContact($values['iban'], $values['bic'], $contactId);
      $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactId);
    }

    if (isset($accounts[$iban_account_id])) {
      $iban = $accounts[$iban_account_id]['iban'];
      $bic = $accounts[$iban_account_id]['bic'];

      $sql = "INSERT INTO `" . $table . "` (`entity_id`, `" . $iban_field . "`, `" . $bic_field . "`) VALUES (%1, %2, %3);";
      $dao = CRM_Core_DAO::executeQuery($sql, array(
            '1' => array($contribution_id, 'Integer'),
            '2' => array($iban, 'String'),
            '3' => array($bic, 'String'),
      ));
    }
  }
  
  protected function getContributionId($values, $contactId) {
    if (!empty($this->form->getVar('_id'))) {
      return $this->form->getVar('_id');
    }
    //do a dirty fix to retrieve the last saved contribution id 
    //becasue the contribution form does not set the id upon
    //creation of a new contribution record
    $sql = "SELECT MAX(`id`) AS `id` FROM `civicrm_contribution` WHERE `contact_id` = %1";
    $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($contactId, 'Integer')));
    if ($dao->fetch()) {
      return $dao->id;
    }
    return false;
  }
  
}

