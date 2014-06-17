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
    $config = CRM_Ibanaccounts_Config::singleton();
    $table = $config->getIbanMembershipCustomGroupValue('table_name');
    $iban_field = $config->getIbanMembershipCustomFieldValue('column_name');
    $bic_field = $config->getBicMembershipCustomFieldValue('column_name');
    
    //retrieve the values and the membershipIDs
    $membership_ids = $this->form->getVar('_membershipIDs');
    $mid = $membership_ids[0]; 
    $values = $this->form->controller->exportValues($this->form->getVar('_name'));
    
    //retrieve the contact ID for the IBAN
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
    
    //remove the current bank account
    CRM_Core_DAO::executeQuery("DELETE FROM `".$table."` WHERE `entity_id` = %1", array(1 => array($mid, 'Integer')));
    
    $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactId);
    $iban_account_id = $values['iban_account'];
      
    if ($iban_account_id == -1 || !isset($accounts[$iban_account_id])) {
      $iban_account_id = CRM_Ibanaccounts_Ibanaccounts::saveIBANForContact($values['iban'], $values['bic'], $contactId);
      $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactId);
    }
      
    if (isset($accounts[$iban_account_id])) {
      $iban = $accounts[$iban_account_id]['iban'];
      $bic = $accounts[$iban_account_id]['bic'];
        
      $sql = "INSERT INTO `".$table."` (`entity_id`, `".$iban_field."`, `".$bic_field."`) VALUES (%1, %2, %3);";
      $dao = CRM_Core_DAO::executeQuery($sql, array(
        '1' => array($mid, 'Integer'),
        '2' => array($iban, 'String'),
        '3' => array($bic, 'String'),
      ));
    }
  }

  /**
   * Add the UI code to the form
   */
  public function parse() {
    $options[] = ts(' -- Select IBAN Account --');
    $options[-1] = ts('New account');
    $options[-2] = ts('No IBAN details provided');
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
      $options[-2] = ts('No IBAN details provided');
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

