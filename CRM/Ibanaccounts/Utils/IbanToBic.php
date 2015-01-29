<?php
/**
 * Created by PhpStorm.
 * User: jaap
 * Date: 1/29/15
 * Time: 10:49 AM
 */

class CRM_Ibanaccounts_Utils_IbanToBic {

    /**
     * Returns the BIC code from IBAN
     *
     * Requires the org.project60.bic extension
     *
     * @param $iban
     */
    public static function getBic($iban) {
        try {
            $config = CRM_Ibanaccounts_Config::singleton();
            if (!$config->isProject60BICExtensionEnabled()) {
                return '';
            }
            if (empty($iban)) {
                return '';
            }
            $bic = civicrm_api3('bic', 'getfromiban', array('iban' => $iban));
            if (!isset($bic['bic'])) {
                return '';
            }
            return $bic['bic'];
        } catch (Exception $e) {
            //do nothing
        }
        return '';
    }

}