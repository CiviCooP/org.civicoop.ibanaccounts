<?php

/**
 * Singleton class to hold config settings for the iban module
 */

class CRM_Ibanaccounts_Config {
  
  protected static $_instance;
  
  protected $custom_groups = array();
  
  protected $custom_fields = array();
  
  protected function __construct() {
    $this->custom_groups['IBAN'] = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'IBAN'));
    foreach($this->custom_groups as $gname => $group) {
      $this->custom_fields[$gname] = array();
      $fields = civicrm_api3('CustomField', 'get', array('custom_group_id' => $group['id']));
      foreach($fields['values'] as $field) {
        $fname = $field['name'];
        $this->custom_fields[$gname][$fname] = $field;
      }
    }
  }
  
  /**
   * Singleton instanciated function
   */
  public static function singleton() {
    if (!self::$_instance) {
      self::$_instance = new CRM_Ibanaccounts_Config();
    }
    return self::$_instance;
  }
  
  public function getIbanCustomGroupValue($field='id') {
    return $this->custom_groups['IBAN'][$field];
  }
  
  public function getIbanCustomFieldValue($field='id') {
    return $this->custom_fields['IBAN']['IBAN'][$field];
  }
  
}