<div class="leadin">{block name="leadin"}{/block}</div>

{if $generate}
	<div class="bootstrap">
        <div class="module_confirmation conf confirm alert alert-success">
            <button data-dismiss="alert" class="close" type="button">×</button>
            {l s='%1$d combinations successfully created.' sprintf=[$combinations_size]   mod='attributecarrier'}
        </div>
    </div>
{/if}
<form enctype="multipart/form-data" method="post" id="generator" action="{$url_generator}">
	<fieldset style="margin-bottom: 35px;">
		<legend><i class="icon-cogs"></i> {l s='Attributes generator' mod='attributecarrier'}</legend>
		<div  class="col-sm-4 col-sx-12">
			<select multiple name="attributes[]" id="attribute_group" style="height: 500px; margin-bottom: 10px;">
				{foreach $attribute_groups as $k => $attribute_group}
					{if isset($attribute_js[$attribute_group['id_attribute_group']])}
						<optgroup name="{$attribute_group['id_attribute_group']}" id="{$attribute_group['id_attribute_group']}" label="{$attribute_group['name']|escape:'htmlall':'UTF-8'}">
							{foreach $attribute_js[$attribute_group['id_attribute_group']] as $k => $v}
								<option name="{$k}" id="attr_{$k}" value="{$v|escape:'htmlall':'UTF-8'}" title="{$v|escape:'htmlall':'UTF-8'}">{$v|escape:'htmlall':'UTF-8'}</option>
							{/foreach}
						</optgroup>
					{/if}
				{/foreach}
			</select>
			<div style="text-align: center; margin-bottom: 10px;">
				<p>
					<input class="btn btn-default" type="button" style="margin-right: 15px;" value="{l s='Add' mod='attributecarrier'}" onclick="add_attr_multiple();" />
					<input class="btn btn-default" type="button" value="{l s='Reset' mod='attributecarrier'}"  onclick="del_attr_multiple();" />
				</p>
			</div>
		</div>
		<div  class="col-sm-8 col-sx-12">
			<div class="well well-sm">{l s='The Combinations Generator is a tool that allows you to easily create a series of combinations by selecting the related attributes. For example, if you\'re selling t-shirts in three different sizes and two different colors, the generator will create six combinations for you.' mod='attributecarrier'}</div>
			<div class="well well-sm"><h4>{l s='On the left side, select the attributes you want to use (Hold down the "Ctrl" key on your keyboard and validate by clicking on "Add")' mod='attributecarrier'}</h4></div>
			<div>
			{foreach $attribute_groups as $k => $attribute_group}
				{if isset($attribute_js[$attribute_group['id_attribute_group']])}
					<table class="table clear" cellpadding="0" cellspacing="0" style="margin-bottom: 10px; display: none;">
						<thead>
							<tr>
								<th id="tab_h1" style="width: 150px">{$attribute_group['name']|escape:'htmlall':'UTF-8'}</th>
							</tr>
						</thead>
						<tbody id="table_{$attribute_group['id_attribute_group']}" name="result_table">
						</tbody>
					</table>
					{if isset($attributes[$attribute_group['id_attribute_group']])}
						{foreach $attributes[$attribute_group['id_attribute_group']] AS $k => $attribute}
							<script type="text/javascript">
								$('#table_{$attribute_group['id_attribute_group']}').append(create_attribute_row({$k}, {$attribute_group['id_attribute_group']}, '{$attribute['attribute_name']|addslashes}', {$attribute['price']}, {$attribute['weight']}));
								toggle(getE('table_' + {$attribute_group['id_attribute_group']}).parentNode, true);
							</script>
						{/foreach}						
					{/if}
				{/if}
			{/foreach}
            </div>
            <h4>{l s='Default Shipping Price' mod='attributecarrier'}</h4>
			<table border="0" class="table" cellpadding="0" cellspacing="0">
				<tr>
					<td nowrap="nowrap"><span style="display:inline-block; white-space:nowrap; line-height:25px;"><input type="text" size="20" name="default_shipping_price" value="{$default_shipping_price}" style="width: 50px; float:left;" />&nbsp;{$currency_sign}</span></td>
				</tr>
			</table>
			<h4>{l s='Please click on "Generate these Combinations"' mod='attributecarrier'}</h4>
            <input name="implode_currect_shops" type="hidden" value="{$implode_currect_shops}" />
			<p><input type="submit" class="btn btn-primary" style="margin-bottom:5px;" name="generate" value="{l s='Generate these Combinations' mod='attributecarrier'}" /></p>
		</div>
	</fieldset>
</form>