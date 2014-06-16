<?php

/* 
 * Class to add functionality for selecting IBAN on a membership
 * 
 */

class CRM_Ibanaccounts_Buildform_Membership {
  
  protected $form;

  public function __construct(&$form) {
    $this->form = $form;
  }
  
  public function postProcess() {
    $membership_ids = $this->form->getVar('_membershipIDs');
    $values = $this->form->controller->exportValues($this->form->getVar('_name'));
    
    $contactId = '';
    if (!empty($this->form->getVar('_contactID'))) {
      $contactId = $this->form->getVar('_contactID');
    } 
    
    if (isset($values['contribution_contact_select_id']) && isset($values['contribution_contact_select_id'][1])) {
      $contactId = $values['contribution_contact_select_id'][1];
    } elseif (isset($values['contact_select_id']) && isset($values['contact_select_id'][1])) {
      $contactId = $values['contact_select_id'][1];
    }
    
    $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactId);
    foreach($membership_ids as $mid) {
      if (!isset($accounts[$values['iban_account']])) {
        CRM_Ibanaccounts_Ibanaccounts::saveIBANForContact($values['iban'], $values['bic'], $contactId);
        $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactId);
      }
      
      if (isset($accounts[$values['iban_account']])) {
        $iban = $accounts[$values['iban_account']]['iban'];
        $bic = $accounts[$values['iban_account']]['bic'];
        
        $sql = "INSERT INTO `civicrm_membership_iban_account` (`membership_id`, `iban`, `bic`) VALUES (%1, %2, %3);";
        $dao = CRM_Core_DAO::executeQuery($sql, array(
          '1' => array($mid, 'Integer'),
          '2' => array($iban, 'String'),
          '3' => array($bic, 'String'),
        ));
      }
    }
  }

  /**
   * Add the UI code to the form
   */
  public function parse() {
    $options[] = ts(' -- Select IBAN Account --');
    $options[-1] = ts('New account');
    $accounts = array();
    
    $contactId = '';
    $values = $this->form->exportValues();
    if (!empty($this->form->getVar('_contactID'))) {
      $contactId = $this->form->getVar('_contactID');
    } 
    
    if (isset($values['contribution_contact_select_id']) && isset($values['contribution_contact_select_id'][1])) {
      $contactId = $values['contribution_contact_select_id'][1];
    } elseif (isset($values['contact_select_id']) && isset($values['contact_select_id'][1])) {
      $contactId = $values['contact_select_id'][1];
    }
    
    if (strlen($contactId)) {
      //the contact id is already set on this form so set the information static
      $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactId);
      
      $options = array();
      $options[] = ts(' -- Select IBAN Account --');
      foreach($accounts as $id => $account) {
        $options[$id] = $account['iban'];
      }
      $options[-1] = ts('New account');
    }
      
    $snippet['template'] = 'CRM/Ibanaccounts/Buildform/Membership.tpl';
    $snippet['accounts'] = $accounts;
    $snippet['contact_id'] = $contactId;
      
    $this->form->add('select', 'iban_account', ts('IBAN Account'), $options);
    $this->form->add('text', 'iban', ts('IBAN'));
    $this->form->add('text', 'bic', ts('BIC'));
      
    CRM_Core_Region::instance('page-body')->add($snippet);
    CRM_Core_Resources::singleton()->addScriptFile('org.civicoop.ibanaccounts', 'membership.js');
  }
  
}

