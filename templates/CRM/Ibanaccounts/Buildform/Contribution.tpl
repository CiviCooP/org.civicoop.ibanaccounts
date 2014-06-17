{capture name="iban_account" assign="iban_account"}
<tr class="crm-iban_account-form-block-iban_account">
    <td class="label">{$form.iban_account.label}</td>
    <td>{$form.iban_account.html}</td>
</tr>
<tr class="crm-iban_account-form-block-iban hiddenElement">
    <td class="label">{$form.iban.label}</td>
    <td>{$form.iban.html}</td>
</tr>
<tr class="crm-iban_account-form-block-bic hiddenElement">
    <td class="label">{$form.bic.label}</td>
    <td>{$form.bic.html}</td>
</tr>
{/capture}

<script type="text/javascript">
{literal}
cj(function() {
    cj('tr.crm-contribution-form-block-contribution_status_id').after('{/literal}{$iban_account|escape:'javascript'}{literal}');
    var iban_contribution = new IBAN_Contribution();
    iban_contribution.init('{/literal}{$snippet.contact_id}{literal}');
});
{/literal}
</script>