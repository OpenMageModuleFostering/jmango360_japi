<?php
/**
 * Copyright 2017 JMango360
 */

/**
 * Class Jmango360_Japi_Helper_Review_Bazaarvoice
 */
class Jmango360_Japi_Helper_Review_Bazaarvoice extends Mage_Core_Helper_Abstract
{
    const URL_STAGING = 'https://stg.api.bazaarvoice.com/';
    const URL_PRODUCTION = 'https://api.bazaarvoice.com/';
    const API_VERSION = '5.4';

    const DEFAULT_LIMIT = 99;
    const DEFAULT_SORT = 'SubmissionTime';
    const DEFAULT_DIR = 'desc';

    const CACHE_KEY_REVIEW_FORM = 'BAZAARVOICE_REVIEW_FORM';

    protected $FORM_FIELDS = array(
        'rating' => 'Overall Rating',
        'title' => 'Review Title',
        'reviewtext' => 'Review',
        'usernickname' => 'Nickname',
        'useremail' => 'Email',
        'rating_' => null,
        'contextdatavalue_' => null,
        'isrecommended' => 'Would you recommend this product to your friends?',
        'agreedtotermsandconditions' => 'I agree to the terms & conditions'
    );
    protected $LOGGED_IN_EXCLUDE_FIELDS = array();

