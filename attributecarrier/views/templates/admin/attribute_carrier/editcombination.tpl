{if $ErrorShops == false}
{if $list_combinations_group_by_shop}
<form enctype="multipart/form-data" id="attribute_carrier_config_form" method="post" action="{$url_action}">
	<fieldset style="margin-bottom: 35px;">
    <legend><i class="icon-cogs"></i> {l s='Update configuration' mod='attributecarrier'}</legend>
    <table border="0" cellspacing="0" cellpadding="5">
      <tr>
        <td align="left" valign="top">
        	{assign var=first_call value=0}
            {assign var=price_row value=0}
            {foreach from=$list_combinations_group_by_shop key=current_id_shop item=list_combinations}
                {if $first_call == 0}
                    {assign var=first_call value=1}
            		<!--caracteristiques-->
                    <table cellspacing="0" cellpadding="0" class="table tableDnD">
                        <thead>
                        <tr class="nodrag nodrop"> 
                            <th>{l s='Attribute name' mod='attributecarrier'}</th>
                        </tr>
                      </thead>
                      <tbody>
                    {if $list_combinations}
                        {foreach from=$list_combinations item=one_combination}
                        <tr>
                            <td class="left">{$one_combination.name}
                            <input name="id_attribute_carrier_config" type="hidden" value="{$one_combination.id_attribute_carrier_config}" /></td>
                        </tr>
                        {/foreach}
                    {/if}    
                    </tbody>
                  </table>
          		{/if}
             {/foreach}   
        </td>
        {foreach from=$list_combinations_group_by_shop key=current_id_shop item=list_combinations}
        	{assign var=price_row value=$list_combinations[0].price}
        <td align="left" valign="top">
        	<!--shops-->
            <table cellspacing="0" cellpadding="0" class="table tableDnD">
                <thead>
                  <tr class="nodrag nodrop"> 
                    <th>
                    {if $shops}
                        {foreach from=$shops item=shop}
                            {if $shop.id_shop == $current_id_shop}
                            	{$shop.name}
                            {/if}
                        {/foreach}
                    {else}
                        {l s='Value'  mod='attributecarrier'}
                    {/if}
                    </th>
                </tr>
              </thead>
              <tbody>
                    <tr>
                        <td class="center" nowrap="nowrap" style="border-bottom:0px !important;">
                        {if $shops}
                            <span style="display:inline-block; line-height:25px; white-space:nowrap;"><input name="price_{$current_id_shop}" type="text" value="{$price_row}" size="2" style="float:left;" />&nbsp;{$currency_sign}</span>
                        {else}
                            <span style="display:inline-block; line-height:25px; white-space:nowrap;"><input  name="price"  type="text" value="{$price_row}" size="2" style="float:left;" />&nbsp;{$currency_sign}</span>
                        {/if}
                        </td>
                    </tr>
            	</tbody>
      		</table>
        </td>
        {/foreach}
      </tr>
    </table>
    <br />
    <p><input type="submit" id="attribute_carrier_config_form_submit_btn" class="btn btn-primary" style="margin-bottom:5px;" name="edit_combination" value="{l s='Save' mod='attributecarrier'}" /></p>
   </fieldset> 
</form>    
{/if}
{/if}