<?php

class CRM_Ibanaccounts_Buildform_MembershipRenewal extends CRM_Ibanaccounts_Buildform_IbanAccounts {
  
  protected function getName() {
    return 'membershiprenewal';
  }
  
  protected function getContactIdForIban($values) {
    $contactId = '';
    if (!empty($this->form->getVar('_contactID'))) {
      $contactId = $this->form->getVar('_contactID');
    }

    //check if contribution is recorded for someone else
    if (isset($values['contribution_contact']) && isset($values['contribution_contact'][1]) && !empty($values['contribution_contact'][1])) {
      $contactId = $values['contribution_contact_select_id'][1];
    } elseif (isset($values['contact_select_id']) && isset($values['contact_select_id'][1])) {
      $contactId = $values['contact_select_id'][1];
    }
    return $contactId;
  }
  
  protected function getCurrentIbanAccount($contactId) {
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanMembershipCustomGroupValue('table_name');
    $iban_field = $config->getIbanMembershipCustomFieldValue('column_name');

    $mid = $this->form->getVar('_id');
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
  
  public function postProcess() {
    if ($this->form->getVar('_action') == CRM_Core_Action::DELETE) {
      return;
    }
    
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanMembershipCustomGroupValue('table_name');
    $iban_field = $config->getIbanMembershipCustomFieldValue('column_name');
    $bic_field = $config->getBicMembershipCustomFieldValue('column_name');
    $tnv_field = $config->getTnvMembershipCustomFieldValue('column_name');

    //retrieve the values and the membershipIDs
    $mid = $this->form->getVar('_id');
    $values = $this->form->controller->exportValues($this->form->getVar('_name'));

    //retrieve the contact ID for the IBAN
    $contactId = $this->getContactIdForIban($values);

    //remove the current bank account
    CRM_Core_DAO::executeQuery("DELETE FROM `" . $table . "` WHERE `entity_id` = %1", array(1 => array($mid, 'Integer')));

    $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactId);
    $iban_account_id = isset($values['iban_account']) ? $values['iban_account'] : false;

    if ($iban_account_id == -1 || !isset($accounts[$iban_account_id])) {
      $iban_account_id = CRM_Ibanaccounts_Ibanaccounts::saveIBANForContact($values['iban'], $values['bic'], $values['tnv'], $contactId);
      $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactId);
    }

    if (isset($accounts[$iban_account_id])) {
      $iban = $accounts[$iban_account_id]['iban'];
      $bic = $accounts[$iban_account_id]['bic'];
      $tnv = $accounts[$iban_account_id]['tnv'];

      $sql = "INSERT INTO `" . $table . "` (`entity_id`, `" . $iban_field . "`, `" . $bic_field . "`, `".$tnv_field."`) VALUES (%1, %2, %3, %4);";
      $dao = CRM_Core_DAO::executeQuery($sql, array(
            '1' => array($mid, 'Integer'),
            '2' => array($iban, 'String'),
            '3' => array($bic, 'String'),
            '4' => array($tnv, 'String'),
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
          $postMembershipPayment->saveIban($membership_payment->contribution_id, $iban, $bic, $tnv);
        }
      }
    }
  }
  
}
