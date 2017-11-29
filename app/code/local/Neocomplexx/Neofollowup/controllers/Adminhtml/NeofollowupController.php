<?php
/**
 * Atwix
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.

 * @category    Atwix Mod
 * @package     Atwix_Tweaks
 * @author      Atwix Core Team
 * @copyright   Copyright (c) 2012 Atwix (http://www.atwix.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Neocomplexx_Neofollowup_Adminhtml_NeofollowupController extends Mage_Adminhtml_Controller_Action
{

    const CONFIG_HELPER_NAME = "neofollowup";//equivalente a neofollowup/Data
    const DISCOUNT_PERCENTAGE = 10;//usado en la regla

    /**
     * Return some checking result
     *
     * @return void
     */
    public function generateRuleAction()
    {
        $result = $this->generarReglaDescuento();
        //$result tiene el id de la regla generada
        Mage::app()->getResponse()->setBody($result);
    }


    public function generarReglaDescuento(){


        $name = "neo follow up rule"; // name of Shopping Cart Price Rule
        $websiteId = Mage::app()->getWebsite()->getId(); //asi se crean las otras
        $actionType = 'by_percent'; // discount by percentage (other options are: by_fixed, cart_fixed, buy_x_get_y)
        $discount = self::DISCOUNT_PERCENTAGE; // percentage discount
        $sku = Mage::helper(self::CONFIG_HELPER_NAME)->getAllSKUs();


        $shoppingCartPriceRule = Mage::getModel('salesrule/rule');

        $shoppingCartPriceRule
        ->setName($name)
        ->setDescription(NULL)
        ->setFromDate(NULL)
        ->setToDate(NULL)
        ->setUsesPerCustomer('0')
        ->setIsActive('1')
        ->setConditionsSerialized('')//condiciones y acciones se ponen en vacias. Hay que agregarlas por separado
        ->setActionsSerialized('')
        ->setStopRulesProcessing('0')
        ->setIsAdvanced('1')
        ->setProductIds(NULL)
        ->setSortOrder('0')
        ->setSimpleAction($actionType)
        ->setDiscountAmount($discount)
        ->setDiscountQty(NULL)
        ->setDiscountStep('0')
        ->setSimpleFreeShipping('0')
        ->setApplyToShipping('0')
        ->setTimesUsed('1')
        ->setIsRss('1')
        ->setCouponType('2')
        ->setUseAutoGeneration('1')
        ->setUsesPerCoupon('1')
        ->setCustomerGroupIds(array('0','1','2','3','4','5','6','7','8','9','10','11',)) //todos los grupos actuales. General es el 1
        ->setWebsiteIds(array('5',))
        ->setCouponCode(NULL);

        //agrego una condicion en conditions para decir que la regla solo se aplique si en la carta se cumple que hay uno de estos productos (no seria tan necesario)
        
        $found = Mage::getModel('salesrule/rule_condition_product_found')
            ->setType('salesrule/rule_condition_product_found') //"product attribute combination" que despues dice como "if an item is ..."
            ->setValue(1)           // 1 == FOUND, 0 == NOT FOUND
            ->setAggregator('all'); // match ALL conditions. Podria ser ANY
        $shoppingCartPriceRule->getConditions()->addCondition($found);

        $skuCond = Mage::getModel('salesrule/rule_condition_product')
            ->setType('salesrule/rule_condition_product')
            ->setAttribute('sku') 
            ->setOperator('()')//si quiero uno solo usar '=='. El '()' significa is one of. Lo saco de inspeccionar la lista desplegable en el panel de admin
            ->setValue($sku);

        $found->addCondition($skuCond);   //se agrega a found. Es decir: "Si se encuentra un item que cumpla con todas las condiciones: Que el sku sea uno de la lista"



        //agrego la condicion en la parte de actions para decir que la regla se aplique solo a esos productos
        $skuCond = Mage::getModel('salesrule/rule_condition_product')
        ->setType('salesrule/rule_condition_product')
        ->setAttribute('sku')
        ->setOperator('()')//si quiero uno solo usar '=='. El '()' significa is one of. Lo saco de inspeccionar la lista desplegable en el panel de admin
        ->setValue($sku);
        $shoppingCartPriceRule->getActions()->addCondition($skuCond);  


        $shoppingCartPriceRule->save();

        $ruleId = $shoppingCartPriceRule->getId();

        Mage::helper(self::CONFIG_HELPER_NAME)->log("Se creo la regla ".$ruleId);

        //guardo el id en la configuracion para que se use de ahora en mas esta regla
        Mage::helper(self::CONFIG_HELPER_NAME)->setShoppingCartRuleId($ruleId);

        return $ruleId;

    }
}