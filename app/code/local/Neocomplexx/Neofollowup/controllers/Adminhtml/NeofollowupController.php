<?php


class Neocomplexx_Neofollowup_Adminhtml_NeofollowupController extends Mage_Adminhtml_Controller_Action
{

    const CONFIG_HELPER_NAME = "neofollowup";//equivalent to neofollowup/Data
    const DISCOUNT_PERCENTAGE = 10; //used in the generated rule

    /**
     * Return some checking result
     *
     * @return void
     */
    public function generateRuleAction()
    {
        $result = $this->generateDiscountRule();
        //$result is the generated rule id
        Mage::app()->getResponse()->setBody($result);
    }

    /**
     * Generate the Shopping Cart Price Rule
     *
     * @return int generated Rule Id
     */
    public function generateDiscountRule(){


        $name = "neo follow up rule"; // name of Shopping Cart Price Rule       
        $actionType = 'by_percent'; // discount by percentage (other options are: by_fixed, cart_fixed, buy_x_get_y)
        $discount = self::DISCOUNT_PERCENTAGE; // percentage discount
        $sku = Mage::helper(self::CONFIG_HELPER_NAME)->getAllSKUs();

        $groups = Mage::getModel('customer/group')->getCollection();
        
        //apply it to every customer groups
        $customerGroupIds = array();
        foreach ($groups as $Group) {
            $customerGroupIds[] = $Group->getCustomerGroupId();        
        }

        //apply it to all the websites
        $websites = Mage::app()->getWebsites(); 
        $websiteIds = array();
        foreach ($websites as $id => $website) {
            $websiteIds[] = $id;            
        }



        $shoppingCartPriceRule = Mage::getModel('salesrule/rule');

        $shoppingCartPriceRule
        ->setName($name)
        ->setDescription(NULL)
        ->setFromDate(NULL)
        ->setToDate(NULL)
        ->setUsesPerCustomer('0')
        ->setIsActive('1')
        ->setConditionsSerialized('')//leave the conditions and actions empty. We need to add them separately
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
        ->setCustomerGroupIds($customerGroupIds) //Every customer group. General is number 1
        ->setWebsiteIds($websiteIds)
        ->setCouponCode(NULL);


        //add a condition to rule conditions: Only apply the rule if the shopping cart has uno of these products (here we can avoid this step)        
        $found = Mage::getModel('salesrule/rule_condition_product_found')
            ->setType('salesrule/rule_condition_product_found') //"product attribute combination" which says "if an item is ..."
            ->setValue(1)           // 1 == FOUND, 0 == NOT FOUND
            ->setAggregator('all'); // match ALL conditions. Could be ANY
        $shoppingCartPriceRule->getConditions()->addCondition($found);

        $skuCond = Mage::getModel('salesrule/rule_condition_product')
            ->setType('salesrule/rule_condition_product')
            ->setAttribute('sku') 
            ->setOperator('()')//If is only one product, use '=='. '()' means "is one of". See other options in loadOperatorOptions() in app/code/core/Mage/SalesRule/Model/Rule/Condition/Product/Subselect.php or inspect  the drop-down list in the admin panel, when creating the shopping rule manually
            ->setValue($sku);

        $found->addCondition($skuCond);   //add condition to found. It means: "If an item is found that meets all the conditions: The sku is one of the list"


        //add a condition to actions section. Only apply the rule discount to these products
        $skuCond = Mage::getModel('salesrule/rule_condition_product')
        ->setType('salesrule/rule_condition_product')
        ->setAttribute('sku')
        ->setOperator('()')//If is only one product, use '=='. '()' means "is one of". See other options in loadOperatorOptions() in app/code/core/Mage/SalesRule/Model/Rule/Condition/Product/Subselect.php or inspect  the drop-down list in the admin panel, when creating the shopping rule manually
        ->setValue($sku);
        $shoppingCartPriceRule->getActions()->addCondition($skuCond);  


        $shoppingCartPriceRule->save();

        $ruleId = $shoppingCartPriceRule->getId();

        Mage::helper(self::CONFIG_HELPER_NAME)->log("Se creo la regla ".$ruleId);

        //Save the rule id in the configuration to use this rule from now on
        Mage::helper(self::CONFIG_HELPER_NAME)->setShoppingCartRuleId($ruleId);

        return $ruleId;

    }
}
