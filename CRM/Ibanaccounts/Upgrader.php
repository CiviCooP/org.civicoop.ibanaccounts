<?php

/**
 * Collection of upgrade steps
 */
class CRM_Ibanaccounts_Upgrader extends CRM_Ibanaccounts_Upgrader_Base {


  public function install() {
    $this->executeSqlFile('sql/install.sql');
    $this->executeCustomDataFile('xml/iban.xml');
  }

  public function uninstall() {
   $this->deleteCustomGroup('IBAN');
   $this->executeSqlFile('sql/uninstall.sql');
  }
  
  public function enable() {
    $this->enableCustomGroup('IBAN', true);
  }
  
  public function disable() {
    $this->enableCustomGroup('IBAN', false);
  }
  
  protected function deleteCustomGroup($group_name) {
    $gid = civicrm_api3('CustomGroup', 'getvalue', array('name' => $group_name, 'return'=>'id'));
    $fields = civicrm_api3('CustomField', 'get', array('custom_group_id' => $gid));
    foreach($fields['values'] as $field) {
      civicrm_api3('CustomField', 'delete', array('id' => $field['id']));
    }
    civicrm_api3('CustomGroup', 'delete', array('id' => $gid));
  }  
  
  protected function enableCustomGroup($group_name, $enable) {
    $group = civicrm_api3('CustomGroup', 'getsingle', array('name' => $group_name));
    $group['is_active'] = ($enable ? '1' : '0');
    civicrm_api3('CustomGroup', 'create', $group);
  }

}
