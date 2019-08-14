<?php

class Jmango360_Japi_Model_Rest_Product_Price extends Mage_Checkout_Model_Cart
{
    /**
     * @return array
     * @throws Jmango360_Japi_Exception
     */
    public function getPrice()
    {
        $data = array('product' => null, 'buy_request' => null);

        $params = $this->_getRequest()->getParams();
        if (isset($params['qty'])) {
            $filter = new Zend_Filter_LocalizedToNormalized(
                array('locale' => Mage::app()->getLocale()->getLocaleCode())
            );
            $params['qty'] = $filter->filter($params['qty']);
        }

        $request = $this->_getProductRequest($params);
        $product = $this->_getProduct($this->_getRequest()->getParam('product_id'));

        if ($product->getTypeId() != 'simple') {
            return $data;
        }

        $cartCandidates = $product->getTypeInstance(true)
            ->prepareForCartAdvanced($request, $product, Mage_Catalog_Model_Product_Type_Abstract::PROCESS_MODE_FULL);

        /**
         * Error message
         */
        if (is_string($cartCandidates)) {
            throw new Jmango360_Japi_Exception(
                $cartCandidates,
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        /**
         * If prepare process return one object
         */
        if (!is_array($cartCandidates)) {
            $cartCandidates = array($cartCandidates);
        }

        $finalPrice = null;
        if (count($cartCandidates) == 1) {
            /* @var $helper Jmango360_Japi_Helper_Product */
            $helper = Mage::helper('japi/product');
            $cartCandidates[0]->setData('final_price', $cartCandidates[0]->getPriceModel()->getFinalPrice(1, $cartCandidates[0]));
            $data['product'] = $helper->convertProductToApiResponseV2($cartCandidates[0], true);
            $data['buy_request'] = $helper->getCartProductBuyRequest($request, $cartCandidates[0]);
        }

        return $data;
    }

    /**
     * @return Jmango360_Japi_Model_Request
     */
    protected function _getRequest()
    {
        return Mage::getSingleton('japi/server')->getRequest();
    }
}