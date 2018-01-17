<?php

class PagarMe_RefusedTransactions_Adminhtml_RefusedTransactionsController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();

        return $this;
    }
}
