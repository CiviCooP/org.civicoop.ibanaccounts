<h2>{ts}IBAN Accounts{/ts}</h2>

{if ($permission == 'edit')}
<div class="action-link">
    {assign var='add_q' value='action=add&cid='|cat:$contactId}
    <a accesskey="N" href="{crmURL p='civicrm/contact/ibanaccount/add' q=$add_q h=0}" class="button">
        <span>
            <div class="icon add-icon"></div>
            {ts}Add IBAN Account{/ts}
        </span>
    </a>
</div>
{/if}

<table>
    <tr>
        <th>{ts}IBAN{/ts}</th>
        <th>{ts}BIC{/ts}</th>
        <th>{ts}Ten name van{/ts}</th>
        {if ($permission == 'edit')}<th></th>{/if}
    </tr>
    {foreach from=$accounts item=account}
        <tr>
            <td>{$account.iban_human}</td>
            <td>{$account.bic}</td>
            <td>{$account.tnv}</td>
            {if ($permission == 'edit')}<td>
                {assign var='edit_q' value='action=update&cid='|cat:$contactId|cat:'&id='|cat:$account.id}
                <a href="{crmURL p='civicrm/contact/ibanaccount/edit' q=$edit_q h=0}" class="">{ts}Edit{/ts}</a>
                {assign var='delete_q' value='action=delete&cid='|cat:$contactId|cat:'&iban='|cat:$account.iban}
                <a href="{crmURL p='civicrm/contact/ibanaccount/view' q=$delete_q h=0}" class="" onclick="return confirm('{ts}Are you sure?{/ts}');">{ts}Delete{/ts}</a>
            </td>{/if}
        </tr>
    {/foreach}
</table>