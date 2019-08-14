<?php

class Jmango360_Japi_Model_Rest_Product_Purchased extends Jmango360_Japi_Model_Rest_Product
{
    /**
     * Get list purchased products of current logged in customer
     * @return array
     * @throws Jmango360_Japi_Exception
     */
    public function getList()
    {
        $data = array();

        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');
        $_productIds = $this->_getPurchasedProductIds();
        if (!count($_productIds)) {
            $data['message'] = $helper->__('No products found.');
        }

        $data['products'] = $helper->convertProductIdsToApiResponse($_productIds);

        return $data;
    }

    /**
     * Get recently purchased product IDs of current logged in customer
     * @return array
     * @throws Jmango360_Japi_Exception
     */
    protected function _getPurchasedProductIds()
    {
        $_data = array();
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            if (!Mage::getSingleton('customer/session')->getCustomer()->getId()) {
                throw new Jmango360_Japi_Exception(
                    Mage::helper('japi')->__('Cannot find customer ID, please try again!'),
                    Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
                );
            }

            $_customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
            $orders = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToFilter('customer_id', $_customerId)
                ->setOrder('entity_id', 'DESC');

            foreach ($orders as $order) {
                if (count($_data) >= 5) {
                    break;
                }

                /* @var $order Mage_Sales_Model_Order */
                $order = Mage::getModel("sales/order")->load($order->getId());
                $items = $order->getAllVisibleItems();

                foreach ($items as $item) {
                    /* @var $item Mage_Sales_Model_Order_Item */
                    if ($item->getProductId() && !in_array($item->getProductId(), $_data)) {
                        $_data[] = $item->getProductId();
                    }
                    if (count($_data) >= 5) {
                        break;
                    }
                }
            }
        }

        return $_data;
    }
}