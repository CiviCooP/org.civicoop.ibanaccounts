function IBAN_Membership() {
    IBAN_Account.call(this);
}

IBAN_Membership.prototype = new IBAN_Account();
IBAN_Membership.prototype.constructor = IBAN_Membership;
IBAN_Membership.prototype.super = new IBAN_Account();
IBAN_Membership.prototype.iban_custom_field_block = '#IBAN_Membership';

IBAN_Membership.prototype.contributionContactElement = '#contribution_contact_1';
IBAN_Membership.prototype.contributionContactHiddenElement = 'input[name="contribution_contact_select_id[1]"]';
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
        var contactId = cj(this.contributionContactHiddenElement).val();
        this.retrieveIbanAccountsForContact(contactId);
    }.bind(this));

    cj(this.contributionDifferentContactElement + ':checkbox').change(function(e) {
        if (!cj(this.contributionDifferentContactElement).is(':checked')) {
            var contactId = this.retrieveMembershipContactId();
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