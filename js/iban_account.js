function IBAN_Account() {
}

IBAN_Account.prototype.contactElement = '#contact_1';
IBAN_Account.prototype.contactHiddenElement = 'input[name="contact_select_id[1]"]';

IBAN_Account.prototype.iban_custom_field_block = '';

IBAN_Account.prototype.currentContactId = false;

IBAN_Account.prototype.contactId = false;

IBAN_Account.prototype.init = function(contactId) {
    this.contactId = contactId;
    this.hideCustomData();
    this.initEventHandlers(this);
};

IBAN_Account.prototype.hideCustomData = function() {
    cj(this.iban_custom_field_block).remove(); //remove the custom fields for iban
}

IBAN_Account.prototype.retrieveContactId = function() {
    if (cj(this.contactHiddenElement) && cj(this.contactHiddenElement).val()) {
        return cj(this.contactHiddenElement).val();
    }
    return this.contactId;
};

IBAN_Account.prototype.retrieveIbanAccountsForContact = function(contactId) {
    if (contactId === this.currentContactId) {
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
};


IBAN_Account.prototype.initEventHandlers = function(ctx) {
    //init change handler for show/hide
    cj('#iban_account').change(function(e) {
        if (cj('#iban_account').val() == -1) {
            cj('tr.crm-iban_account-form-block-iban').removeClass('hiddenElement');
            cj('tr.crm-iban_account-form-block-bic').removeClass('hiddenElement');
            cj('tr.crm-iban_account-form-block-tnv').removeClass('hiddenElement');
            cj('tr.crm-iban_account-form-block-iban #iban').focus();
        } else {
            cj('tr.crm-iban_account-form-block-iban').addClass('hiddenElement');
            cj('tr.crm-iban_account-form-block-bic').addClass('hiddenElement');
            cj('tr.crm-iban_account-form-block-tnv').addClass('hiddenElement');
        }
    });

    cj('#iban_account').trigger('change'); //set to right display on initial

    //init onchange handlers to change the iban options
    cj(this.contactElement).blur(function(e) {
        var contactId = this.retrieveContactId();
        this.retrieveIbanAccountsForContact(contactId);
    }.bind(this));
};
