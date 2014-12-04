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
    $this->custom_groups['IBAN_Membership'] = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'IBAN_Membership'));
    $this->custom_groups['IBAN_Contribution'] = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'IBAN_Contribution'));
    
    //load all the fields for every custom group
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
   * 
   * @return CRM_Ibanaccounts_Config
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
  
  public function getIbanMembershipCustomGroupValue($field='id') {
    return $this->custom_groups['IBAN_Membership'][$field];
  }
  
  public function getIbanContributionCustomGroupValue($field='id') {
    return $this->custom_groups['IBAN_Contribution'][$field];
  }
  
  public function getIbanCustomFieldValue($field='id') {
    return $this->custom_fields['IBAN']['IBAN'][$field];
  }
  
  public function getBicCustomFieldValue($field='id') {
    return $this->custom_fields['IBAN']['BIC'][$field];
  }
  
  public function getIbanMembershipCustomFieldValue($field='id') {
    return $this->custom_fields['IBAN_Membership']['IBAN'][$field];
  }
  
  public function getBicMembershipCustomFieldValue($field='id') {
    return $this->custom_fields['IBAN_Membership']['BIC'][$field];
  }
  
  public function getIbanContributionCustomFieldValue($field='id') {
    return $this->custom_fields['IBAN_Contribution']['IBAN'][$field];
  }
  
  public function getBicContributionCustomFieldValue($field='id') {
    return $this->custom_fields['IBAN_Contribution']['BIC'][$field];
  }
  
}