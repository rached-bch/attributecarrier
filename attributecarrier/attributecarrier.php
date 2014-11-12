<?php
if (!defined('_PS_VERSION_'))
	exit;

include_once(dirname(__FILE__) . '/classes/AttributeCarrierConfig.php');

class AttributeCarrier extends CarrierModule
{
	private $_fieldsList = array();
	
	/*
	** Construct Method
	**
	*/
	public function __construct()
	{
		$this->name = 'attributecarrier';
		$this->tab = 'shipping_logistics';
		$this->version = '2.0';
		$this->author = 'Pixels';
		
		$this->bootstrap = true;
		$this->ps_versions_compliancy = array('min' => '1.6.0');
		parent::__construct ();

		$this->displayName = $this->l('Attributes Carrier');
		$this->description = $this->l('Configure multiple shipping fees depending on attributes');
		
		$this->loadingVar();
	}
	
	public function loadingVar()
	{
		// Loading Fields List
		$this->_fieldsList = array(
			'ATT_CARRIER_ID' => '',
			'ATT_CARRIER_IDTAB' => '',
			'ATT_CARRIER_DEFAULT_SHIPPING_PR' => 0,
		);
	}
	
	public function install()
	{
		// Install SQL
		include(dirname(__FILE__).'/sql-install.php');
		foreach ($sql as $s)
			if (!Db::getInstance()->Execute($s))
				return false;
				
		// Install Module
		if (!parent::install() or 
			!$this->registerHook('orderDetailDisplayed') or 
			!$this->registerHook('adminOrder') or 
			!$this->registerHook('extraCarrier') or 
			!$this->registerHook('newOrder') or
			!$this->registerHook('actionAttributeDelete') or
			!$this->registerHook('actionAttributeSave') or
			!$this->registerHook('actionAttributeGroupDelete'))
			return false;
		if (file_exists('../modules/'.$this->name.'/configShipping.xml'))
		{
			$serviceList = simplexml_load_file('../modules/'.$this->name.'/configShipping.xml');
			if ($serviceList == false)
				return false;
		}
		
		$menuadmin = 'Attributes Carrier' ;
		$menuadminfr = 'Attributes Carrier' ;
		
		foreach($serviceList as $k => $v)
		{
			$carrierConfig = array(
				'name' => $this->l($v->name),
				'id_tax_rules_group' => 0,
				'deleted' => 0,
				'shipping_handling' => false,
				'range_behavior' => 0,
				'delay' => array('fr' => $v->descriptionfr, 'en' => $v->description),
				'id_zone' => 1,
				'is_module' => true,
				'shipping_external' => true,
				'external_module_name' => $this->name,
				'need_range' => true,
				'active' => true
			);
			
			$menuadmin = $v->menuadmin ;
			$menuadminfr = $v->menuadminfr ;
			
			$id_carrier = $this->installExternalCarrier($carrierConfig);
			Configuration::updateValue('ATT_CARRIER_ID', (int)($id_carrier));
			Configuration::updateValue('ATT_CARRIER_DEFAULT_SHIPPING_PR', $this->_fieldsList['ATT_CARRIER_DEFAULT_SHIPPING_PR']);
		}
		
		/* Before creating a new tab  we need to remove any existing tab */
		if ($id_tab = Tab::getIdFromClassName('AdminAttributeCarrier')) {
			$tab = new Tab((int)$id_tab);
			$tab->delete();
		}
		
		/* If the "AdminAttributeCarrier" tab does not exist yet, create it */
		if (!$id_tab = Tab::getIdFromClassName('AdminAttributeCarrier'))
		{
			$tab = new Tab();
			$tab->active = 1;
			$tab->class_name = 'AdminAttributeCarrier';
			$tab->module = $this->name;
			$tab->id_parent = (int)Tab::getIdFromClassName('AdminParentShipping');
			$tab->name = array();
			foreach (Language::getLanguages(false) as $lang) {
				if($lang['iso_code'] == "fr")
					$tab->name[(int)$lang['id_lang']] = $menuadminfr;
				else
					$tab->name[(int)$lang['id_lang']] = $menuadmin;
			}
			$tab->save();
		}
		else
			$tab = new Tab((int)$id_tab);
		
		/* Update the tab id in database or exit */
		if (Validate::isLoadedObject($tab))
			Configuration::updateValue('ATT_CARRIER_IDTAB', (int)$tab->id);

		return true;
	}
	
