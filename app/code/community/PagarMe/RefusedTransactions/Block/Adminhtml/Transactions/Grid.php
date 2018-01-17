<?php

class PagarMe_RefusedTransactions_Block_Adminhtml_Transactions_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('transactionsGrid');
        $this->setDefaultSort('nome');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInsession(true);
    }

    protected function _prepareCollection()
    {
        $collection = ['nome', 'valor', 'blah'];
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('nome', ['header' => 'nome', 'index'=> 'nome']);
        $this->addColumn('valor', ['header' => 'valor', 'index'=> 'valor']);
        $this->addColumn('blah', ['header' => 'blah', 'index'=> 'blah']);

        return parent::_prepareColumns();
    }

    protected function _prepareMassActions()
    {}

}