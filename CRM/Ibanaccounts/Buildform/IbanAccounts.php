<?php

abstract class CRM_Ibanaccounts_Buildform_IbanAccounts {

  abstract public function postProcess();
  
  abstract protected function getContactIdForIban($values);

  abstract protected function getName();
  
  abstract protected function getCurrentIbanAccount($contactId);

  protected $form;

  public function __construct(&$form) {
    $this->form = $form;
  }
  
  public function validateForm(&$values, &$files, &$errors) {
    if ($this->form->getVar('_action') == CRM_Core_Action::DELETE) {
      return;
    }
    
    //retrieve the contact ID for the IBAN
    $contactId = $this->getContactIdForIban($values);

    $this->validateIbanBicFields($values, 'iban_account', 'iban', 'bic', $errors, $contactId);
  }

  /**
   * Returns an empty string or an error message
   * 
   * @param type $iban
   */
  protected function validateIbanField($iban, $contactId) {
    require_once('php-iban/oophp-iban.php');
    $ibanValidator = new IBAN();

    //a new iban account is provided
    if (empty($iban)) {
      return ts('IBAN is required');
    } elseif (!$ibanValidator->Verify($iban)) {
      return ts("'" . $iban . "' is not a valid IBAN");
    }

    //check if IBAN belongs to another contact
    $accounts = CRM_Ibanaccounts_Ibanaccounts::findIBANByIban($iban);
    $foundAtOtherContact = false;
    $otherContactId = false;
    $foundAtSelf = false;
    foreach ($accounts as $account) {
      if ($account['contact_id'] == $contactId) {
        $foundAtSelf = true;
      } else {
        $foundAtOtherContact = true;
        $otherContactId = $account['contact_id'];
      }
    }

    if ($foundAtOtherContact && !$foundAtSelf && $otherContactId) {
      $displayName = CRM_Contact_BAO_Contact::displayName($otherContactId);
      $url = CRM_Utils_System::url('civicrm/contact/view', array('cid' => $otherContactId));
      return ts('IBAN belongs to <a href="%1">%2</a>', array(
        1 => $url,
        2 => $displayName
      ));
    } elseif ($foundAtOtherContact && !$foundAtSelf) {
      return ts('IBAN belongs to another contact');
    }
    return "";
  }

  protected function validateIbanBicFields($values, $account_field, $iban_field, $bic_field, &$errors, $contactId) {
    $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactId);
    $iban_account_id = isset($values[$account_field]) ? $values[$account_field] : false;

    if ($iban_account_id === -1 || !isset($accounts[$iban_account_id])) {
      if (empty($values[$bic_field])) {
        $errors[$bic_field] = ts('BIC is required');
      }

      $iban_error = $this->validateIbanField($values[$iban_field], $contactId);
      if (!empty($iban_error)) {
        $errors[$iban_field] = $iban_error;
      }
    }
  }

  protected function generateOptions($contactId) {
    $options = array();
    $options[] = ts(' -- Select IBAN Account --');
    if (strlen($contactId)) {
      //the contact id is already set on this form so set the information static
      $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactId);

      foreach ($accounts as $id => $account) {
        $options[$id] = $account['iban'];
      }
    }
    $options[-1] = ts('New account');
    $options[-2] = ts('No IBAN details provided');
    return $options;
  }
  
  /**
   * Add the UI code to the form
   */
  public function parse() {
    $values = $this->form->exportValues();
    $contactId = $this->getContactIdForIban($values);

    $options = $this->generateOptions($contactId);
    
    

    $snippet['template'] = 'CRM/Ibanaccounts/Buildform/'.ucfirst($this->getName()).'.tpl';
    $snippet['contact_id'] = $contactId;

    $this->form->add('select', 'iban_account', ts('IBAN Account'), $options);
    $this->form->add('text', 'iban', ts('IBAN'));
    $this->form->add('text', 'bic', ts('BIC'));

    $currentIbanAccount = $this->getCurrentIbanAccount($contactId);

    if ($currentIbanAccount) {
      $defaults['iban_account'] = $currentIbanAccount;
      $this->form->setDefaults($defaults);
    }

    CRM_Core_Region::instance('page-body')->add($snippet);
    CRM_Core_Resources::singleton()->addScriptFile('org.civicoop.ibanaccounts', 'js/iban_account.js', -1);
    CRM_Core_Resources::singleton()->addScriptFile('org.civicoop.ibanaccounts', 'js/'.strtolower($this->getName()).'.js', 10);
  }

}
