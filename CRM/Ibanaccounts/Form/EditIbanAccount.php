<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Ibanaccounts_Form_EditIbanAccount extends CRM_Core_Form {
  
  protected $_contactId;

  protected $_ibanId;
  
  function preProcess() {
    parent::preProcess();
    
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->_ibanId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->assign('contact_display_name', CRM_Contact_BAO_Contact::displayName($this->_contactId));
    
    //set user context
    $session = CRM_Core_Session::singleton();
    $userContext = CRM_Utils_System::url('civicrm/contact/view', 'cid='.$this->_contactId.'&selectedChild=iban_accounts&reset=1');
    $session->pushUserContext($userContext);
  }
  
  
  function buildQuickForm() {

    CRM_Utils_System::setTitle(ts('IBAN Accounts'));

    $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($this->_contactId);
    $this->assign('account', $accounts[$this->_ibanId]);

    $this->add('text', 'tnv', ts('Ten name van'), '', false);
    $this->add('hidden', 'cid', $this->_contactId);
    $this->add('hidden', 'id', $this->_ibanId);
    
    $this->addButtons(array(
      array(
        'type' => 'done',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    //$this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  function setDefaultValues() {
    $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($this->_contactId);
    $account = $accounts[$this->_ibanId];
    return $account;
  }

  function postProcess() {
    $values = $this->exportValues();
    $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($this->_contactId);
    $account = $accounts[$this->_ibanId];
    
    $iban_account_id = CRM_Ibanaccounts_Ibanaccounts::saveIBANForContact($account['iban'], $account['bic'], $values['tnv'], $this->_contactId);
    
    parent::postProcess();
  }




}
