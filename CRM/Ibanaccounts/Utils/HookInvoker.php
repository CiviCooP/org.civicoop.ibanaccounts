<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */
class CRM_Ibanaccounts_Utils_HookInvoker {

  private static $singleton;

  private function __construct() {

  }

  /**
   * This hook is invoked to retrieve info about the use iban accounts.
   *
   * If an IBAN account is used by an entity then this iban account
   * cannot be removed from the system.
   *
   * @param $iban_system
   * @param $contactId
   * @return array
   *   The hook should return an array with the entity as a key
   *   and the id as a key and the message to show to the user.
   */
  public function hook_civicrm_iban_usages($iban_system, $contactId) {
    return $this->invoke('civicrm_iban_usages', 2 , $iban_system, $contactId);
  }

  /**
   * This hook is invoked when an iban numbers gets removed from the system.
   *
   * You can use this hook if you have to do some clean up.
   *
   * @param $iban_system
   * @param $contactId
   * @return mixed
   */
  public function hook_civicrm_remove_iban($iban_system, $contactId) {
    return $this->invoke('civicrm_remove_iban', 2, $iban_system, $contactId);
  }

  /**
   * @return \CRM_Ibanaccounts_Utils_HookInvoker
   */
  public static function singleton() {
    if (!self::$singleton) {
      self::$singleton = new CRM_Ibanaccounts_Utils_HookInvoker();
    }
    return self::$singleton;
  }

  private function invoke($fnSuffix, $numParams, &$arg1 = null, &$arg2 = null, &$arg3 = null, &$arg4 = null, &$arg5 = null) {
    $hook =  CRM_Utils_Hook::singleton();
    $civiVersion = CRM_Core_BAO_Domain::version();

    if (version_compare($civiVersion, '4.5', '<')) {
      //in CiviCRM 4.4 the invoke function has 5 arguments maximum
      return $hook->invoke($numParams, $arg1, $arg2, $arg3, $arg4, $arg5, $fnSuffix);
    } else {
      //in CiviCRM 4.5 and later the invoke function has 6 arguments
      return $hook->invoke($numParams, $arg1, $arg2, $arg3, $arg4, $arg5, CRM_Utils_Hook::$_nullObject, $fnSuffix);
    }
  }

}