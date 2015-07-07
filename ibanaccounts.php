<?php

require_once 'ibanaccounts.civix.php';
require_once('php-iban/oophp-iban.php');

/**
 * Check if an iban is in use by a membership
 * 
 * @param string $iban
 * @param int|bool $contactId
 * @return array
 */
function ibanaccounts_civicrm_iban_usages($iban, $contactId = false) {
  $config = CRM_Ibanaccounts_Config::singleton();
  $table = $config->getIbanMembershipCustomGroupValue('table_name');
  $iban_field = $config->getIbanMembershipCustomFieldValue('column_name');
  
  $sql = "SELECT `m`.`id` AS `id`, `mtype`.`name` FROM `".$table."` `i` INNER JOIN `civicrm_membership` `m` ON `i`.`entity_id`  = `m`.`id` INNER JOIN `civicrm_membership_type` `mtype` ON `m`.`membership_type_id`  = `mtype`.`id` WHERE `i`.`".$iban_field."` = %1 AND `m`.`contact_id` = %2";
  $dao = CRM_Core_DAO::executeQuery($sql, array(
    '1' => array($iban, 'String'),
    '2' => array($contactId, 'Integer'),
  ));
  $return = array();
  while($dao->fetch()) {
    $return['civicrm_membership'][$dao->id] = ts("IBAN Account is used for membership %1", array(1 => $dao->name));
  }
  
  //check if iban is in use at contribution level
  $table = $config->getIbanContributionCustomGroupValue('table_name');
  $iban_field = $config->getIbanContributionCustomFieldValue('column_name');
  $sql = "SELECT COUNT(`m`.`id`) AS `contribution_count` FROM `".$table."` `i` INNER JOIN `civicrm_contribution` `m` ON `i`.`entity_id`  = `m`.`id` WHERE `i`.`".$iban_field."` = %1 AND `m`.`contact_id` = %2";
  $dao = CRM_Core_DAO::executeQuery($sql, array(
    '1' => array($iban, 'String'),
    '2' => array($contactId, 'Integer'),
  ));
  $return = array();
  while($dao->fetch() && $dao->contribution_count) {
    $return['civicrm_contribution'][1] = ts("IBAN Account is used for %1 contributions", array(1 => $dao->contribution_count));
  }
  return $return;
} 



/**
 * Implementatio of hook__civicrm_tabs
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_tabs
 */
function ibanaccounts_civicrm_tabs(&$tabs, $contactID) {
  $config = CRM_Ibanaccounts_Config::singleton();
  
  //unset the tab for iban accounts via custom fields and set our own tab for 
  //display the iban accounts
  $tab_id = 'custom_'.$config->getIbanCustomGroupValue('id'); 
  foreach($tabs as $key => $tab) {
   if ($tab['id'] == $tab_id) {
     unset($tabs[$key]);
   } 
  }

  if (CRM_Core_Permission::check('access CiviContribute')) {
    $url = CRM_Utils_System::url('civicrm/contact/ibanaccount/view', "cid=$contactID&snippet=1");

    //Count rules
    $accounts = CRM_Ibanaccounts_Ibanaccounts::IBANForContact($contactID);
    $tabs[] = array(
      'id' => 'iban_accounts',
      'url' => $url,
      'count' => count($accounts),
      'title' => ts('IBAN Accounts'),
      'weight' => -100
    );
  }
}

/**
 * Validate the entered IBAN account number
 * 
 * 
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_validateForm
 * @param type $formName
 * @param type $fields
 * @param type $files
 * @param type $form
 * @param type $errors
 */
function ibanaccounts_civicrm_validateForm( $formName, &$fields, &$files, &$form, &$errors ) {
  if ($formName == 'CRM_Contact_Form_CustomData') {
    $config = CRM_Ibanaccounts_Config::singleton();
    
    $groupId = $form->getVar('_groupID');
    if ($groupId != $config->getIbanCustomGroupValue('id')) {
      return;
    }

    $iban = new IBAN();
    foreach($fields as $key => $value) {
      if (strpos($key, "custom_".$config->getIbanCustomFieldValue('id'))===0) {
        if (!$iban->Verify($value)) {
          $errors[$key] = ts("'".$value."' is not a valid IBAN");
        }
      }
    }
  } elseif ($formName == 'CRM_Member_Form_Membership') {
   $membership = new CRM_Ibanaccounts_Buildform_Membership($form);
   $membership->validateForm($fields, $files, $errors);
  }
  if ($formName == 'CRM_Contribute_Form_Contribution') {
   $contribution = new CRM_Ibanaccounts_Buildform_Contribution($form);
   $contribution->validateForm($fields, $files, $errors);
  }
   if ($formName == 'CRM_Member_Form_MembershipRenewal') {
   $membership = new CRM_Ibanaccounts_Buildform_MembershipRenewal($form);
   $membership->validateForm($fields, $files, $errors);
 }
}

/**
 * 
 * Implementation of hook_civicrm_buildForm
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_buildForm
 */
function ibanaccounts_civicrm_buildForm($formName, &$form) {
 if ($formName == 'CRM_Member_Form_Membership') {
   //add template 
   $membership = new CRM_Ibanaccounts_Buildform_Membership($form);
   $membership->parse();
 } 
 if ($formName == 'CRM_Member_Form_MembershipRenewal') {
   $membership = new CRM_Ibanaccounts_Buildform_MembershipRenewal($form);
   $membership->parse();
 }
 if ($formName == 'CRM_Contribute_Form_Contribution') {
   $contribution = new CRM_Ibanaccounts_Buildform_Contribution($form);
   $contribution->parse();
 }
}

/**
 * 
 * Implementation of hook_civicrm_postProcess
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postProcess
 */
function ibanaccounts_civicrm_postProcess( $formName, &$form ) {
  if ($formName == 'CRM_Member_Form_Membership') {
   $membership = new CRM_Ibanaccounts_Buildform_Membership($form);
   $membership->postProcess();
  }
  if ($formName == 'CRM_Member_Form_MembershipRenewal') {
   $membership = new CRM_Ibanaccounts_Buildform_MembershipRenewal($form);
   $membership->postProcess();
 }
  if ($formName == 'CRM_Contribute_Form_Contribution') {
   $contribution = new CRM_Ibanaccounts_Buildform_Contribution($form);
   $contribution->postProcess();
 }
}

/**
 * Implementation of hook_civicrm_post
 * 
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_post
 * @param type $op
 * @param type $objectName
 * @param type $objectId
 * @param type $objectRef
 */
function ibanaccounts_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  if ($objectName == 'MembershipPayment') {
    $membership_payment = new CRM_Ibanaccounts_Post_MembershipPayment();
    $membership_payment->post($op, $objectRef);
  }
}

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function ibanaccounts_civicrm_config(&$config) {
  _ibanaccounts_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function ibanaccounts_civicrm_xmlMenu(&$files) {
  _ibanaccounts_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function ibanaccounts_civicrm_install() {
  return _ibanaccounts_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function ibanaccounts_civicrm_uninstall() {
  return _ibanaccounts_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function ibanaccounts_civicrm_enable() {
  return _ibanaccounts_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function ibanaccounts_civicrm_disable() {
  return _ibanaccounts_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function ibanaccounts_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _ibanaccounts_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function ibanaccounts_civicrm_managed(&$entities) {
  return _ibanaccounts_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function ibanaccounts_civicrm_caseTypes(&$caseTypes) {
  _ibanaccounts_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function ibanaccounts_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _ibanaccounts_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