	public function getContent()
	{
		Tools::redirectAdmin($this->context->link->getAdminLink('AdminAttributeCarrier'));
	}
	
	public function uninstall()
	{
		// Uninstall Carriers
		// 1.5 id_shop !!
		Db::getInstance()->autoExecute(_DB_PREFIX_.'carrier', array('deleted' => 1), 'UPDATE', '`external_module_name` = \''.$this->name.'\'');
		// Uninstall Config
		foreach ($this->_fieldsList as $keyConfiguration => $name)
		{
			Configuration::deleteByName($keyConfiguration);
		}
		
		// Uninstall SQL
		include(dirname(__FILE__).'/sql-uninstall.php');
		foreach ($sql as $s)
			if (!Db::getInstance()->Execute($s))
				return false;
				
		// MENU
		if ($id_tab = Tab::getIdFromClassName('AdminAttributeCarrier')) {
			$tab = new Tab((int)$id_tab);
			$tab->delete();
		}
		
		// Uninstall Module
		if (!parent::uninstall() or
			!$this->unregisterHook('actionAttributeDelete') or
			!$this->unregisterHook('actionAttributeSave') or
			!$this->unregisterHook('actionAttributeGroupDelete'))
			return false;
		return true;
	}
	
	public static function installExternalCarrier($config)
	{
		$carrier = new Carrier();
		$carrier->name = $config['name'];
		$carrier->id_tax_rules_group = $config['id_tax_rules_group'];
		$carrier->id_zone = $config['id_zone'];
		$carrier->active = $config['active'];
		$carrier->deleted = $config['deleted'];
		$carrier->delay = $config['delay'];
		$carrier->shipping_handling = $config['shipping_handling'];
		$carrier->range_behavior = $config['range_behavior'];
		$carrier->is_module = $config['is_module'];
		$carrier->shipping_external = $config['shipping_external'];
		$carrier->external_module_name = $config['external_module_name'];
		$carrier->need_range = $config['need_range'];
		$carrier->active = $config['active'];

		$languages = Language::getLanguages(true);
		foreach ($languages as $language)
		{
			if (($language['iso_code'] == 'fr') || ($language['iso_code'] == 'en'))
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
			if ($language['iso_code'] == Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) && isset($config['delay'][$language['iso_code']]))
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
			elseif ($language['iso_code'] == Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) && isset($config['delay']['en']))
				$carrier->delay[(int)$language['id_lang']] = $config['delay']['en'];
		}

		if ($carrier->add())
		{
			$groups = Group::getGroups(true);
			foreach ($groups as $group)
				Db::getInstance()->autoExecute(_DB_PREFIX_.'carrier_group', array('id_carrier' => (int)($carrier->id), 'id_group' => (int)($group['id_group'])), 'INSERT');

			$rangePrice = new RangePrice();
			$rangePrice->id_carrier = $carrier->id;
			$rangePrice->delimiter1 = '0';
			$rangePrice->delimiter2 = '10000';
			$rangePrice->add();

			$rangeWeight = new RangeWeight();
			$rangeWeight->id_carrier = $carrier->id;
			$rangeWeight->delimiter1 = '0';
			$rangeWeight->delimiter2 = '10000';
			$rangeWeight->add();

			Db::getInstance()->autoExecute(_DB_PREFIX_.'carrier_zone', array('id_carrier' => (int)($carrier->id), 'id_zone' => (int)($carrier->id_zone)), 'INSERT');
			Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_.'delivery', array('id_carrier' => (int)($carrier->id), 'id_range_price' => (int)($rangePrice->id), 'id_range_weight' => null, 'id_zone' => (int)($carrier->id_zone), 'price' => '0'), 'INSERT');
			Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_.'delivery', array('id_carrier' => (int)($carrier->id), 'id_range_price' => null, 'id_range_weight' => (int)($rangeWeight->id), 'id_zone' => (int)($carrier->id_zone), 'price' => '0'), 'INSERT');

			// Copy Logo
			if (!copy(dirname(__FILE__).'/carrier.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg'))
				return false;

			// Return ID Carrier
			return (int)($carrier->id);
		}

		return false;
	}
	
	// hook appelé lors de la suppresion d'un groupe attribus
	public function hookActionAttributeGroupDelete($params)
	{
		if(isset($params['id_attribute_group'])) {
			$id_attribute_group = (int) $params['id_attribute_group'] ;	
			if(!empty($id_attribute_group)) {
				$SqlVerif = 'SELECT DISTINCT cmb.`id_attribute_carrier_config`
				FROM `'._DB_PREFIX_.'attribute_carrier_config_combination` cmb 
				LEFT OUTER JOIN `ps_attribute` att ON (att.`id_attribute` = cmb.`id_attribute`)
				LEFT OUTER JOIN `ps_attribute_group` attg ON (attg.`id_attribute_group` = att.`id_attribute_group`)
				WHERE att.`id_attribute` IS NULL OR attg.`id_attribute_group` IS NULL ';
				$ResVerif = Db::getInstance()->executeS($SqlVerif);
				if($ResVerif) {
					foreach($ResVerif as $RowCombination) {
						$id_attribute_carrier_config = (int) $RowCombination['id_attribute_carrier_config'] ;
						$ObjectAttributeCarrierConfig = new AttributeCarrierConfig($id_attribute_carrier_config);
						$ObjectAttributeCarrierConfig->delete() ;
					}
				}
			}
		}
	}
	
	// hook appelé lors de la modification d'un attribut
	// vérifie si le groupe de cet attribut a changé, si oui on va effacer la ligne de trnsport de cette déclinaison qui 
	// a un autre attribut ayant le meme groupe
	public function hookActionAttributeSave($params)
	{
		if(isset($params['id_attribute'])) {
			$id_attribute = (int) $params['id_attribute'] ;	
			$ObjectAttribute = new Attribute($id_attribute);
			if($ObjectAttribute->id && $ObjectAttribute->id_attribute_group) {
				$SqlVerif = 'SELECT attg.id_attribute_group , cmb.`id_attribute_carrier_config`, COUNT(*) as nbr_occ
				FROM `'._DB_PREFIX_.'attribute_carrier_config_combination` cmb 
				INNER JOIN `'._DB_PREFIX_.'attribute` att ON (att.`id_attribute` = cmb.`id_attribute`)
				INNER JOIN `'._DB_PREFIX_.'attribute_group` attg ON (attg.`id_attribute_group` = att.`id_attribute_group`)
				WHERE attg.id_attribute_group = '.(int)$ObjectAttribute->id_attribute_group.' AND 
				cmb.`id_attribute_carrier_config` IN (SELECT cmb2.`id_attribute_carrier_config` 
				FROM `'._DB_PREFIX_.'attribute_carrier_config_combination` cmb2 
				WHERE cmb2.`id_attribute_carrier_config` = cmb.`id_attribute_carrier_config` 
				AND cmb2.`id_attribute` = '.(int)$id_attribute.')
				GROUP BY attg.id_attribute_group , cmb.`id_attribute_carrier_config`';
				$ResVerif = Db::getInstance()->executeS($SqlVerif);
				if($ResVerif) {
					foreach($ResVerif as $RowCombination) {
						if($RowCombination['nbr_occ'] > 1) { // incohérence => on va supprimer la ligne
							$id_attribute_carrier_config = (int) $RowCombination['id_attribute_carrier_config'] ;
							$ObjectAttributeCarrierConfig = new AttributeCarrierConfig($id_attribute_carrier_config);
							$ObjectAttributeCarrierConfig->delete() ;
							break;
						}
					}
				}
			}
		}
	}
	
	// hook appelé lors de la suppresion d'un attribu
	public function hookActionAttributeDelete($params)
	{
		if(isset($params['id_attribute'])) {
			$id_attribute = (int) $params['id_attribute'] ;	
			if(!empty($id_attribute)) {
				$SqlVerif = 'SELECT DISTINCT cmb.`id_attribute_carrier_config`
				FROM `'._DB_PREFIX_.'attribute_carrier_config_combination` cmb 
				WHERE cmb.id_attribute = '.(int)$id_attribute.' ';
				$ResVerif = Db::getInstance()->executeS($SqlVerif);
				if($ResVerif) {
					foreach($ResVerif as $RowCombination) {
						$id_attribute_carrier_config = (int) $RowCombination['id_attribute_carrier_config'] ;
						$ObjectAttributeCarrierConfig = new AttributeCarrierConfig($id_attribute_carrier_config);
						$ObjectAttributeCarrierConfig->delete() ;
					}
				}
			}
		}
	}
	
	// hook appelé lors de la confirmation d'une commande passée en Front
	public function hooknewOrder($params)
	{
		
	}
	// affiche un texte supplémentaire en dessous de la liste des transporteur en dessous du bloc "Méthodes de livraison"  dans l'écran de la commande en Front
	public function hookextraCarrier($params)
	{
		return "" ;
	}
	// texte affiché en dessous du bloc Informations client lors de l'affichage des détails de la commande en Admin
	public function hookadminOrder($params)
	{
		return "" ;
	}
	// texte affiché en dessus du panier affiché dans le détails des commandes déjà passées pour une utilisateur inscrit 
	public function hookorderDetailDisplayed($params)
	{
		return "" ;
	}
	
	public function getOrderShippingCost($params, $shipping_cost)
	{ 
		if (!$this->active)
			return false;
		
		$product = $params->getProducts();
		//printf('<hr><pre>%s</pre><hr>' , print_r($product , true));//betadev
		if(count($product)) {
			$somme_shipping_cost = 0;
			foreach($product as $row_product) {
				$cart_quantity = (isset($row_product['cart_quantity'])) ? (int) $row_product['cart_quantity'] : 1 ;
				$cart_quantity = ($cart_quantity <= 0) ? 1 : $cart_quantity ;
				for($i = 0 ; $i < $cart_quantity ; $i++) {
					$product_shipping_cost	= $this->getProductShippingCostFromAttributes($row_product['id_product'], $row_product['id_product_attribute']);
					$somme_shipping_cost += $product_shipping_cost ;
					// on ajoute le frais de transp des déclinaisons au frais total déja calculé (selon le poids)
					$somme_shipping_cost += $shipping_cost ;
				}
			}
			$shipping_cost = $somme_shipping_cost ;
		}
		return $shipping_cost; 
	}
	public function getOrderShippingCostExternal($params){ return $this->getOrderShippingCost($params, null); }
	
	/*
		Fournit le frais de transport pour un produit selon son déclinasion
		id_product_attribute => l'id de la déclinasion
	*/
	public function getProductShippingCostFromAttributes($id_product, $id_product_attribute) {
		$product_shipping_cost = 0 ;
		
		$context = Context::getContext();
		
		$object_product = new Product($id_product);	
		if($object_product->id) {
			$AttributeCombinations = $object_product->getAttributeCombinations($context->language->id);	
			//printf('<hr><pre>%s</pre><hr>' , print_r($AttributeCombinations , true));die();//betadev
			if(!empty($AttributeCombinations)) {
				$list_options = array();
				foreach($AttributeCombinations as $key => $one_attribute) {
					if($one_attribute['id_product_attribute'] == $id_product_attribute) {
						if(isset($one_attribute['id_attribute'])) {
							if(!empty($one_attribute['id_attribute'])) {
								$id_attribute = $one_attribute['id_attribute'] ;
								$list_options[] = $id_attribute ;
							}
						}
					}
				}
				// on va préparer la liste des combinations pour vérifier le shipping configuré
				if(count($list_options) > 0) {
					$id_attribute_carrier_config = AttributeCarrierConfig::combinationAttributeExists($list_options, $context, true);
					if($id_attribute_carrier_config) {
						$ObjectAttributeCarrierConfig = new AttributeCarrierConfig($id_attribute_carrier_config);
						$product_shipping_cost = (float) $ObjectAttributeCarrierConfig->price ;	
					}
				}
			}
		}
		return $product_shipping_cost ;
	}
}