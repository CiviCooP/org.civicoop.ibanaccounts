<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Ibanaccounts_Form_IbanAccount extends CRM_Core_Form {
  
  protected $_contactId;
  
  function preProcess() {
  	if (!CRM_Ibanaccounts_Config::accessToIbanAccounts()) {
  		CRM_Core_Session::setStatus('U hebt geen toegang tot deze pagina.', '', 'info');
	    $referer = CRM_Utils_System::refererPath();
	    if ($referer && strpos($referer, $_SERVER['REQUEST_URI']) === false) {
	      CRM_Utils_System::redirect($referer);
	    }
	    else {
	      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/dashboard'));
	    }
  	}
		
    parent::preProcess();
    
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->assign('contact_display_name', CRM_Contact_BAO_Contact::displayName($this->_contactId));
    
    //set user context
    $session = CRM_Core_Session::singleton();
    $userContext = CRM_Utils_System::url('civicrm/contact/view', 'cid='.$this->_contactId.'&selectedChild=iban_accounts&reset=1');
    $session->pushUserContext($userContext);
  }
  
  
  function buildQuickForm() {

    CRM_Utils_System::setTitle(ts('IBAN Accounts'));
    
    // add form elements
    $this->add('text', 'iban', ts('IBAN'), '', true);
    $this->add('text', 'bic', ts('BIC'), '', false);
    $this->add('text', 'tnv', ts('Ten name van'), '', false);
    $this->add('hidden', 'cid', $this->_contactId);
    
    $this->addButtons(array(
      array(
        'type' => 'done',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  function postProcess() {
    $values = $this->exportValues();

    if (empty($values['bic'])) {
      $values['bic'] = CRM_Ibanaccounts_Utils_IbanToBic::getBic($values['iban']);
    }
    
    $iban_account_id = CRM_Ibanaccounts_Ibanaccounts::saveIBANForContact($values['iban'], $values['bic'], $values['tnv'], $this->_contactId);
    
    parent::postProcess();
  }
  
  function validate() {
    
    $iban_error = CRM_Ibanaccounts_Validator::validateIbanField($this->_submitValues['iban'], $this->_contactId);
    if (!empty($iban_error)) {
      $this->_errors['iban'] = $iban_error;
    }
    
    return parent::validate();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
}
