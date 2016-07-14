function IBAN_Membership() {
    IBAN_Account.call(this);
}

IBAN_Membership.prototype = new IBAN_Account();
IBAN_Membership.prototype.constructor = IBAN_Membership;
IBAN_Membership.prototype.super = new IBAN_Account();
IBAN_Membership.prototype.iban_custom_field_block = '.custom-group-IBAN_Membership';

IBAN_Membership.prototype.contributionContactElement = '#contribution_contact_1';
IBAN_Membership.prototype.contributionContactHiddenElement = '#soft_credit_contact_id';
IBAN_Membership.prototype.contributionDifferentContactElement = '#is_different_contribution_contact';
IBAN_Membership.prototype.membership_type = '#membership_type_id_1';

IBAN_Membership.prototype.retrieveContactId = function() {
    if (cj(this.contributionDifferentContactElement).is(':checked')) {
        return cj(this.contributionContactHiddenElement).val();
    } else {
        return this.super.retrieveContactId.call(this);
    }

};

IBAN_Membership.prototype.initEventHandlers = function(ctx) {
    this.super.initEventHandlers.call(this, ctx);

    cj(this.contributionContactElement).blur(function(e) {
        var contactId = this.retrieveContactId();
        this.retrieveIbanAccountsForContact(contactId);
    }.bind(this));

    cj(this.contributionContactHiddenElement).change(function(e) {
        var contactId = this.retrieveContactId();
        this.retrieveIbanAccountsForContact(contactId);
    }.bind(this));

    cj(this.contributionDifferentContactElement + ':checkbox').change(function(e) {
        if (!cj(this.contributionDifferentContactElement).is(':checked')) {
            var contactId = this.retrieveContactId();
            this.retrieveIbanAccountsForContact(contactId);
        } else {
            var contactId = cj(this.contributionContactHiddenElement).val();
            this.retrieveIbanAccountsForContact(contactId);
        }
    }.bind(this));
    
    cj(this.membership_type).change(function(e) {
        this.hideCustomData();
    }.bind(this));
    
};