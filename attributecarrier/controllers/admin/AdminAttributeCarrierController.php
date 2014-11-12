<?php
class AdminAttributeCarrierController extends ModuleAdminController
{
	protected $combinations = array();
	
	public function __construct()
	{ 
		$this->bootstrap = true;
		$asso_table = Shop::getAssoTable('attribute_carrier_config');
		if ($asso_table === false)
			Shop::addTableAssociation('attribute_carrier_config', array('type' => 'shop'));
		
		$this->table = 'attribute_carrier_config';
		$this->className = 'AttributeCarrierConfig';
		$this->multishop_context_group = false;
		/******/
		$this->required_database = false;
		$this->required_fields = array('id_attribute_carrier_config','price');
		$this->lang = false;
		$this->explicitSelect = true;
		$this->allow_export = false;
		$this->deleted = false;
		$this->context = Context::getContext();
		$this->edit = true;
		$this->delete = true;
		$this->addRowAction('edit');
		//$this->addRowAction('view');
		$this->addRowAction('delete');
		$this->bulk_actions = array('delete' => array('text' => $this->l('Delete selected'), 'confirm' => $this->l('Would you like to delete the selected items ?')));

		$this->context = Context::getContext();

		$this->default_form_language = $this->context->language->id;
		
		$this->identifier = 'id_attribute_carrier_config';
		
		/*$this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'attribute_carrier_config_combination` pac ON (pac.`id_attribute_carrier_config` = a.`id_attribute_carrier_config`) 
		
		LEFT JOIN `'._DB_PREFIX_.'attribute` att 
			ON (att.`id_attribute` = pac.`id_attribute`)
		'.Shop::addSqlAssociation('attribute', 'att').'
		LEFT JOIN `'._DB_PREFIX_.'attribute_lang` attl
			ON (att.`id_attribute` = attl.`id_attribute` AND attl.`id_lang` = '.(int)$this->context->language->id.')';*/
			
		/*$this->_join .=  'INNER JOIN `'._DB_PREFIX_.'attribute_carrier_config` b ON (b.`id_attribute_carrier_config` = a.`id_attribute_carrier_config`)';
		'INNER JOIN '._DB_PREFIX_.'attribute_carrier_config_shop b
		ON (b.id_attribute_carrier_config = a.id_attribute_carrier_config) ' ;*/
		
		//die(Shop::addSqlAssociation('attribute_carrier_config', 'a'));
		//$this->_join .=  Shop::addSqlAssociation('attribute_carrier_config', 'a');
		$this->_select .= 'a.id_attribute_carrier_config as id';
		
		$this->fields_list['id_attribute_carrier_config'] = array(
			'title' => $this->l('ID'),
			'align' => 'center',
			'type' => 'int',
			'width' => 40
		);
		
		$this->fields_list['id'] = array(
			'title' => $this->l('List combinations'),
			'align' => 'left',
			'callback' => 'getAttributesFromConfig'
		);
		$this->fields_list['price'] = array(
			'title' => $this->l('Price'),
			'align' => 'left',
			'callback' => 'formatPriceAttributeConfig'
		);
		$this->fields_form = array(
			'legend' => array(
				'title' => $this->l('New Row Combination'),
				'image' => '../img/admin/tab-categories.gif'
			),
			'input' => array(
				// custom template
				array(
					'type' => 'text',
					'label' => $this->l('Name:'),
					'name' => 'price',
					'lang' => false,
					'required' => true,
					//'size' => 50
				),
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'button'
			)
		);
		/******/
		
		parent::__construct();
	}
	
