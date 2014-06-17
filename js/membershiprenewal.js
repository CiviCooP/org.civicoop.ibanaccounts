function IBAN_Membershiprenewal() {
    IBAN_Account.call(this);
}

IBAN_Membershiprenewal.prototype = new IBAN_Account();
IBAN_Membershiprenewal.prototype.constructor = IBAN_Membershiprenewal;
IBAN_Membershiprenewal.prototype.super = new IBAN_Account();
IBAN_Membershiprenewal.prototype.iban_custom_field_block = '#IBAN_Membership';

IBAN_Membershiprenewal.prototype.contributionContactElement = '#contribution_contact_1';
IBAN_Membershiprenewal.prototype.contributionContactHiddenElement = 'input[name="contribution_contact_select_id[1]"]';
IBAN_Membershiprenewal.prototype.contributionDifferentContactElement = '#contribution_contact';
IBAN_Membershiprenewal.prototype.membership_type = '#membership_type_id_1';

IBAN_Membershiprenewal.prototype.retrieveContactId = function() {
    if (cj(this.contributionDifferentContactElement).is(':checked')) {
        return cj(this.contributionContactHiddenElement).val();
    } else {
        return this.super.retrieveContactId.call(this);
    }

};

IBAN_Membershiprenewal.prototype.initEventHandlers = function(ctx) {
    this.super.initEventHandlers.call(this, ctx);

    cj(this.contributionContactElement).blur(function(e) {
        var contactId = cj(this.contributionContactHiddenElement).val();
        this.retrieveIbanAccountsForContact(contactId);
    }.bind(this));

    cj(this.contributionDifferentContactElement + ':checkbox').change(function(e) {
        if (!cj(this.contributionDifferentContactElement).is(':checked')) {
            cj(this.contributionContactElement).val('');
        }
        var contactId = this.retrieveContactId();
        this.retrieveIbanAccountsForContact(contactId);
    }.bind(this));
    
    cj(this.membership_type).change(function(e) {
        this.hideCustomData();
    }.bind(this));
    
};