<?php

class Neocomplexx_Neofollowup_Model_Observer
{




    const CONFIG_TEMPLATE_ID = "neocomplexx_neofollowup/general/custom_template";
    const CONFIG_HELPER_NAME = "neofollowup";//equivalente a neofollowup/Data

    
    public function sendEmail($customerName, $recepientEmail, $productName, $productId, $couponCode)
    { 

       //inicializo todas las variables del email       
       $templateId = Mage::helper(self::CONFIG_HELPER_NAME)->getTemplateId();//a number id. Eg 48
       $senderName = Mage::getStoreConfig('trans_email/ident_support/name');  //Get Sender Name from Store Email Addresses
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

        //if debug is enable, log that
        Mage::helper(self::CONFIG_HELPER_NAME)->log("email send to ".$customerName." at ".$recepientEmail);

    }



    public function generarCupon(){

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

        Mage::helper(self::CONFIG_HELPER_NAME)->log("Se genero un cupon para la regla ".$ruleId." con codigo ".$couponCode);

        return $couponCode;

    }



    
    //Es la funcion que ejecuta el cron
    public function sendFollowUpEmails()
    { 
        $configHelper = Mage::helper(self::CONFIG_HELPER_NAME);
        $isEnable = $configHelper->isEnabled();

        if ($isEnable)   {

            $followUpDays = $configHelper->getDaysPerProduct();
            /*
            followUpDays expect to have the format of 
                [15] => [product1, product2, ...], //15 days
                [20] => [product3, product4, ...], //20 days
                ...
            */    

            foreach ($followUpDays as $days => $productArray)
                {   //para cada uno de los dias hay que buscar las ordenes de ese dia. Necesito ir X cantidad de dias atras y tener el siguiente a ese dia

                    $stringDay= '-'.$days.' days';
                    $searchDay = date('Y-m-d', strtotime($stringDay)); //$days dias antes de hoy
                
                    $nextDay = date('Y-m-d', strtotime($searchDay .' +1 day'));


                    //busco las ordenes de ese dia
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


                        //comparar si alguno de los items que compro estan en $productArray. Si esta, usar sendEmail(...)

                        $customerName = $order->getCustomerFirstname();
                        $recepientEmail = $order->getCustomerEmail();
                        
                        foreach ($productArray as $productSku) {
                            
                            //por cada producto de la configuracion, busco si esta en la orden                        
                            foreach($items as $item) {
                                if ($productSku == $item['sku']) {
                                    $productId  = $item['id'];
                                    $productName = $item['name'];
                                    $couponCode = $this->generarCupon();
                                    $this->sendEmail($customerName, $recepientEmail,$productName,$productId, $couponCode);
                                    break 2; //encontre un producto. A ese cliente ya le mando un mail y no necesito seguir buscando. Salgo de los 2 foreach
                                }
                            }


                        }



                    }//cierre foreach de las ordenes de compra

                    


                }//cierre foreach para cada uno de los dias que estan configurados en el panel de administracion


       
            }//cierre if enable


    }


}