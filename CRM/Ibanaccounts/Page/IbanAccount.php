<?php

require_once 'CRM/Core/Page.php';

class CRM_Ibanaccounts_Page_IbanAccount extends CRM_Core_Page {
  
  protected $_contactId;
  
  function run() {
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $action = CRM_Utils_Request::retrieve('action', 'String');
    if (isset($action) && $action == CRM_Core_Action::DELETE) {
      $this->delete();
    }
    
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
  
  protected function delete() {
    $iban  = CRM_Utils_Request::retrieve('iban', 'String', $this, TRUE);
    $session = CRM_Core_Session::singleton();
    
    $usages = CRM_Ibanaccounts_Ibanaccounts::getIBANUsages($iban);
    if (count($usages)) {
      $message = ts('IBAN Account is in use').'<br><br>';
      foreach($usages as $usage) {
        $message .= $usage .'<br>';
      }
      $session->setStatus($message, ts('Delete'), 'alert');
    } else {
      CRM_Ibanaccounts_Ibanaccounts::removeIban($iban, $this->_contactId);
      $session->setStatus(ts("IBAN Account removed."), ts("Delete"), 'success');
    }
    
    
    
    
    $userContext = CRM_Utils_System::url('civicrm/contact/view', 'cid='.$this->_contactId.'&selectedChild=iban_accounts&reset=1');
    CRM_Utils_System::redirect($userContext);
  }
}
