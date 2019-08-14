<?php
class Jmango360_Japi_Model_Rest_Cart_Coupon extends Jmango360_Japi_Model_Rest_Cart
{
    protected $_restCouponCode = null;
    
    public function _construct()
    {
        $request = Mage::helper('japi')->getRequest();
        $this->_restCouponCode = $request->getParam('coupon_code', null);
    }

    public function add()
    {
        $this->_applyCoupon();
        
        return $this->getCouponData();
    }

    public function update()
    {
        $this->_applyCoupon();
        
        return $this->getCouponData();
    }

    public function remove()
    {
        $this->_restCouponCode = '';
        $this->_applyCoupon();
        
        return $this->getCouponData();
    }

    protected function _applyCoupon()
    {
        $quote = $this->getQuote();

        if (!$quote->getItemsCount()) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Cart is empty.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        try {
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->setCouponCode(strlen($this->_restCouponCode) ? $this->_restCouponCode : '')
                ->collectTotals()
                ->save();
        } catch (Exception $e) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Coupon is not valid: ' . $e->getMessage()), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        if ($this->_restCouponCode) {
            if ($this->_restCouponCode != $quote->getCouponCode()) {
                throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Coupon could not be applied.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            }
        }
        
        return $this;
    }
}




