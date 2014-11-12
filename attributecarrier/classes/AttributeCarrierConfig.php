<?php
class AttributeCarrierConfig extends ObjectModel
{
	public $price;
	public $date_add;
	public $date_upd;
	
	public static $definition = array(
	'table' => 'attribute_carrier_config',
	'primary' => 'id_attribute_carrier_config',
	'multilang' => false,
		'fields' => array(
			'price' => 						array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice', 'required' => true),
			'date_add' => 					array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat', 'copy_post' => false),
			'date_upd' => 					array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat', 'copy_post' => false),
		),
	);
	
	/*public function __construct()
	{
		Shop::addTableAssociation('attribute_carrier_config', array('type' => 'shop'));
		parent::__construct();
	}*/
	
	
	public function __construct($id_attribute_carrier_config = null, $full = false, $id_lang = null, $id_shop = null, Context $context = null)
	{
		$asso_table = Shop::getAssoTable('attribute_carrier_config');
		if ($asso_table === false)
			Shop::addTableAssociation('attribute_carrier_config', array('type' => 'shop'));
			
		parent::__construct($id_attribute_carrier_config, $id_lang, $id_shop);
		if (!$context)
			$context = Context::getContext();
	}
	
	public static function generateMultipleCombinations($combinations, $default_shipping_price)
	{
		// selection des shops 
		if (Shop::isFeatureActive())
			$currect_shops = Shop::getContextListShopID();	
		
		$attributes_list = array();
		$res = true;	
		$default_on = 1;
		foreach ($combinations as $key => $combination)
		{
			//printf('<pre>%s</pre>' , print_r($combination , true));die();
			$id_attribute_carrier_config = self::combinationAttributeExists($combination, null);
			//
			$obj = new AttributeCarrierConfig($id_attribute_carrier_config);
			$obj->price = $default_shipping_price ;
			if (Shop::isFeatureActive()) {
				$obj->id_shop_list = $currect_shops ;
				// s'il s'agit d'une modif dans tous les shops on va ajouter les enregistrements des shops avant la mise à jour pour en créer tous
				if (Shop::getContext() != Shop::CONTEXT_SHOP && $obj->id) {
					$res_delete_rows_shops = Db::getInstance()->delete(self::$definition['table'].'_shop', '`'.self::$definition['primary'].'`='.(int)$obj->id.' AND id_shop IN ('.implode(', ', $currect_shops).')');
					// insertion des rows
					foreach ($currect_shops as $id_shop) {
						$all_fields = array();
						$all_fields[self::$definition['primary']] = $obj->id;
						$all_fields['price'] = $obj->price;
						$all_fields['id_shop'] = (int)$id_shop;
						$result_insert = Db::getInstance()->insert(self::$definition['table'].'_shop', $all_fields, false);
					}
				}
			}
			if($obj->save()) {
				if(!$id_attribute_carrier_config) {
					$attribute_list = array();
					foreach ($combination as $id_attribute)
						$attribute_list[] = array(
							'id_attribute_carrier_config' => (int)$obj->id,
							'id_attribute' => (int)$id_attribute
						);
					$res &= Db::getInstance()->insert('attribute_carrier_config_combination', $attribute_list);
				}
			}
		}

		return $res;
	}
	
	public static function combinationAttributeExists($attributes_list,Context $context = null, $with_verif_shops = false)
	{
		if (!Combination::isFeatureActive())
			return false;
		if ($context === null)
			$context = Context::getContext();
		$result = Db::getInstance()->executeS(
			'SELECT DISTINCT pac.`id_attribute`, pac.`id_attribute_carrier_config`
			FROM `'._DB_PREFIX_.'attribute_carrier_config` pa
			'.($with_verif_shops ? Shop::addSqlAssociation('attribute_carrier_config', 'pa') : '').'
			LEFT JOIN `'._DB_PREFIX_.'attribute_carrier_config_combination` pac ON (pac.`id_attribute_carrier_config` = pa.`id_attribute_carrier_config`)
			WHERE 1 ');
//printf('<pre>%s</pre>' , print_r($result , true));die();
		/* If something's wrong */
		if (!$result || empty($result))
			return false;
		/* Product attributes simulation */
		$attributes = array();
		foreach ($result as $one_attribute_carrier_config) {
			$key = $one_attribute_carrier_config['id_attribute_carrier_config'] ;
			$attributes[$key][] = $one_attribute_carrier_config['id_attribute'];
		}
		/* Checking product's attribute existence */
		foreach ($attributes as $key => $one_attribute_carrier_config)
			if (count($one_attribute_carrier_config) == count($attributes_list))
			{
				$diff = false;
				for ($i = 0; $diff == false && isset($one_attribute_carrier_config[$i]); $i++)
					if (!in_array($one_attribute_carrier_config[$i], $attributes_list))
						$diff = true;
				
				if (!$diff)
					return $key ;
			}
		return false ;
	}
	
	public static function getListCombination($id_attribute_carrier_config = NULL, Context $context = null)
	{
		if ($context === null)
			$context = Context::getContext();
			
		$SqlCombinations =  'SELECT DISTINCT pac.`id_attribute`, attl.`name` , pac.`id_attribute_carrier_config`, pas.`id_shop`, pas.`price`, att_gl.`public_name`
			FROM `'._DB_PREFIX_.'attribute_carrier_config` pa 
			'.Shop::addSqlAssociation('pas.attribute_carrier_config', 'pa').'
			LEFT JOIN `'._DB_PREFIX_.'attribute_carrier_config_combination` pac ON (pac.`id_attribute_carrier_config` = pa.`id_attribute_carrier_config`) 
			
			LEFT JOIN `'._DB_PREFIX_.'attribute` att 
				ON (att.`id_attribute` = pac.`id_attribute`)
			'.Shop::addSqlAssociation('attribute', 'att').'
			LEFT JOIN `'._DB_PREFIX_.'attribute_lang` attl
				ON (att.`id_attribute` = attl.`id_attribute` AND attl.`id_lang` = '.(int)$context->language->id.')
				
			LEFT JOIN `'._DB_PREFIX_.'attribute_group` att_g 
				ON (att_g.`id_attribute_group` = att.`id_attribute_group`)
			'.Shop::addSqlAssociation('attribute_group', 'att_g').'
			LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` att_gl
				ON (att_g.`id_attribute_group` = att_gl.`id_attribute_group` AND att_gl.`id_lang` = '.(int)$context->language->id.')	
				
			WHERE 1 ';
			if(!is_null($id_attribute_carrier_config))
				$SqlCombinations .= " AND pa.id_attribute_carrier_config = '".(int)$id_attribute_carrier_config."' " ;
			
			$SqlCombinations .= ' ORDER BY pas.`id_shop` ASC, pac.`id_attribute_carrier_config` ASC, attl.`name` ASC ' ;
			//print "<pre>" ;
			//print  ($SqlCombinations) ;
			//printf('<hr><pre>%s</pre><hr>' , print_r($SqlCombinations , true));die();//betadev
		return  Db::getInstance()->executeS($SqlCombinations);
	}
	
	public function delete()
	{
		$return = parent::delete();
		if ($return) {
			Db::getInstance()->execute('
				DELETE FROM `'._DB_PREFIX_.'attribute_carrier_config_combination`
				WHERE `id_attribute_carrier_config`	= '.(int)$this->id.' ') ;	
		}
		return $return ;
	}
}
