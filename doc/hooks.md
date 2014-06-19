# Available hooks in this extension

## hook_civicrm_iban_usages

**Description**

This hook is invoked to retrieve info about the use iban accounts. 
If an IBAN account is used by an entity then this iban account cannot be removed from the system


**Spec**

    hook_civicrm_iban_usages($iban);

**Return value**

The hook should return an array with the entity as a key and the id as a key and the message to show to the user

**Example**

    mymodule_civicrm_iban_usages($iban)
        $sql = "SELECT membership.id, membership.name from civicrm_membership where iban = %1";
        $dao = CRM_Core_DAO::executeQuery($sql, array('1' => array($iban, 'String')));
        $return = array();
        while($dao->fetch()) {
            $return['civicrm_membership'][$dao->id] = ts("IBAN Account is used for membership %1", array(1 => $dao->name));
        }
        return $return;
    }

## hook_civicrm_remove_iban

**Description**

This hook is invoked when an iban numbers gets removed from the system. 
You can use this hook if you have to do some clean up.

**Spec**

    hook_civicrm_remove_iban($iban);