	/*public function renderView()
	{
		if (!Combination::isFeatureActive())
		{
			$this->displayWarning($this->l('This feature has been disabled. You can activate it at:').'
				<a href="index.php?tab=AdminPerformance&token='.Tools::getAdminTokenLite('AdminPerformance').'#featuresDetachables">'.
					$this->l('Performance').'</a>');
			return;
		}
		$js_attributes = AdminAttributeCarrierController::displayAndReturnAttributeJs();
		$attribute_groups = AttributeGroup::getAttributesGroups($this->context->language->id);

		$this->context->smarty->assign(array(
			'generate' => isset($_POST['generate']) && !count($this->errors),
			'combinations_size' => count($this->combinations),
			'url_generator' => self::$currentIndex.'&id_product='.(int)Tools::getValue('id_product').'&attributegenerator&token='.Tools::getValue('token'),
			'attribute_groups' => $attribute_groups,
			'attribute_js' => $js_attributes,
		));
		
		return parent::renderView();
	}*/
	public function getAttributesFromConfig($id_attribute_carrier_config)
	{
		$output = "" ;
		$object_attribute_carrier_config = new $this->className($id_attribute_carrier_config);
		$list_combinations = $object_attribute_carrier_config->getListCombination($object_attribute_carrier_config->id);
		
		//printf('<hr><pre>%s</pre><hr>' , print_r($list_combinations , true));die();//betadev
		$list_combinations_names = array();
		if(count($list_combinations) > 0) {
			$first_call_id_shop = 0 ;
			foreach($list_combinations as $on_combination) {
				if($first_call_id_shop != 0 && $on_combination['id_shop'] != $first_call_id_shop) 
					break ;
				$list_combinations_names[] = "<strong>".$on_combination['public_name']."</strong> : <em>".$on_combination['name']."</em>" ;
				$first_call_id_shop = $on_combination['id_shop'] ;
			}
		}
		
		return implode(", ", $list_combinations_names) ;
	}
	
	public function formatPriceAttributeConfig($price)
	{
		return number_format($price, 6, ",", " ") . " " . Context::getContext()->currency->sign ; 
	}
	
	public function editCombinationShipping()
	{
		$this->toolbar_btn['save-and-stay'] = array(
			'short' => 'SaveAndStay',
			'href' => '#',
			'desc' => $this->l('Save and stay'),
		);
		$this->initToolbar();
		$this->setTemplate('editcombination.tpl');
		$id_attribute_carrier_config = (int) Tools::getValue('id_attribute_carrier_config') ;
		$object_attribute_carrier_config = new $this->className($id_attribute_carrier_config);
		//printf('<pre>%s</pre>' , print_r($object_attribute_carrier_config , true)); die();
		
		if (Shop::isFeatureActive()) {
			$ErrorShops = true ;
			$currect_shops = Shop::getContextListShopID();	
		} else {
			$ErrorShops = false ;	
		}
			
		$list_combinations = $object_attribute_carrier_config->getListCombination($object_attribute_carrier_config->id);
		$list_combinations_group_by_shop = array();
		if(count($list_combinations) > 0) {
			foreach($list_combinations as $on_combination) {
				$list_combinations_group_by_shop[$on_combination['id_shop']][] = $on_combination ;
				if (Shop::isFeatureActive()) {
					if(in_array($on_combination['id_shop'], $currect_shops))
						$ErrorShops = false ;	
				}
			}
		}
		
		if (Shop::isFeatureActive()) {
			if($ErrorShops == true)
				$this->errors[] = Tools::displayError('Selected combination doese not exist in this shop !');
		}
		
		$shops = false;
		if (Shop::isFeatureActive())
			$shops = Shop::getShops();
			
		$this->context->smarty->assign(array(
			'currency_sign' => $this->context->currency->sign,
			'default_shipping_price' => Configuration::get('ATT_CARRIER_DEFAULT_SHIPPING_PR'),
			'shops' => $shops,
			'list_combinations_group_by_shop' => $list_combinations_group_by_shop,
			'url_action' => self::$currentIndex.'&action=edit&token='.Tools::getValue('token'),
			'toolbar_btn' => $this->toolbar_btn,
			'toolbar_scroll' => 1,
			'title' => $this->l('Edit combination shiping'),
			'ErrorShops' => $ErrorShops,
		));
	}
	
