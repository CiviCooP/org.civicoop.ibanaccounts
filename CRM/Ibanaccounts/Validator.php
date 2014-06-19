<?php

/* 
 * Validate functionality for IBAN Accounts
 * 
 */

class CRM_Ibanaccounts_Validator {
  
  /**
   * Returns an empty string or an error message
   * 
   * @param type $iban
   */
  public static function validateIbanField($iban, $contactId) {
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
  
}

