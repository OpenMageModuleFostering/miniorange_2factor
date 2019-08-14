<?php
class MiniOrange_2factor_Model_Words
{
    public function toOptionArray()
    {
        return array(
			 array('value'=>0, 'label'=>Mage::helper('MiniOrange_2factor')->__('No')), 
			 array('value'=>1, 'label'=>Mage::helper('MiniOrange_2factor')->__('Yes'))                                
        );
    }
	
	
	
	 /*public function getLabelText(){ 
       return 'test';
    }*/

}