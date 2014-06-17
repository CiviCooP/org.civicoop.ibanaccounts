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
    if (isset($values['contribution_contact_select_id']) && isset($values['contribution_contact_select_id'][1])) {
      $contactId = $values['contribution_contact_select_id'][1];
    } elseif (isset($values['contact_select_id']) && isset($values['contact_select_id'][1])) {
      $contactId = $values['contact_select_id'][1];
    }
    return $contactId;
  }

  public function postProcess() {
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanMembershipCustomGroupValue('table_name');
    $iban_field = $config->getIbanMembershipCustomFieldValue('column_name');
    $bic_field = $config->getBicMembershipCustomFieldValue('column_name');

    //retrieve the values and the membershipIDs
    $membership_ids = $this->form->getVar('_membershipIDs');
    $mid = $membership_ids[0];
    $values = $this->form->controller->exportValues($this->form->getVar('_name'));

    //retrieve the contact ID for the IBAN
    $contactId = $this->getContactIdForIban($values);

    //remove the current bank account
    CRM_Core_DAO::executeQuery("DELETE FROM `" . $table . "` WHERE `entity_id` = %1", array(1 => array($mid, 'Integer')));

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
            '1' => array($mid, 'Integer'),
            '2' => array($iban, 'String'),
            '3' => array($bic, 'String'),
      ));
    }
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
