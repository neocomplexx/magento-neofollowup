<?php
class Neocomplexx_Neofollowup_Block_Config_DaysProduct 
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function _prepareToRender()
    {
        $this->addColumn('product', array(
            'label' => Mage::helper('neofollowup')->__('Product SKU'),
            'style' => 'width:100px',
        ));
        $this->addColumn('days', array(
            'label' => Mage::helper('neofollowup')->__('Days until follow up Email'),
            'style' => 'width:130px',
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('neofollowup')->__('Add');
    }
}