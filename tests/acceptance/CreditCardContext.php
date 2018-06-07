<?php

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Behat\Tester\Exception\PendingException;
use PagarMe_Core_Model_CurrentOrder as CurrentOrder;
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class CreditCardContext extends RawMinkContext
{
    use PagarMe\Magento\Test\Helper\PagarMeSettings;
    use PagarMe\Magento\Test\Helper\PagarMeSwitch;
    use PagarMe\Magento\Test\Helper\CustomerDataProvider;
    use PagarMe\Magento\Test\Helper\ProductDataProvider;
    use PagarMe\Magento\Test\Helper\SessionWait;

    use PagarMe\Magento\Test\CreditCard\AdminInterestRateCheck;
    use PagarMe\Magento\Test\CreditCard\AdminPaymentDetailsCheck;
    use PagarMe\Magento\Test\HookHandler\ScreenshotAfterFailedStep;

    private $createdOrderId;
    private $orderId;

    /**
     * @BeforeScenario
     */
    public function setUp()
    {
        $config = Mage::getModel('core/config');

        $this->magentoUrl = getenv('MAGENTO_URL');
        $this->session = $this->getSession();
        $this->product = $this->getProduct();
        $this->product->save();

        $stock = $this->getProductStock();
        $stock->assignProduct($this->product);
        $stock->save();

        $this->enablePagarmeTransparent();

        $config = Mage::getModel('core/config');

        $config->saveConfig(
            'payment/pagarme_configurations/general_encryption_key',
            PAGARME_ENCRYPTION_KEY
        );
        $config->saveConfig(
            'payment/pagarme_configurations/general_api_key',
            PAGARME_API_KEY
        );
    }

    /**
     * @Given a registered user
     */
    public function aRegisteredUser()
    {
        $this->customer = $this->getCustomer();
        $this->customer->save();

        $this->customerAddress = $this->getCustomerAddress();
        $this->customerAddress->setCustomerId($this->customer->getId());
        $this->customerAddress->save();
    }

    /**
     * @Given set a max installment as :installments and interest rate as :interestRate
     */
    public function setAMaxInstallmentAsAndInterestRateAs(
        $installments,
        $interestRate
    ) {
        $config = Mage::getModel('core/config');
        $config->saveConfig(
            'payment/pagarme_configurations/creditcard_max_installments',
                $installments
        );
        $config->saveConfig(
            'payment/pagarme_configurations/creditcard_interest_rate',
            $interestRate
        );

    }

    /**
     * @Given a created order with installment value of :installments and interest of :interestRate
     */
    public function aCreatedOrderWithInstallmentValueOfAndInterestOf(
        $installments,
        $interestRate
    ) {
        $this->iAccessTheStorePage();
        $this->addAnyProductToBasket();
        $this->iGoToCheckoutPage();
        $this->loginWithRegisteredUser();
        $this->confirmBillingAndShippingAddressInformation();
        $this->choosePayWithTransparentCheckoutUsingCreditCard();
        $config = Mage::getModel('core/config');
        $config->saveConfig(
            'payment/pagarme_configurations/creditcard_max_installments',
            10
        );
        $config->saveConfig(
            'payment/pagarme_configurations/creditcard_interest_rate',
            10
        );
        $this->iChooseMaxInstallments($installments);
        $this->iConfirmMyPaymentInformation();
        $this->placeOrder();
        $this->thePurchaseMustBePaidWithSuccess();
        $this->session->wait(5000);
        $this->createdOrderId = $this->session->getPage()
            ->find('css', '.col-main a:first-of-type')
            ->getText();
    }

    /**
     * @Given registered user logged
     */
    public function registeredUserLogged()
    {
        $this->session
            ->visit($this->magentoUrl . 'customer/account/login');
        $this->waitForElement('#email', 2000);
        $this->loginWithRegisteredUser();
    }

    /**
     * @When I check the order interest amount in its detail page
     */
    public function iCheckTheOrderInterestAmountInItsDetailPage()
    {
        $currentUser = Mage::getSingleton('customer/session')
            ->getCustomer();
        /**
         *Might result in problems if the tests are parallel
         */
        $order = Mage::getModel('sales/order')->getCollection()
            ->addFieldToSelect('*')
            ->setOrder('created_at', 'desc')
            ->getFirstItem();

        $this->session
            ->visit($this->magentoUrl . 'sales/order/view/order_id/' . $order->getId());
    }

    /**
     * @When I check the order interest amount in its admin detail page
     */
    public function iCheckTheOrderInterestAmountInItsAdminDetailPage()
    {
        $order = Mage::getModel('sales/order')
            ->load($this->createdOrderId, 'increment_id');
        $this->session
            ->visit(
                $this->magentoUrl . 'admin/sales_order/view/order_id/' . $order->getId()
            );
    }

    /**
     * @When I set max installments to :maxInstallments
     */
    public function iSetMaxInstallmentsTo($maxInstallments)
    {
        $config = Mage::getModel('core/config');

        $config->saveConfig(
            'payment/pagarme_configurations/creditcard_max_installments',
            $maxInstallments
        );
    }

    /**
     * @When I set interest rate to :interestRate
     */
    public function iSetInterestRateTo($interestRate)
    {
        $config = Mage::getModel('core/config');

        $config->saveConfig(
            'payment/pagarme_configurations/creditcard_interest_rate',
            $interestRate
        );

    }

    /**
     * @When I access the store page
     */
    public function iAccessTheStorePage()
    {
        $this->session
            ->visit($this->magentoUrl);
    }

    /**
     * @When add any product to basket
     */
    public function addAnyProductToBasket()
    {
        $page = $this->session->getPage();

        $page->clickLink($this->product->getName());

        $page->pressButton(
            Mage::helper('pagarme_modal')->__('Add to Cart')
        );

    }

    /**
     * @When I go to checkout page
     */
    public function iGoToCheckoutPage()
    {
        $page = $this->session->getPage();

        $page->pressButton(
            Mage::helper('pagarme_modal')->__('Proceed to Checkout')
        );

    }

    /**
     * @When I access the my account page
     */
    public function iAccessTheMyAccountPage()
    {
        $this->session
            ->visit($this->magentoUrl);
    }

    /**
     * @When login with registered user
     */
    public function loginWithRegisteredUser()
    {
        $page = $this->session->getPage();
        $this->session->wait(10000);
        $page->fillField(
            Mage::helper('pagarme_modal')->__('Email Address'),
            $this->customer->getEmail()
        );

        $page->fillField(
            Mage::helper('pagarme_modal')->__('Password'),
            $this->customer->getPassword()
        );

        $page->pressButton('Login');
    }

    /**
     * @When confirm billing and shipping address information
     */
    public function confirmBillingAndShippingAddressInformation()
    {
        $page = $this->session->getPage();

        $page->find('css', '#billing-buttons-container button')->press();

        $this->session->wait(10000);

        $page->find('css', '#shipping-method-buttons-container button')
            ->press();
    }

    /**
     * @When choose pay with transparent checkout using credit card
     */
    public function choosePayWithTransparentCheckoutUsingCreditCard()
    {
        $page = $this->session->getPage();

        $this->waitForElement('#checkout-step-payment', 5000);

        $this->waitForElement('#p_method_pagarme_creditcard', 7000);
        $page->find('css', '#p_method_pagarme_creditcard')->click();
    }

    /**
     * @When I confirm my payment information
     */
    public function iConfirmMyPaymentInformation()
    {
        $page = $this->session->getPage();

        $page->find('css', '#pagarme_creditcard_creditcard_number')
            ->setValue('4111111111111111');

        $page->find('css', '#pagarme_creditcard_creditcard_owner')
            ->setValue('Luiz Maria da Silva');

        $page->find('css', '#pagarme_creditcard_creditcard_expiration_date')
            ->setValue('0722');

        $page->find('css', '#pagarme_creditcard_creditcard_cvv')
            ->setValue('123');

        $this->session->getPage()->find(
            'css',
            '#payment-buttons-container button'
        )->click();
    }

    /**
     * @When I choose :maxInstallments
     */
    public function iChooseMaxInstallments($maxInstallments)
    {
        $page = $this->session->getPage();

        $page->find('css', '#pagarme_creditcard_creditcard_installments')
            ->selectOption($maxInstallments);
    }

    /**
     * @When place order
     */
    public function placeOrder()
    {
        $this->waitForElement('#checkout-step-review', 8000);

        $this->session
            ->getPage()
            ->pressButton(
                Mage::helper('pagarme_creditcard')
                    ->__('Place Order')
            );
    }

    /**
     * @Then I get the created order id 
     */
    public function iGetTheCreatedOrderId()
    {
        $this->createdOrderId = $this->session->getPage()
            ->find('css', '.col-main a:first-of-type')
            ->getText();
    }

    /**
     * @Then the purchase must be paid with success
     */
    public function thePurchaseMustBePaidWithSuccess()
    {
        $this->session->wait(17000);
        $page = $this->session->getPage();

        $successMessage = $page->find('css', 'h1')
            ->getText();

        \PHPUnit_Framework_TestCase::assertEquals(
            getenv('MAGENTO_URL') . 'index.php/checkout/onepage/success/',
            $this->session->getCurrentUrl()
        );

        \PHPUnit_Framework_TestCase::assertEquals(
            strtolower(
                Mage::helper('pagarme_creditcard')
                    ->__('Your order has been received.')
            ),
            strtolower($successMessage)
        );
    }

    /**
     * @When I should see only installment options up to :maxInstallments
     */
    public function iShouldSeeOnlyInstallmentOptionsUpTo($maxInstallments)
    {
        $this->assertSession()->elementsCount(
            'css',
            '#pagarme_creditcard_creditcard_installments > option',
            intval($maxInstallments)
        );
        $this->assertThereIsEveryOptionValueUntil(
            $maxInstallments,
            '#pagarme_creditcard_creditcard_installments'
        );
    }

    private function assertThereIsEveryOptionValueUntil(
        $maxValue, 
        $selectCssSelector
    ) {
        for ($value = 1; $value <= $maxValue; $value++) {
            $this->assertSession()->elementExists(
                'css',
                $selectCssSelector . " > option[value={$value}]"
            );
        }
    }

    /**
     * @Then the interest value should consider the values :installments and :interestRate
     */
    public function theInterestValueShouldConsiderTheValuesAnd(
        $installments,
        $interestRate
    ) {
        $this->waitForElement('.pagarme_creditcard_rate_amount', 3000);
        $page = $this->session->getPage();
        $interestAmount = $page
            ->find('css', '.pagarme_creditcard_rate_amount .price')
            ->getText();

        \PHPUnit_Framework_TestCase::assertEquals(
            $interestAmount,
            'R$11.22'
        );

    }

    /**
     * @Then the purchase must be created with value based on both :installments and :interestRate
     */
    public function thePurchaseMustBeCreatedWithValueBasedOnBothAnd(
        $installments,
        $interestRate
    ) {
        $page = $this->session->getPage();

        $this->session->wait(2000);

        $checkoutTotalAmount = $page->find(
            'css',
            'tr.last:not(.first) .price'
        )->getText();
        \PHPUnit_Framework_TestCase::assertEquals(
            $checkoutTotalAmount,
            'R$27.44'
        );
    }

   /**
     * @Given a existing order
     */
    public function aExistingOrder()
    {
        $resource = Mage::getSingleton('core/resource');

        $readConnection = $resource->getConnection('core_read');

        $query = 'SELECT order_id FROM pagarme_transaction WHERE rate_amount > 0 AND payment_method = \'credit_card\'';

        $this->orderId = (int)$readConnection->fetchOne($query);

        \PHPUnit_Framework_TestCase::assertInternalType('int', $this->orderId);
    }

    /**
     * @When I check the invoice interest amount in its admin detail page
     */
    public function iCheckTheInvoiceInterestAmountInItsAdminDetailPage()
    {
        $orderObject = Mage::getModel('sales/order')->load($this->orderId);

        $invoiceIds = $orderObject->getInvoiceCollection()->getAllIds();

        Mage::getConfig()->saveConfig('admin/security/use_form_key', 0);

        $url = $this->magentoUrl . 'index.php/admin/sales_order_invoice/view/invoice_id/'.$invoiceIds[0].'/order_id/'. $this->orderId;

        $this->session->visit($url);
        
        Mage::getConfig()->saveConfig('admin/security/use_form_key', 1);
    }

    /**
     * @Then the interest value should be :interest in the invoice details
     */
    public function theInterestValueShouldBeInTheInvoiceDetails($interest)
    {
        $this->session->wait(3000);

        $invoiceInterest = $this->session->evaluateScript(
                "return document.querySelector(
                    '.order-totals td:last-child > .price'
                ).innerHTML;"
            );

        \PHPUnit_Framework_TestCase::assertEquals('R$'.$interest, $invoiceInterest);
    }

    /**
     * @AfterScenario
     */
    public function afterEveryScenario()
    {
        Mage::getSingleton('customer/session')->logout();
    }

}
