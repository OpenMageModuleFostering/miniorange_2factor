<?php

class MiniOrange_2factor_Adminhtml_Login_MiniOrangeController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed(){
        return true;
    }

    public function loginAction(){
		$this->_outTemplate('miniorange_2factor/login');
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
