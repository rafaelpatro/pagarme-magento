<?php
use Behat\MinkExtension\Context\RawMinkContext;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class RefusedTransactionsContext extends RawMinkContext
{
    use \PagarMe\Magento\Test\Helper\AdminAccessProvider;

    /**
     * @When I go to Pagar.me refused transactions page
     */
    public function iGoToPagarMeRefusedTransactionsPage()
    {
        $session = $this->getSession();
        $page = $session->getPage();
        $this->closeAdminPopup($page);

        $page->find('named', ['link', 'Pagar.me'])
            ->mouseOver();

        $page->find('named', ['link', 'Refused Transactions'])
            ->click();
    }

    /**
     * @Then a refused transactions list should be visible
     */
    public function aRefusedTransactionsListShouldBeVisible()
    {
        throw new PendingException();
    }
}