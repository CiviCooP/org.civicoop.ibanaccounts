<div class="crm-block crm-form-block">

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>

<table class="form-layout">

<tr>
    <td class="label">{ts}Contact{/ts}</td>
    <td>{$contact_display_name}</td>
</tr>

{foreach from=$elementNames item=elementName}
  <tr class="">
    <td class="label">{$form.$elementName.label}</td>
    <td class="content">{$form.$elementName.html}</td>
  </tr>
{/foreach}

</table>

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

</div>
