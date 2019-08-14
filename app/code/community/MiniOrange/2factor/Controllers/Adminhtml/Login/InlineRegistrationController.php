<?php

class MiniOrange_2factor_Adminhtml_Login_InlineRegistrationController extends Mage_Adminhtml_Controller_Action
{
	protected function _isAllowed(){
        return true;
    }

    public function indexAction(){
		$this->_outTemplate('miniorange_2factor/inlineRegistration');
    }

    protected function _outTemplate($tplName, $data = array()){
        $this->_initLayoutMessages('adminhtml/session');
		$block = $this->getLayout()->createBlock('adminhtml/template')->setTemplate("$tplName.phtml");
		foreach ($data as $index => $value) {
			$block->assign($index, $value);
		}
		$html = $block->toHtml();
		Mage::getSingleton('core/translate_inline')->processResponseBody($html);
		$this->getResponse()->setBody($html);
    }
	
	
		
}