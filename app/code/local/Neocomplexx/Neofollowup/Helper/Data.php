<?php
/**
 *
 * @author: Neocomplexx
 *
 */
class Neocomplexx_Neofollowup_Helper_Data extends Mage_Core_Helper_Abstract
{

	const CONFIG_DAYS_PER_PRODUCT = "neocomplexx_neofollowup/general/product_days";
	const CONFIG_ENABLED = "neocomplexx_neofollowup/general/active";
	const CONFIG_DEBUG = "neocomplexx_neofollowup/general/debug";
	const CONFIG_TEMPLATE_ID = "neocomplexx_neofollowup/general/custom_template";
	const CONFIG_RULE_ID = "neocomplexx_neofollowup/general/ruleid";
    const CONFIG_PRODUCT_COLUMN = "product";
	const CONFIG_DAYS_COLUMN = "days";	

	const LOG_FILE_NAME = "Neocomplexx_neofollowup.log";
	
    /**
	 * Check if Neocomplexx_Neofollow module is enabled
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return  (int)Mage::getStoreConfig(self::CONFIG_ENABLED);
	}

	/**
	 * Check if Neocomplexx_Neofollow debug is enabled
	 *
	 * @return bool
	 */
	 public function debug()
	 {
		 return  (int)Mage::getStoreConfig(self::CONFIG_DEBUG);
	 }


	/**
	 * Logging facility
	 *
	 * @param mixed $data Message to save to file
	 * @param string $filename log filename, default is <Neocomplexx_Neofollowup.log>
	 */
	public function log($data, $filename = self::LOG_FILE_NAME)
	{
		if($this->debug())
			Mage::log($data, Zend_Log::INFO, $filename);
	}

	/**
	 * Get email template id
	 *
	 * @return int
	 */
	 public function getTemplateId()
	 {
		 return  (int) Mage::getStoreConfig(self::CONFIG_TEMPLATE_ID);
	 }

	
	 /**
	 * Get shopping cart price rule id 
	 *
	 * @return int
	 */
	 public function getShoppingCartRuleId()
	 {
		 return  (int) Mage::getStoreConfig(self::CONFIG_RULE_ID);
	 }

	/**
	 * Set shopping cart price rule id 
	 *
	 * @param int $ruleId
	 */
	 public function setShoppingCartRuleId($ruleId)
	 {
		Mage::getModel('core/config')->saveConfig(self::CONFIG_RULE_ID, $ruleId); 
		Mage::getModel('core/config')->cleanCache(); //clean the cache to delete previous value
	 }




	/**
	 * get the follow up days and products order by days
	 *
	 * @return two dimension array with the days as keys and an array of sku products as values
	 */
	 public function getDaysPerProduct()
	 {
		$daysArray = array();
		/*order the product configuration by day. $daysArray will be a two dimension array with the following format 
			[days] => [productA, productB, ...],
			[15] => [product1, product2, ...], //15 days
			[20] => [product3, product4, ...], //20 days
			...
		*/


		$daysPerProduct = Mage::getStoreConfig(self::CONFIG_DAYS_PER_PRODUCT);
        if ($daysPerProduct) {//check if it has any row
            $daysPerProduct = unserialize($daysPerProduct);
            if (is_array($daysPerProduct)) {
                foreach($daysPerProduct as $daysPerProductRow) {
                    $product = $daysPerProductRow[self::CONFIG_PRODUCT_COLUMN];
					$days = $daysPerProductRow[self::CONFIG_DAYS_COLUMN];


					if (array_key_exists($days, $daysArray))						{
						//that key already has a value. Add the new one to the existing array
						$daysArray[$days][]= $product;
						}
					else {
						//there is no product with that amount of days yet. Create the array
						$daysArray[$days] = array();
						$daysArray[$days][]= $product;

					}
                }
            } else {
                // handle unserializing errors here
            }
		}
		

		return $daysArray;
	 }



	/**
	 * Return a String with all products skus set in the module configuration
	 *
	 * @return string
	 */
	 public function getAllSKUs()
	 {
		$skuString = ""; 


		$daysPerProduct = Mage::getStoreConfig(self::CONFIG_DAYS_PER_PRODUCT);
        if ($daysPerProduct) {//check if it has any row
            $daysPerProduct = unserialize($daysPerProduct);
            if (is_array($daysPerProduct)) {
                foreach($daysPerProduct as $daysPerProductRow) {
					$product = $daysPerProductRow[self::CONFIG_PRODUCT_COLUMN];
					
					$skuString = $skuString.', '. trim($product);
				}
				$skuString = substr($skuString,2);//remove the initial ", " 

            } else {
                // handle unserializing errors here
            }
		}
		

		return $skuString;
	 }



}