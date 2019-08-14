<?php

class Jmango360_Japi_Model_Rest_Product_Review extends Jmango360_Japi_Model_Rest_Product
{
    /**
     * Get product reviews list
     */
    public function getList()
    {
        $product = $this->_initProduct();

        /* @var $reviewHelper Jmango360_Japi_Helper_Product_Review */
        $reviewHelper = Mage::helper('japi/product_review');

        $data['overview'] = $reviewHelper->getProductReviewOverview($product);
        $data['reviews'] = $reviewHelper->getProductReviewList($product);
        $data['review_counter'] = $reviewHelper->getProductReviewCount($product);

        return $data;
    }

    /**
     * Get product review form fields
     */
    public function getForm()
    {
        /* @var $reviewHelper Jmango360_Japi_Helper_Product_Review */
        $reviewHelper = Mage::helper('japi/product_review');

        $data['allow_guest_review'] = Mage::getStoreConfigFlag('catalog/review/allow_guest');
        $data['reviews'] = $reviewHelper->getProductReviewForm();

        return $data;
    }

    /**
     * Save product review
     */
    public function saveReview()
    {
        $product = $this->_initProduct();

        $data = $this->_getRequest()->getParams();
        $rating = $this->_getRequest()->getParam('ratings', array());

        if ($product && !empty($data)) {
            $review = Mage::getModel('review/review')->setData($data);
            /* @var $review Mage_Review_Model_Review */

            $validate = $review->validate();
            if ($validate === true) {
                try {
                    $review->setEntityId($review->getEntityIdByCode(Mage_Review_Model_Review::ENTITY_PRODUCT_CODE))
                        ->setEntityPkValue($product->getId())
                        ->setStatusId(Mage_Review_Model_Review::STATUS_PENDING)
                        ->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId())
                        ->setStoreId(Mage::app()->getStore()->getId())
                        ->setStores(array(Mage::app()->getStore()->getId()))
                        ->save();

                    foreach ($rating as $ratingId => $optionId) {
                        Mage::getModel('rating/rating')
                            ->setRatingId($ratingId)
                            ->setReviewId($review->getId())
                            ->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId())
                            ->addOptionVote($optionId, $product->getId());
                    }

                    $review->aggregate();
                    return array('success' => Mage::helper('review')->__('Your review has been accepted for moderation.'));
                } catch (Exception $e) {
                    throw new Jmango360_Japi_Exception(
                        Mage::helper('review')->__('Unable to post the review.'),
                        Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                    );
                }
            } else {
                if (is_array($validate)) {
                    $errors = array();
                    foreach ($validate as $errorMessage) {
                        $errors[] = $errorMessage;
                    }
                    throw new Jmango360_Japi_Exception(
                        implode("\n", $errors),
                        Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                    );
                } else {
                    throw new Jmango360_Japi_Exception(
                        Mage::helper('review')->__('Unable to post the review.'),
                        Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                    );
                }
            }
        } else {
            throw new Jmango360_Japi_Exception(
                Mage::helper('review')->__('Unable to post the review.'),
                Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
            );
        }
    }
}