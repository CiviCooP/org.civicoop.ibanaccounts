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

  /**
   * Add the UI code to the form
   */
  public function parse() {
    $options[] = ts(' -- Select IBAN Account --');
    $options[-1] = ts('New account');
    $accounts = array();
    
    $contactId = '';
    if (!empty($this->form->getVar('_contactID'))) {
      $contactId = $this->form->getVar('_contactID');
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

