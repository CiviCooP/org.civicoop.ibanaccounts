var IBAN_Membership = {
    
    contributionContactElement: '#contribution_contact_1',
    contributionContactHiddenElement: 'input[name="contribution_contact_select_id[1]"]',
    contributionDifferentContactElement: '#is_different_contribution_contact',

    contactElement: '#contact_1',
    contactHiddenElement: 'input[name="contact_select_id[1]"]',
    
    currentContactId: false,
    
    init: function(membershipContactId) {
        this.membershipContactId = membershipContactId;
        this.initEventHandlers(this);
    },
    retrieveMembershipContactId: function() {
        if (cj(this.contributionDifferentContactElement).is(':checked')) {
            return cj(this.contributionContactHiddenElement).val();
        } else if (cj(this.contactElement)) {
            return cj(this.contactHiddenElement).val();
        }
        return this.membershipContactId;
    },
    retrieveIbanAccountsForContact: function(contactId) {
        if (contactId == this.currentContactId) {
            return;
        }
        this.currentConatctId = contactId;
        
        cj('#iban_account').find('option').each(function(index) {
            if (cj(this).val() > 0) {
                cj(this).remove();
            }
        });
        if (contactId > 0) {
            CRM.api('IbanAccount', 'get', {'contact_id': contactId}, {
                success: function(data) {
                    cj.each(data.values, function(key, value) {
                        cj('#iban_account option[value="-1"]').before('<option value="' + value.id + '">' + value.iban + '</option>');
                    });
                }
            });
        }
    },
    initEventHandlers: function(ctx) {
        //init change handler for show/hide
        cj('#iban_account').change(function(e) {
            if (cj('#iban_account').val() == -1) {
                cj('tr.crm-membership-form-block-iban').focus();
                cj('tr.crm-membership-form-block-iban').removeClass('hiddenElement');
                cj('tr.crm-membership-form-block-bic').removeClass('hiddenElement');
            } else {
                cj('tr.crm-membership-form-block-iban').addClass('hiddenElement');
                cj('tr.crm-membership-form-block-bic').addClass('hiddenElement');
            }
        });

        //init onchange handlers to change the iban options
        cj(this.contactElement).blur(function(e) {
            var contactId = this.retrieveMembershipContactId();
            this.retrieveIbanAccountsForContact(contactId);
        }.bind(this));
        

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
    }

};