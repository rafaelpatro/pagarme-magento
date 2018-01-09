<?php

namespace PagarMe\Magento\Test\Helper;

trait AdminAccessProvider
{
    private $adminUser;

    /**
     * @return string
     */
    private function getAdminPassword()
    {
        return 'admin123';
    }

    /**
     * @Given an admin user
     * @Then as an Admin user
     */
    public function aAdminUser()
    {
        $this->adminUser = \Mage::getModel('admin/user')
            ->setData(
                array(
                    'username'  => mktime() . '_admin',
                    'firstname' => 'Admin',
                    'lastname'  => 'Admin',
                    'email'     => mktime() . '@admin.com',
                    'password'  => $this->getAdminPassword(),
                    'is_active' => 1
                )
            )->save();

        $this->adminUser->setRoleIds(
            array(1)
        )
            ->setRoleUserId($this->adminUser->getUserId())
            ->saveRelations();
    }

    /**
     * @When I access the admin
     */
    public function iAccessTheAdmin()
    {
        $session = $this->getSession();
        $session->visit(getenv('MAGENTO_URL') . 'index.php/admin');

        $page = $session->getPage();
        $inputLogin = $page->find('named', array('id', 'username'));
        $inputLogin->setValue($this->adminUser->getUsername());

        $inputPassword = $page->find('named', array('id', 'login'));
        $inputPassword->setValue($this->getAdminPassword());

        $page->pressButton('Login');
    }

    /**
     * @When go to system configuration page
     */
    public function goToSystemConfigurationPage()
    {
        $session = $this->getSession();
        $page = $session->getPage();

        $popup = $page->find('css', '.message-popup-head a');

        if ($popup instanceof \Behat\Mink\Element\NodeElement) {
            $popup->click();
        }

        $page->find('named', array('link', 'System'))
            ->mouseOver();

        $page->find('named', array('link', 'Configuration'))
            ->click();

        $page->find('named', array('link', 'Payment Methods'))
            ->click();

        $page->find('css', '#payment_pagarme_configurations-head')->click();

        $this->spin(function () use ($page) {
            return $page->findById('config_edit_form') != null;
        }, 10);
    }

    public function closeAdminPopup($page)
    {
        $popup = $page->find('css', '.message-popup-head a');

        if ($popup instanceof \Behat\Mink\Element\NodeElement) {
            $popup->click();
        }
    }
}
