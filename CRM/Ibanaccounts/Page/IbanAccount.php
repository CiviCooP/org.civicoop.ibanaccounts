<?php

require_once 'CRM/Core/Page.php';

class CRM_Ibanaccounts_Page_IbanAccount extends CRM_Core_Page {
  
  protected $_contactId;
  
  function run() {
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    
    CRM_Utils_System::setTitle(ts('IBAN Accounts'));
    
    $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($this->_contactId);
    $this->assign('accounts', $accounts);
    $this->assign('contactId', $this->_contactId);

    parent::run();
    
    //set user context
    $session = CRM_Core_Session::singleton();
    $userContext = CRM_Utils_System::url('civicrm/contact/view', 'cid='.$this->_contactId.'&selectedChild=iban_accounts&reset=1');
    $session->pushUserContext($userContext);
  }
}
