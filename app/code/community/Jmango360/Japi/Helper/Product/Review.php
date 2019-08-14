<?php

class Jmango360_Japi_Helper_Product_Review extends Mage_Core_Helper_Abstract
{
    public function isReviewEnable()
    {
        return $this->isModuleEnabled('Mage_Review') && $this->isModuleOutputEnabled('Mage_Review');
    }

    /**
     * Get if customer can submit review
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function isAllowedReview($product = null)
    {
        $isModuleEnable = $this->isModuleEnabled();
        if (!$isModuleEnable) {
            return false;
        } else {
            /* @var $customerSession Mage_Customer_Model_Session */
            $customerSession = Mage::getSingleton('customer/session');
            if (Mage::getStoreConfigFlag('catalog/review/allow_guest')) {
                return true;
            } else {
                return $customerSession->isLoggedIn();
            }
        }
    }

    /**
     * Get product review summary
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getProductReviewSummary($product)
    {
        if (!$this->isReviewEnable()) return;
        if (!$product || !$product->getId()) return;
        if (!$product->getRatingSummary()) return;
        return (int)$product->getRatingSummary()->getRatingSummary();
    }

    /**
     * Get product review count
     *
     * @param Mage_Catalog_Model_Product $product
     * @return int
     */
    public function getProductReviewCount($product)
    {
        if (!$this->isReviewEnable()) return;
        if (!$product || !$product->getId()) return;
        if (!$product->getRatingSummary()) return;
        return (int)$product->getRatingSummary()->getReviewsCount();
    }

    /**
     * Get product review overview
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getProductReviewOverview($product)
    {
        $data = array();

        $ratingCollection = $this->_getRatingsCollection();
        $ratingCollection->addEntitySummaryToItem($product->getId(), Mage::app()->getStore()->getId());

        foreach ($ratingCollection as $rating) {
            if ($rating->getSummary()) {
                $data[] = array(
                    'title' => $rating->getRatingCode(),
                    'code' => $rating->getRatingCode(),
                    'type' => 'overview',
                    'percent' => $rating->getSummary()
                );
            }
        }

        return $data;
    }

    /**
     * Get product reviews list
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getProductReviewList($product)
    {
        $data = array();

        $ratingValues = array();
        $ratingCollection = $this->_getRatingsCollection();
        foreach ($ratingCollection as $rating) {
            $ratingOptions = array();
            foreach ($rating->getOptions() as $option) {
                $ratingOptions[] = $option->getId();
            }
            $ratingValues[$rating->getId()] = $ratingOptions;
        }

        $reviewCollection = $this->_getReviewsCollection($product);
        foreach ($reviewCollection as $review) {
            /* @var $review Mage_Review_Model_Review */
            $reviewData = array(
                'nickname' => $review->getNickname(),
                'create_at' => $this->_getDateFormat($review->getCreatedAt())
            );

            $ratings = array();
            foreach ($review->getRatingVotes() as $rating) {
                /* @var $rating Mage_Rating_Model_Rating */

                $ratings[] = array(
                    'title' => $rating->getRatingCode(),
                    'code' => 'ratings',
                    'type' => 'radio',
                    'required' => true,
                    'id' => $rating->getRatingId(),
                    'values' => isset($ratingValues[$rating->getRatingId()]) ? $ratingValues[$rating->getRatingId()] : null,
                    'selected' => $this->_getSelectedRating($rating, isset($ratingValues[$rating->getRatingId()]) ? $ratingValues[$rating->getRatingId()] : array()),
                    'percent' => $rating->getPercent()
                );
            }

            $ratings = array_merge($ratings, $this->_getProductReviewFormByVersion($review));

            $reviewData['review'] = $ratings;
            $data[] = $reviewData;
        }

        return $data;
    }

    /**
     * Get product review form fields
     */
    public function getProductReviewForm()
    {
        $data = array();

        $ratingCollection = $this->_getRatingsCollection();
        foreach ($ratingCollection as $rating) {
            $values = array();
            foreach ($rating->getOptions() as $option) {
                $values[] = $option->getId();
            }

            $ratingData = array(
                'title' => $rating->getRatingCode(),
                'code' => 'ratings',
                'type' => 'radio',
                'required' => true,
                'id' => $rating->getRatingId(),
                'values' => $values
            );

            $data[] = $ratingData;
        }

        $data = array_merge($data, $this->_getProductReviewFormByVersion());

        return $data;
    }

    protected function _getProductReviewFormByVersion($review = null)
    {
        if (version_compare(Mage::getVersion(), '1.9.0', '>=')) {
            $form = array(
                array(
                    'title' => $this->__('Let us know your thoughts'),
                    'code' => $this->__('detail'),
                    'type' => 'area',
                    'required' => true,
                    'selected' => $review ? $review->getDetail() : null
                ),
                array(
                    'title' => $this->__('Summary of Your Review'),
                    'code' => $this->__('title'),
                    'type' => 'field',
                    'required' => true,
                    'selected' => $review ? $review->getTitle() : null
                ),
                array(
                    'title' => $this->__('What\'s your nickname?'),
                    'code' => $this->__('nickname'),
                    'type' => 'field',
                    'required' => true,
                    'selected' => $review ? $review->getNickname() : null
                )
            );
        } else {
            $form = array(
                array(
                    'title' => $this->__('Nickname'),
                    'code' => $this->__('nickname'),
                    'type' => 'field',
                    'required' => true,
                    'selected' => $review ? $review->getNickname() : null
                ),
                array(
                    'title' => $this->__('Summary of Your Review'),
                    'code' => $this->__('title'),
                    'type' => 'field',
                    'required' => true,/**/
                    'selected' => $review ? $review->getTitle() : null
                ),
                array(
                    'title' => $this->__('Review'),
                    'code' => $this->__('detail'),
                    'type' => 'area',
                    'required' => true,
                    'selected' => $review ? $review->getDetail() : null
                )
            );
        }

        return $form;
    }

    /**
     * Fix old data
     *
     * @param Mage_Rating_Model_Rating $rating
     * @param array $values
     * @return int
     */
    protected function _getSelectedRating($rating, $values)
    {
        if (in_array($rating->getOptionId(), $values)) {
            return $rating->getOptionId();
        } else {
            if ($rating->getPercent() == '') return null;
            $index = floor($rating->getPercent() / (100 / count($values)));
            return isset($values[$index - 1]) ? $values[$index - 1] : null;
        }
    }

    /**
     * Get data by store timezone
     *
     * @param $date
     * @return string
     */
    protected function _getDateFormat($date)
    {
        $date = Mage::app()->getLocale()->date(strtotime($date), null, null);
        return $date->toString('Y-M-d h:m:s');
    }

    /**
     * @return Mage_Rating_Model_Resource_Rating_Collection
     */
    protected function _getRatingsCollection()
    {
        $ratingCollection = Mage::getModel('rating/rating')
            ->getResourceCollection()
            ->addEntityFilter('product')
            ->setPositionOrder()
            ->addRatingPerStoreName(Mage::app()->getStore()->getId())
            ->setStoreFilter(Mage::app()->getStore()->getId())
            ->load()
            ->addOptionToItems();

        return $ratingCollection;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Review_Model_Resource_Review_Collection
     */
    protected function _getReviewsCollection($product)
    {
        $reviewsCollection = Mage::getModel('review/review')->getCollection()
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED)
            ->addEntityFilter('product', $product->getId())
            ->setDateOrder()
            ->addRateVotes();

        return $reviewsCollection;
    }
}