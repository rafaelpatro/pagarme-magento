<?php
use Behat\MinkExtension\Context\RawMinkContext;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class RefusedTransactionsContext extends RawMinkContext
{
    use \PagarMe\Magento\Test\Helper\AdminAccessProvider;
}