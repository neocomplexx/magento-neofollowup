<?php

class Neocomplexx_Neofollowup_Block_Adminhtml_System_Config_Form_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /*
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('neocomplexx/neofollowup/system/config/button.phtml');
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxCheckUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/adminhtml_neofollowup/generateRule');
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
            'id'        => 'neofollowup_button',
            'label'     => $this->helper('adminhtml')->__('Generate Rule'), //is the button label that shows in the admin panel
            'onclick'   => 'javascript:generateRule(); return false;' //generateRule is defined in the template. It calls getAjaxCheckUrl()  
        ));

        return $button->toHtml();
    }
}