    /**
     * Get product Id for Bazaarvoice
     *
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    public function getBvProductId($product)
    {
        if (!$product && !$product->getId()) return '';

        if (class_exists('Bazaarvoice_Connector_Helper_Data')) {
            /* @var $bvHelper Bazaarvoice_Connector_Helper_Data */
            $bvHelper = Mage::helper('bazaarvoice');
            return $bvHelper->getProductId($product);
        }

        return $this->_getProductId($product);
    }

    /**
     * Get the uniquely identifying product ID for a catalog product.
     *
     * This is the unique, product family-level id (duplicates are unacceptable).
     * If a product has its own page, this is its product ID. It is not necessarily
     * the SKU ID, as we do not collect separate Ratings & Reviews for different
     * styles of product - i.e. the 'Blue' vs. 'Red Widget'.
     *
     * @param Mage_Catalog_Model_Product $product a reference to a catalog product object
     * @return string The unique product ID to be used with Bazaarvoice
     */
    public function _getProductId($product)
    {
        $rawProductId = $product->getSku();

        // >> Customizations go here
        $rawProductId = preg_replace_callback('/\./s', create_function('$match', 'return "_bv".ord($match[0])."_";'), $rawProductId);
        // << No further customizations after this

        return $this->_replaceIllegalCharacters($rawProductId);
    }

    /**
     * This unique ID can only contain alphanumeric characters (letters and numbers
     * only) and also the asterisk, hyphen, period, and underscore characters. If your
     * product IDs contain invalid characters, simply replace them with an alternate
     * character like an underscore. This will only be used in the feed and not for
     * any customer facing purpose.
     *
     * @param string $rawId
     * @return mixed
     */
    protected function _replaceIllegalCharacters($rawId)
    {
        // We need to use a reversible replacement so that we can reconstruct the original ID later.
        // Example rawId = qwerty$%@#asdf
        // Example encoded = qwerty_bv36__bv37__bv64__bv35_asdf

        return preg_replace_callback('/[^\w\d\*-\._]/s', create_function('$match', 'return "_bv".ord($match[0])."_";'), $rawId);
    }

    /**
     * Append review data to product collection
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @throws Jmango360_Japi_Exception
     */
    public function appendReviews($collection)
    {
        if (!$collection || !$collection->getSize()) {
            return;
        }

        $productIds = array();
        foreach ($collection as $item) {
            $productIds[$this->_getProductId($item)] = $item;
        }
        $locale = Mage::app()->getLocale()->getLocaleCode();
        $apikey = $this->_getApiKey();
        $url = $this->_getApiUrl('data/batch.json', array(
            'passkey' => $apikey,
            'apiVersion' => self::API_VERSION,
            'resource.q0' => 'statistics',
            'stats.q0' => 'reviews',
            'filter.q0' => 'contentlocale:eq:' . $locale,
            'filter_reviews.q0' => 'contentlocale:eq:' . $locale,
            'filter.q0' => 'productid:eq:' . join(',', array_keys($productIds))
        ));

        $result = $this->send('GET', $url);

        if (isset($result['HasErrors']) && $result['HasErrors']) {
            if (!empty($result['Errors'])) {
                foreach ($result['Errors'] as $error) {
                    throw new Jmango360_Japi_Exception(
                        sprintf('%s', $error['Message']),
                        Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                    );
                }
            } else {
                throw new Jmango360_Japi_Exception(
                    Mage::helper('japi')->__('An error has occurred, please try again later.'),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }
        }

        if (!empty($result['BatchedResults']['q0']['Results'])) {
            $reviewsData = $result['BatchedResults']['q0']['Results'];
            foreach ($reviewsData as $reviewData) {
                if (!empty($reviewData['ProductStatistics'])) {
                    $productData = $reviewData['ProductStatistics'];
                    $ratingSummary = new Varien_Object();
                    $ratingSummary->setReviewsCount(@$productData['ReviewStatistics']['TotalReviewCount']);
                    $averageOverallRating = @$productData['ReviewStatistics']['AverageOverallRating'];
                    $overallRatingRange = @$productData['ReviewStatistics']['OverallRatingRange'];
                    $ratingSummary->setRatingSummary(floor(100 * $averageOverallRating / $overallRatingRange));
                    $ratingSummary->setRatingRange($overallRatingRange);
                    foreach ($productIds as $id => $item) {
                        if ($id == @$productData['ProductId']) {
                            $item->setRatingSummary($ratingSummary);
                        }
                    }
                }
            }
        }
    }

    /**
     * Requesting all reviews for a particular product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     * @throws Jmango360_Japi_Exception
     */
    public function getReviews($product)
    {
        if (!$product->getId()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Product not found'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        $productId = $this->getBvProductId($product);

        $apiKey = $this->_getApiKey();
        $url = $this->_getApiUrl('data/reviews.json', array(
            'apiVersion' => '5.4',
            'passkey' => $apiKey,
            'Filter' => sprintf('ProductId:%s', $productId),
            'Sort' => $this->_getReviewsSort(),
            'Offset' => $this->_getReviewsOffset(),
            'Limit' => $this->_getReviewsLimit(),
            'Include' => 'Products',
            'Stats' => 'Reviews',
            'Locale' => Mage::app()->getLocale()->getLocaleCode()
        ));

        $result = $this->send('GET', $url);

        if (isset($result['HasErrors']) && $result['HasErrors']) {
            if (!empty($result['Errors'])) {
                foreach ($result['Errors'] as $error) {
                    throw new Jmango360_Japi_Exception(
                        sprintf('%s', $error['Message']),
                        Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                    );
                }
            } else {
                throw new Jmango360_Japi_Exception(
                    Mage::helper('japi')->__('An error has occurred, please try again later.'),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }
        }

        $data = array(
            'overview' => $this->_parseReviewOverview($result, $productId),
            'reviews' => array(),
            'review_counter' => @$result['TotalResults']
        );

        $reviewForm = $this->getForm($product);
        if (!empty($reviewForm['reviews'])) {
            $fields = $reviewForm['reviews'];
        } else {
            $fields = array();
        }

        if (is_array($result['Results'])) {
            foreach ($result['Results'] as $item) {
                if ($review = $this->_parseReview($item, $fields)) {
                    $data['reviews'][] = $review;
                }
            }
        }

        return $data;
    }

    /**
     * Return review form fields
     *
     * @param Mage_Catalog_Model_Product|null $product
     * @return array
     * @throws Jmango360_Japi_Exception
     */
    public function getForm($product = null)
    {
        if (!$product || !$product->getId()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Product not found'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        $cacheKeys[] = self::CACHE_KEY_REVIEW_FORM;
        $cacheKeys[] = Mage::app()->getStore()->getId();
        //$cacheKeys[] = $product->getId();

        /* @var $session Mage_Customer_Model_Session */
        $session = Mage::getSingleton('customer/session');
        $cacheKeys[] = $session->isLoggedIn() ? 1 : 0;

        $cacheKey = implode('|', $cacheKeys);

        $cache = Mage::app()->getCache();
        if (!$cache->load($cacheKey)) {
            $productId = $this->getBvProductId($product);

            $apiKey = $this->_getApiKey();
            $url = $this->_getApiUrl('data/submitreview.json', array(
                'ApiVersion' => '5.4',
                'PassKey' => $apiKey,
                'ProductId' => $productId,
                'Locale' => Mage::app()->getLocale()->getLocaleCode(),
                'UserId' => $session->isLoggedIn() ? $session->getCustomerId() : null
            ));

            $result = $this->send('GET', $url);

            $data = array(
                'allow_guest_review' => true,
                'photo_review' => false,
                'video_review' => false,
                'api_key' => $apiKey,
                'photo_url' => $this->_getApiUrl('data/uploadphoto.json', array(
                    'ApiVersion' => '5.4',
                    'ContentType' => 'review'
                ))
            );

            if (!empty($result['Data']['Fields'])) {
                $outputFields = $this->FORM_FIELDS;
                if ($session->isLoggedIn()) {
                    foreach ($this->FORM_FIELDS as $fk => $fv) {
                        if (in_array($fk, $this->LOGGED_IN_EXCLUDE_FIELDS)) {
                            unset($outputFields[$fk]);
                        }
                    }
                }

                $fields = $result['Data']['Fields'];

                foreach ($fields as $field => $fieldData) {
                    if (strpos($field, 'photourl_') !== false) {
                        $data['photo_review'] = true;
                    }
                    if (strpos($field, 'videourl_') !== false) {
                        $data['video_review'] = true;
                    }
                }

                $index = 0;
                foreach ($outputFields as $field => $label) {
                    foreach ($fields as $fieldName => $fieldData) {
                        if ($field == $fieldName || (strpos($fieldName, $field) === 0 && $field != 'rating')) {
                            $fieldTmp = array(
                                'code' => $this->_getFormFieldCode(@$fieldData['Id']),
                                'title' => empty($fieldData['Label']) ? $this->__($label) : @$fieldData['Label'],
                                'type' => $this->_getFormFieldType(@$fieldData['Type']),
                                'required' => @$fieldData['Required'],
                                'id' => ++$index . "",
                                'bv_id' => @$fieldData['Id'],
                                'selected' => null,
                                'values' => array()
                            );

                            switch (@$fieldData['Type']) {
                                case 'SelectInput':
                                    if (!empty($fieldData['Options'])) {
                                        foreach (@$fieldData['Options'] as $option) {
                                            $fieldTmp['options'][] = array(
                                                'value' => @$option['Value'],
                                                'label' => @$option['Label']
                                            );
                                        }
                                    }
                                    break;
                                case 'IntegerInput':
                                    $fieldTmp['values'] = range(1, 5);
                                    break;
                                case 'TextInput':
                                case 'TextAreaInput':
                                    $fieldTmp['min_length'] = @$fieldData['MinLength'];
                                    $fieldTmp['max_length'] = @$fieldData['MaxLength'];
                                    break;
                            }

                            if (@$fieldData['Id'] == 'agreedtotermsandconditions') {
                                $html = Mage::getStoreConfig('japi/jmango_rest_bazaarvoice_settings/tc');
                                $html = str_replace("\n", '<br/>', $html);
                                $html = sprintf('<!DOCTYPE html><html><head><title>%s</title></head><body>%s</body></html>',
                                    $this->__('Terms & Conditions'),
                                    $html
                                );
                                $fieldTmp['html'] = $html;
                            }

                            if (@$fieldData['Id'] == 'useremail') {
                                if ($session->isLoggedIn()) {
                                    $fieldTmp['selected'] = $session->getCustomer()->getEmail();
                                }
                            }

                            $data['reviews'][] = $fieldTmp;
                        }
                    }
                }
            }

            $cache->save(Mage::helper('core')->jsonEncode($data), $cacheKey,
                array(Mage_Core_Model_Config::CACHE_TAG, strtoupper(Mage_Core_Block_Abstract::CACHE_GROUP)),
                60 * 60
            );
        }

        return Mage::helper('core')->jsonDecode($cache->load($cacheKey));
    }

    /**
     * Submit review for a particular product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     * @return array
     * @throws Jmango360_Japi_Exception
     */
    public function submitReview($product = null, $data = array())
    {
        if (!$product->getId()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Product not found'),
                Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
            );
        }

        $this->_log('SUBMIT ' . print_r($data, true));

        $productId = $this->getBvProductId($product);

        /* @var $session Mage_Customer_Model_Session */
        $session = Mage::getSingleton('customer/session');
        if ($session->isLoggedIn()) {
            $submitData['UserId'] = $session->getCustomer()->getId();
            $submitData['UserEmail'] = $session->getCustomer()->getEmail();
        } else {
            $submitData['UserId'] = uniqid();
            $submitData['UserEmail'] = @$data['useremail'];
        }

        $submitData = array_merge(array(
            'UserNickname' => @$data['usernickname'],
            'Title' => @$data['title'],
            'ReviewText' => @$data['reviewtext'],
            'Rating' => @$data['ratings']['rating'],
            'IsRecommended' => $this->_getBooleanValue(@$data['isrecommended']),
            'AgreedToTermsAndConditions' => $this->_getBooleanValue(@$data['agreedtotermsandconditions'])
        ), $submitData);

        if (!empty($data['ratings'])) {
            $reviewForm = $this->getForm($product);
            if (!empty($reviewForm['reviews'])) {
                $fields = $reviewForm['reviews'];
            } else {
                $fields = array();
            }
            foreach ($data['ratings'] as $rating => $value) {
                foreach ($fields as $field) {
                    if ($rating == $field['id']) {
                        if (strpos($field['bv_id'], 'rating') === 0) {
                            $submitData[str_replace('rating', 'Rating', $field['bv_id'])] = $value;
                        } elseif (strpos($field['bv_id'], 'contextdatavalue_') === 0) {
                            $submitData[str_replace('contextdatavalue_', 'ContextDataValue_', $field['bv_id'])] = $value;
                        }
                    }
                }
            }
        }

        foreach ($data as $key => $value) {
            if (strpos($key, 'PhotoUrl_') !== false
                || strpos($key, 'PhotoCaption_') !== false
                || strpos($key, 'VideoUrl_') !== false
                || strpos($key, 'VideoCaption_') !== false
                || ($key == 'fp' && $value)
            ) {
                $submitData[$key] = $value;
            }
        }

        $apiKey = $this->_getApiKey();
        $url = $this->_getApiUrl('data/submitreview.json');
        $submitData = array_merge(array(
            'apiVersion' => '5.4',
            'passkey' => $apiKey,
            'Action' => 'Submit',
            'ProductId' => $productId,
            'Locale' => Mage::app()->getLocale()->getLocaleCode()
        ), $submitData);

        $result = $this->send('POST', $url, $submitData);

        if (!empty($result['HasErrors'])) {
            if (!empty($result['Errors'])) {
                foreach ($result['Errors'] as $error) {
                    throw new Jmango360_Japi_Exception(
                        sprintf('%s', $error['Message']),
                        Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                    );
                }
            } elseif (!empty($result['FormErrors']['FieldErrors'])) {
                $messages = array();
                foreach ($result['FormErrors']['FieldErrors'] as $formError) {
                    $messages[] = sprintf('%s', $formError['Message']);
                }
                if (count($messages)) {
                    throw new Jmango360_Japi_Exception(
                        join("\n", $messages),
                        Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                    );
                }
            } else {
                throw new Jmango360_Japi_Exception(
                    Mage::helper('japi')->__('An error has occurred, please try again later.'),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }
        }

        return array('success' => Mage::helper('review')->__('Your review has been accepted for moderation.'));
    }

    /**
     * Convert boolean value to string
     *
     * @param $value
     * @return string
     */
    protected function _getBooleanValue($value)
    {
        return $value ? ($value == 'false' ? 'false' : 'true') : 'false';
    }

    /**
     * Reset rating, rating_*, contextdatavalue_* field to ratings field as Magento review code
     *
     * @param $code
     * @return string
     */
    protected function _getFormFieldCode($code)
    {
        if (strpos($code, 'rating') === 0 || strpos($code, 'contextdatavalue_') === 0) {
            return 'ratings';
        }
        return $code;
    }

    /**
     * Convert to mobile supportted field type
     *
     * @param $bvType
     * @return string
     */
    protected function _getFormFieldType($bvType)
    {
        switch ($bvType) {
            case 'SelectInput':
                return 'select';
                break;
            case 'IntegerInput':
                return 'radio';
                break;
            case 'TextAreaInput':
                return 'area';
                break;
            case 'BooleanInput':
                return 'boolean';
                break;
            default:
            case 'TextInput':
                return 'field';
                break;
        }
    }

    protected function _parseReviewOverview($result = array(), $productId = null)
    {
        $data = array();
        if (!empty($result['Includes']['Products'][$productId]['ReviewStatistics']['SecondaryRatingsAverages'])) {
            $reviewStatistics = $result['Includes']['Products'][$productId]['ReviewStatistics']['SecondaryRatingsAverages'];
            foreach ($reviewStatistics as $reviewStatistic) {
                $data[] = array(
                    'title' => @$reviewStatistic['Label'],
                    'code' => @$reviewStatistic['Id'],
                    'type' => 'overview',
                    'percent' => round(100 * @$reviewStatistic['AverageRating'] / (empty($reviewStatistic['ValueRange']) ? 5 : $reviewStatistic['ValueRange']))
                );
            }
        }
        return $data;
    }

    protected function _parseReview($result = array(), $fields = array())
    {
        if (!$result || !is_array($result)) return;
        if (!empty($result['ModerationStatus']) && $result['ModerationStatus'] != 'APPROVED') return;

        $data = array(
            'nickname' => @$result['UserNickname'],
            'create_at' => $this->_getDatetimeValue(@$result['SubmissionTime']),
            'review' => array()
        );

        $data['review'][] = array(
            'code' => 'ratings',
            'title' => $this->__($this->FORM_FIELDS['rating']),
            'type' => 'radio',
            'required' => true,
            'id' => "1",
            'values' => range(1, empty($result['RatingRange']) ? 5 : $result['RatingRange']),
            'selected' => @$result['Rating'],
            'percent' => round(100 * $result['Rating'] / (empty($result['RatingRange']) ? 5 : $result['RatingRange']))
        );

        $index = 1;
        if (!empty($result['SecondaryRatings'])) {
            foreach ($result['SecondaryRatings'] as $rating) {
                $data['review'][] = array(
                    'code' => 'ratings',
                    'title' => @$rating['Label'],
                    'type' => 'radio',
                    'required' => false,
                    'id' => ++$index . "",
                    'values' => range(1, empty($rating['ValueRange']) ? 5 : $rating['ValueRange']),
                    'selected' => @$rating['Value'],
                    'percent' => round(100 * $rating['Value'] / (empty($rating['ValueRange']) ? 5 : $rating['ValueRange']))
                );
            }
        }

        if (!empty($result['ContextDataValues'])) {
            foreach ($result['ContextDataValues'] as $key => $context) {
                $dataTmp = array(
                    'code' => 'ratings',
                    'title' => @$context['DimensionLabel'],
                    'type' => null,
                    'required' => false,
                    'id' => ++$index . "",
                    'selected' => @$context['Value']
                );
                foreach ($fields as $field) {
                    if ($field['bv_id'] == 'contextdatavalue_' . $key) {
                        $dataTmp['title'] = $field['title'];
                        $dataTmp['type'] = $field['type'];
                        $dataTmp['required'] = $field['required'];
                        $dataTmp['values'] = $field['values'];
                        $dataTmp['options'] = $field['options'];
                    }
                }
                $data['review'][] = $dataTmp;
            }
        }

        if (!empty($result['Photos'])) {
            $photoReview = array(
                'code' => 'photo',
                'title' => $this->__('Photos'),
                'type' => 'photo',
                'required' => false,
                'photos' => array()
            );
            foreach ($result['Photos'] as $photo) {
                $item = array(
                    'id' => @$photo['Id'],
                    'caption' => @$photo['Caption']
                );
                if (!empty($photo['Sizes'])) {
                    foreach ($photo['Sizes'] as $size) {
                        $item[@$size['Id']] = @$size['Url'];
                    }
                }
                $photoReview['photos'][] = $item;
            }
            $data['review'][] = $photoReview;
        }

        if (!empty($result['Videos'])) {
            $videoReview = array(
                'code' => 'video',
                'title' => $this->__('Videos'),
                'type' => 'video',
                'required' => false,
                'videos' => array()
            );
            foreach ($result['Videos'] as $video) {
                $videoReview['videos'][] = array(
                    'video_id' => @$video['VideoId'],
                    'video_host' => @$video['VideoHost'],
                    'video_thumbnail_url' => @$video['VideoThumbnailUrl'],
                    'video_iframe_url' => @$video['VideoIframeUrl'],
                    'caption' => @$video['Caption'],
                    'video_url' => @$video['VideoUrl']
                );
            }
            $data['review'][] = $videoReview;
        }

        if (isset($result['IsRecommended'])) {
            $isRecommended = array(
                'code' => 'isrecommended',
                'title' => @$result['IsRecommended'] ? $this->__('I recommend this product') : $this->__('I do not recommend this product'),
                'type' => 'boolean',
                'required' => false,
                'selected' => @$result['IsRecommended']
            );
            foreach ($fields as $field) {
                if ($field['bv_id'] == 'isrecommended') {
                    $isRecommended['type'] = $field['type'];
                    $isRecommended['required'] = $field['required'];
                    $isRecommended['values'] = $field['values'];
                }
            }
            $data['review'][] = $isRecommended;
        }

        $data['review'] = array_merge($data['review'], array(
            array(
                'code' => 'nickname',
                'title' => $this->__('Nickname'),
                'type' => 'field',
                'required' => true,
                'selected' => @$result['UserNickname']
            ),
            array(
                'code' => 'title',
                'title' => $this->__('Review Title'),
                'type' => 'field',
                'required' => true,
                'selected' => @$result['Title']
            ),
            array(
                'code' => 'detail',
                'title' => $this->__('Review'),
                'type' => 'area',
                'required' => true,
                'selected' => @$result['ReviewText']
            )
        ));

        return $data;
    }

    protected function _getDatetimeValue($value)
    {
        $storeTimestamp = Mage::getModel('core/date')->timestamp($value);
        return date('Y-n-j G:i:s', $storeTimestamp);
    }

    protected function _log($data)
    {
        if (Mage::getStoreConfigFlag('japi/jmango_rest_developer_settings/enable')) {
            Mage::log($data, null, 'japi_bazaarvoice.log');
        }
    }

    protected function _getReviewsOffset()
    {
        $p = $this->_getRequest()->getParam('p', 1);
        $p = is_numeric($p) ? (int)$p : 1;
        return $p <= 1 ? 0 : ($p - 1) * $this->_getReviewsLimit();
    }

    protected function _getReviewsLimit()
    {
        $limit = $this->_getRequest()->getParam('limit', self::DEFAULT_LIMIT);
        return is_numeric($limit) ? (int)$limit : self::DEFAULT_LIMIT;
    }

    protected function _getReviewsSort()
    {
        return sprintf('%s:%s', self::DEFAULT_SORT, self::DEFAULT_DIR);
    }

    protected function _getApiUrl($uri = null, $params = array())
    {
        $env = $this->_getEnv();
        $baseUrl = $env == 'staging' ? self::URL_STAGING : self::URL_PRODUCTION;
        if (!empty($params)) {
            return sprintf('%s%s?%s', $baseUrl, $uri, http_build_query($params));
        } else {
            return sprintf('%s%s', $baseUrl, $uri);
        }
    }

    protected function _getEnv()
    {
        $env = Mage::getStoreConfig('japi/jmango_rest_bazaarvoice_settings/env');
        return $env ? $env : 'staging';
    }

    protected function _getApiKey()
    {
        $apiKey = null;

        if ($this->_getEnv() == 'staging') {
            $apiKey = Mage::getStoreConfig('japi/jmango_rest_bazaarvoice_settings/api_key_stg');
        } elseif ($this->_getEnv() == 'production') {
            $apiKey = Mage::getStoreConfig('japi/jmango_rest_bazaarvoice_settings/api_key_prod');
        }

        if (!$apiKey) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Invalid API Key value'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        return $apiKey;
    }

    protected function send($method = 'GET', $url = '', $params = array())
    {
        $this->_log(sprintf('%s %s', $method, $url));
        $this->_log('PARAMS ' . print_r($params, true));

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $result = curl_exec($ch);
        $errorNum = curl_errno($ch);
        $errorMeg = curl_error($ch);

        curl_close($ch);

        if (!$result) {
            $errorStr = sprintf('%s', $errorMeg);

            $this->_log($errorStr);

            throw new Jmango360_Japi_Exception(
                $errorStr,
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        } else {
            $this->_log('RESPONSE ' . $result);
        }

        return $this->_parseResponse($result);
    }

    protected function _parseResponse($rawContent)
    {
        return Mage::helper('core')->jsonDecode($rawContent);
    }
}