	public function renderForm()
	{
		$currect_shops = Shop::getContextListShopID();
	
		if($this->display == "edit") 
			return $this->editCombinationShipping();
		
		if (!Combination::isFeatureActive())
		{
			$this->displayWarning($this->l('This feature has been disabled. You can activate it at:').'
				<a href="index.php?tab=AdminPerformance&token='.Tools::getAdminTokenLite('AdminPerformance').'#featuresDetachables">'.
					$this->l('Performance').'</a>');
			return;
		}
		
		$js_attributes = AdminAttributeCarrierController::displayAndReturnAttributeJs();
		$attribute_groups = AttributeGroup::getAttributesGroups($this->context->language->id);
				
		$this->context->smarty->assign(array(
			'currency_sign' => $this->context->currency->sign,
			'generate' => isset($_POST['generate']) && !count($this->errors),
			'combinations_size' => count($this->combinations),
			'url_generator' => self::$currentIndex.'&attributegenerator&token='.Tools::getValue('token'),
			'attribute_groups' => $attribute_groups,
			'attribute_js' => $js_attributes,
			'default_shipping_price' => Configuration::get('ATT_CARRIER_DEFAULT_SHIPPING_PR'),
			'implode_currect_shops' => implode("-", $currect_shops)
		));
		return parent::renderForm();
	}
	
	public function setMedia()
	{
		$this->addJS('/modules/attributecarrier/views/js/attributesBack.js');
		return parent::setMedia();
	}
	
	public function initProcess()
	{
		if (Tools::isSubmit('generate')) {
			$this->action = 'generate';
			$this->display = "add" ;
		} elseif (Tools::getValue('edit_combinationAndStay') || Tools::getValue('edit_combination')) {
			$this->action = 'edit';
		}
		//die($this->action);	
		parent::initProcess();
	}
	
	
	public function postProcess()
	{
		//die($this->action);
		//printf('<pre>%s</pre>' , print_r($_POST , true)); die();
		parent::postProcess();
	}
	
	protected function processBulkDelete()
	{
		if (is_array($this->boxes) && !empty($this->boxes))
		{
			$success = 1;
			$configs = Tools::getValue($this->table.'Box');
			if (is_array($configs) && ($count = count($configs)))
			{
				// Deleting products can be quite long on a cheap server. Let's say 1.5 seconds by product (I've seen it!).
				if (intval(ini_get('max_execution_time')) < round($count * 1.5))
					ini_set('max_execution_time', round($count * 1.5));

				foreach ($configs as $id_attribute_carrier_config)
				{
					$one_config = new AttributeCarrierConfig((int)$id_attribute_carrier_config);
					
					if (!$one_config->delete())
						$success = false;
				}
			}
			
			if ($success) {
				Tools::redirectAdmin($this->context->link->getAdminLink('AdminAttributeCarrier').'&conf=4');
			} else {
				$this->errors[] = Tools::displayError('An error occurred while deleting this selection.');
			}
		}
		else
			$this->errors[] = Tools::displayError('You must select at least one element to delete.');
	}
	
	public function processEdit()
	{
		//printf('<pre>%s</pre>' , print_r($_POST , true)); die();
		$id_attribute_carrier_config = (int)Tools::getValue('id_attribute_carrier_config');
		
		$shops = false;
		if (Shop::isFeatureActive()) 
			$shops = Shop::getShops();
		
		if($shops) {
			foreach($shops as $one_shop) {
				if(array_key_exists('price_'.$one_shop['id_shop'], $_POST)) {
					$ObjAttributeCarrierConfig = new AttributeCarrierConfig($id_attribute_carrier_config, null, $one_shop['id_shop']);
					$price = (float) Tools::getValue('price_'.$one_shop['id_shop']);
					$ObjAttributeCarrierConfig->price = $price ;
					$ObjAttributeCarrierConfig->id_shop_list = array($one_shop['id_shop']) ;
					//print $price."-$id_attribute_carrier_config-".$one_shop['id_shop']."<br>" ;
					if(!$ObjAttributeCarrierConfig->update()) {
						$this->errors[] = Tools::displayError('An error occurred while updating the specific price.')." id_shop:".$one_shop['id_shop'];
					}
				}
			}
		} else {
			$ObjAttributeCarrierConfig = new AttributeCarrierConfig($id_attribute_carrier_config);
			$price = (float) Tools::getValue('price');
			$ObjAttributeCarrierConfig->price = $price ;
			if(!$ObjAttributeCarrierConfig->update()) {
				$this->errors[] = Tools::displayError('An error occurred while updating the specific price.');
			}	
		}
		if(count($this->errors) == 0) {
			if(Tools::getValue('edit_combinationAndStay'))
				Tools::redirectAdmin($this->context->link->getAdminLink('AdminAttributeCarrier').'&id_attribute_carrier_config='.$id_attribute_carrier_config.'&updateattribute_carrier_config&conf=4');
			else	
				Tools::redirectAdmin($this->context->link->getAdminLink('AdminAttributeCarrier').'&conf=4');
		}
	}
	
