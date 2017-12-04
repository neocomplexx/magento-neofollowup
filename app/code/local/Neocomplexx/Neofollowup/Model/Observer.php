<?php

class Neocomplexx_Neofollowup_Model_Observer
{
    const CONFIG_TEMPLATE_ID = "neocomplexx_neofollowup/general/custom_template";
    const CONFIG_HELPER_NAME = "neofollowup";//equivalent to neofollowup/Data

    
    public function sendEmail($customerName, $recepientEmail, $productName, $productId, $couponCode)
    { 
       //initialize the email variables
       $templateId = Mage::helper(self::CONFIG_HELPER_NAME)->getTemplateId();   //a number id. Eg 48
       $senderName = Mage::getStoreConfig('trans_email/ident_support/name');    //Get Sender Name from Store Email Addresses
       $senderEmail = Mage::getStoreConfig('trans_email/ident_support/email');  //Get Sender Email Id from Store Email Addresses
       $sender = array('name' => $senderName,
                   'email' => $senderEmail);
       
       // Set recepient information
       $recepientName = $customerName;     
       
       // Get Store ID     
       $storeId = Mage::app()->getStore()->getId();

       //search for product info
       $product = Mage::getModel('catalog/product')->load($productId);  //some ID    
       
       $productMediaConfig = Mage::getModel('catalog/product_media_config');
       $baseImageUrl  = $productMediaConfig->getMediaUrl($product->getImage());


        //Create an array of variables to assign to template
        $emailTemplateVariables = array();
        $emailTemplateVariables['customer_name'] = $customerName; 
        $emailTemplateVariables['product_name'] = $productName;
        $emailTemplateVariables['coupon_code'] = $couponCode;
        $emailTemplateVariables['image_url'] = $baseImageUrl;
        $emailTemplateVariables['shopping_link'] = $product->getProductUrl();
        

       //Send Transactional Email
        Mage::getModel('core/email_template')
        ->sendTransactional($templateId, $sender, $recepientEmail, $recepientName, $emailTemplateVariables, $storeId);

        //if debug is enabled, log it
        Mage::helper(self::CONFIG_HELPER_NAME)->log("email send to ".$customerName." at ".$recepientEmail);

    }



    public function generateCoupon(){

        $generator = Mage::getModel('salesrule/coupon_massgenerator');
        $ruleId = Mage::helper(self::CONFIG_HELPER_NAME)->getShoppingCartRuleId();
        $data = array(
            'max_probability'   => .25,
            'max_attempts'      => 10,
            'uses_per_customer' => 0,
            'uses_per_coupon'   => 1,
            'qty'               => 1, //number of coupons to generate
            'length'            => 14, //length of coupon string
            //'to_date'           =>  $todaysdateis, //ending date of generated promo
            /**
             * Possible values include:
             * Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_ALPHANUMERIC
             * Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_ALPHABETICAL
             * Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_NUMERIC
             */
            'format'          => Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_ALPHANUMERIC,
            'rule_id'         => $ruleId //the id of the rule you will use
        );


        $generator->validateData($data);
        $generator->setData($data);
        $generator->generatePool();


        $salesRule = Mage::getModel('salesrule/rule')->load($data['rule_id']);

        //get the last generated code
        $couponCode = Mage::getResourceModel('salesrule/coupon_collection')
        ->addRuleToFilter($salesRule)
        ->addGeneratedCouponsFilter()
        ->getLastItem()
        ->getData('code');

        Mage::helper(self::CONFIG_HELPER_NAME)->log("A coupon for the shopping rule ".$ruleId."was generated, with code ".$couponCode);

        return $couponCode;

    }



    
    //Is the function that the cron executes
    public function sendFollowUpEmails()
    { 
        $configHelper = Mage::helper(self::CONFIG_HELPER_NAME);
        $isEnable = $configHelper->isEnabled();

        if ($isEnable)   {

            $followUpDays = $configHelper->getDaysPerProduct();
            /*$followUpDays will be a two dimension array with the following format 
                [15] => [product1, product2, ...], //15 days
                [20] => [product3, product4, ...], //20 days
                ...
            */    

            foreach ($followUpDays as $days => $productArray)
                {   //For each of the days set in the module configuration, we need to search the orders of that day 
                    //We need to go X days ago and have the following day to that one

                    $stringDay= '-'.$days.' days';
                    $searchDay = date('Y-m-d', strtotime($stringDay)); //$days days ago
                
                    $nextDay = date('Y-m-d', strtotime($searchDay .' +1 day'));


                    //get the orders of that day
                    $orderCollection = Mage::getModel('sales/order')->getCollection()    
                    ->addAttributeToFilter('created_at', array('from' => $searchDay, 'to' => $nextDay))
                    ->addAttributeToFilter('state', 'complete')
                    ->addAttributeToFilter('status', 'complete');
            
                    foreach ($orderCollection as $order) {
                        $items=array();
            
                        foreach ($order->getAllVisibleItems() as $item) {
                        $items[] = array(
                            'orderId'       => $order->getIncrementId(),
                            'name'          => $item->getName(),
                            'sku'           => $item->getSku(),
                            'Price'         => $item->getPrice(),
                            'Ordered Qty'   => $item->getQtyOrdered(), 
                            'id'            => $item->getProductId(),                            
                
                        );}                     


                        $customerName = $order->getCustomerFirstname();
                        $recepientEmail = $order->getCustomerEmail();


                        //check if any of the items bought are in $productArray. If any, use sendEmail(...)
                        foreach ($productArray as $productSku) {
                            
                            //for each product set in the configuration, search if it is in the order
                            foreach($items as $item) {
                                if ($productSku == $item['sku']) {
                                    $productId  = $item['id'];
                                    $productName = $item['name'];
                                    $couponCode = $this->generateCoupon();
                                    $this->sendEmail($customerName, $recepientEmail,$productName,$productId, $couponCode);
                                    break 2; //I found a product match. Send an email to this customer and dont keep searching. Leave the 2 foreach
                                }
                            }


                        }



                    }//foreach closure of the orders of the days

                    


                }// foreach closure foreach of the days set in the module configuration on admin panel


       
            }//if enable closure


    }


}