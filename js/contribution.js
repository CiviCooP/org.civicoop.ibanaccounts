function IBAN_Contribution() {
    IBAN_Account.call(this);
}

IBAN_Contribution.prototype = new IBAN_Account();
IBAN_Contribution.prototype.constructor = IBAN_Contribution;
IBAN_Contribution.prototype.iban_custom_field_block = '#IBAN_Contribution';