	public function processGenerate()
	{
		$verif_shops = true ;
		if (Shop::isFeatureActive()) {
			$currect_shops = Shop::getContextListShopID();	
			if(Tools::getValue('implode_currect_shops') != implode("-", $currect_shops))
				$verif_shops = false ;
		}
		if (!is_array(Tools::getValue('options')))
			$this->errors[] = Tools::displayError('Please select at least one attribute.');
		elseif ($verif_shops === false)
			$this->errors[] = Tools::displayError('Error shops selection.');	
		else
		{
			$tab = array_values(Tools::getValue('options'));
			if (count($tab))
			{
				$this->combinations = array_values(self::createCombinations($tab));
//printf('<pre>%s</pre>' , print_r($this->combinations , true));die();
				AttributeCarrierConfig::generateMultipleCombinations($this->combinations, (int)Tools::getValue('default_shipping_price'));	
				
				//Tools::redirectAdmin($this->context->link->getAdminLink('AdminAttributeCarrier').'&conf=4');
			}
			else
				$this->errors[] = Tools::displayError('Unable to initialize these parameters. A combination is missing or an object cannot be loaded.');
		}
	}
		
	protected static function createCombinations($list)
	{
		if (count($list) <= 1)
			return count($list) ? array_map(create_function('$v', 'return (array($v));'), $list[0]) : $list;
		$res = array();
		$first = array_pop($list);
		foreach ($first as $attribute)
		{
			$tab = self::createCombinations($list);
			foreach ($tab as $to_add)
				$res[] = is_array($to_add) ? array_merge($to_add, array($attribute)) : array($to_add, $attribute);
		}
		return $res;
	}
		
	/*public function initContent()
	{
		if (!Combination::isFeatureActive())
		{
			$this->displayWarning($this->l('This feature has been disabled. You can activate it at:').'
				<a href="index.php?tab=AdminPerformance&token='.Tools::getAdminTokenLite('AdminPerformance').'#featuresDetachables">'.
					$this->l('Performance').'</a>');
			return;
		}
		//$this->initGroupTable();
		$js_attributes = AdminAttributeCarrierController::displayAndReturnAttributeJs();
		$attribute_groups = AttributeGroup::getAttributesGroups($this->context->language->id);

		$this->context->smarty->assign(array(
			'generate' => isset($_POST['generate']) && !count($this->errors),
			'combinations_size' => count($this->combinations),
			'url_generator' => self::$currentIndex.'&id_product='.(int)Tools::getValue('id_product').'&attributegenerator&token='.Tools::getValue('token'),
			'attribute_groups' => $attribute_groups,
			'attribute_js' => $js_attributes,
		));
	}
	*/
	protected static function displayAndReturnAttributeJs()
	{
		$attributes = Attribute::getAttributes(Context::getContext()->language->id, true);
		$attribute_js = array();
		foreach ($attributes as $k => $attribute)
			$attribute_js[$attribute['id_attribute_group']][$attribute['id_attribute']] = $attribute['name'];
		echo '
		<script type="text/javascript">
			var attrs = new Array();
			attrs[0] = new Array(0, \'---\');';
		foreach ($attribute_js as $idgrp => $group)
		{
			echo '
				attrs['.$idgrp.'] = new Array(0, \'---\' ';
			foreach ($group as $idattr => $attrname)
				echo ', '.$idattr.', \''.addslashes(($attrname)).'\'';
			echo ');';
		}
		echo '
		</script>';
		return $attribute_js;
	}
}
