<?php

class PagarMe_RefusedTransactions_Block_Adminhtml_Transactions extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        // class name
        $this->_controller = 'adminhtml_transactions';
        //module name
        $this->_blockGroup = 'pagarme_refusedtransactions';
        $this->_headerText = Mage::helper('pagarme_refusedtransactions')
            ->__('Refused transactions');

        parent::__construct();
    }


}