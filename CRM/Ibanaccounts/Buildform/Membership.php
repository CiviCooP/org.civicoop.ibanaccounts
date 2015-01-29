<?php

/*
 * Class to add functionality for selecting IBAN on a membership
 * 
 */

class CRM_Ibanaccounts_Buildform_Membership extends CRM_Ibanaccounts_Buildform_IbanAccounts {

  protected function getContactIdForIban($values) {
    $contactId = '';
    if (!empty($this->form->getVar('_contactID'))) {
      $contactId = $this->form->getVar('_contactID');
    }

    //check if contribution is recorded for someone else
    if (isset($values['contribution_contact_select_id']) && isset($values['contribution_contact_select_id'][1]) && !empty($values['contribution_contact_select_id'][1])) {
      $contactId = $values['contribution_contact_select_id'][1];
    } elseif (isset($values['contact_select_id']) && isset($values['contact_select_id'][1])) {
      $contactId = $values['contact_select_id'][1];
    }
    return $contactId;
  }
  
  public function validateForm(&$values, &$files, &$errors) {
    if ($this->form->getVar('_action') == CRM_Core_Action::DELETE) {
      return;
    }
    
    //only do validation when a membership payment is recorded.
    if (isset($values['record_contribution'])) {
      parent::validateForm($values, $files, $errors);
    }
  }

  public function postProcess() {
    if ($this->form->getVar('_action') == CRM_Core_Action::DELETE) {
      return;
    }

    //retrieve the values and the membershipIDs
    $membership_ids = $this->form->getVar('_membershipIDs');
    $mid = $membership_ids[0];
    $values = $this->form->controller->exportValues($this->form->getVar('_name'));

    //retrieve the contact ID for the IBAN
    $contactId = $this->getContactIdForIban($values);
    $iban_account_id = isset($values['iban_account']) ? $values['iban_account'] : false;
    
    CRM_Ibanaccounts_Ibanaccounts::saveIbanForMembership($mid, $contactId, $values['iban'], $values['bic'], $values['tnv'], $iban_account_id);
  }

  protected function getName() {
    return 'membership';
  }

  protected function getCurrentIbanAccount($contactId) {
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanMembershipCustomGroupValue('table_name');
    $iban_field = $config->getIbanMembershipCustomFieldValue('column_name');

    $membership_ids = $this->form->getVar('_membershipIDs');
    $mid = isset($membership_ids[0]) ? $membership_ids[0] : false;
    if ($mid) {
      //set default value
      $sql = "SELECT * FROM `" . $table . "` WHERE `entity_id` = %1";
      $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($mid, 'Integer')));
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

}
