<?php

class Jmango360_Japi_Model_Rest_Wishlist extends Mage_Core_Model_Abstract
{
    /**
     * Filter to convert localized values to internal ones
     * @var Zend_Filter_LocalizedToNormalized
     */
    protected $_localFilter = null;

    protected $_wishlist;

    public function dispatch()
    {
        $action = $this->_getRequest()->getAction();
        $operation = $this->_getRequest()->getOperation();

        switch ($action . $operation) {
            case 'getItems' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getItems();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'add' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $data = $this->_addItem();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'update' . Jmango360_Japi_Model_Request::OPERATION_UPDATE:
                $data = $this->_updateItem();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'remove' . Jmango360_Japi_Model_Request::OPERATION_DELETE:
                $data = $this->_removeItem();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'updateItemOptions' . Jmango360_Japi_Model_Request::OPERATION_UPDATE:
                $data = $this->_updateItemOptions();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            default:
                throw new Jmango360_Japi_Exception(
                    Mage::helper('japi')->__('Resource method not implemented'),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
                break;
        }
    }

    protected function _updateItemOptions()
    {
        $wishlist = $this->_getWishlist();
        $productId = (int)$this->_getRequest()->getParam('product');
        $product = Mage::getModel('catalog/product')->load($productId);
        if (!$product->getId() || !$product->isVisibleInCatalog()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('wishlist')->__('Cannot specify product.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        try {
            $id = (int)$this->_getRequest()->getParam('id');
            /* @var $item Mage_Wishlist_Model_Item */
            $item = Mage::getModel('wishlist/item')->load($id);

            if (!$item->getId()) {
                throw new Jmango360_Japi_Exception(
                    Mage::helper('wishlist')->__('Item not found'),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }

            $buyRequest = new Varien_Object($this->_getRequest()->getParams());
            $result = $this->updateWishlistItem($wishlist, $id, $buyRequest);

            Mage::helper('wishlist')->calculate();
            Mage::dispatchEvent('wishlist_update_item', array(
                'wishlist' => $wishlist, 'product' => $product, 'item' => $wishlist->getItem($id)
            ));
            Mage::helper('wishlist')->calculate();

            $data = array(
                'messages' => array(
                    'success' => array(
                        array(
                            'code' => Jmango360_Japi_Model_Request::HTTP_OK,
                            'message' => Mage::helper('japi')->__('%1$s has been updated in your wishlist.', $product->getName())
                        )
                    )
                ),
                'item' => $this->_convertWishlistItemToApiResponse($result)
            );
        } catch (Jmango360_Japi_Exception $e) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__($e->getMessage()),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        } catch (Exception $e) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('An error occurred while updating wishlist.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        return $data;
    }

    /**
     * Update wishlist Item and set data from request
     *
     * $params sets how current item configuration must be taken into account and additional options.
     * It's passed to Mage_Catalog_Helper_Product->addParamsToBuyRequest() to compose resulting buyRequest.
     *
     * Basically it can hold
     * - 'current_config', Varien_Object or array - current buyRequest that configures product in this item,
     *   used to restore currently attached files
     * - 'files_prefix': string[a-z0-9_] - prefix that was added at frontend to names of file options (file inputs), so they won't
     *   intersect with other submitted options
     *
     * For more options see Mage_Catalog_Helper_Product->addParamsToBuyRequest()
     *
     * @param Mage_Wishlist_Model_Wishlist $wishlist
     * @param int|Mage_Wishlist_Model_Item $itemId
     * @param Varien_Object $buyRequest
     * @param null|array|Varien_Object $params
     * @return null|Mage_Wishlist_Model_Item
     *
     * @see Mage_Catalog_Helper_Product::addParamsToBuyRequest()
     */
    protected function updateWishlistItem($wishlist, $itemId, $buyRequest, $params = null)
    {
        $item = null;
        if ($itemId instanceof Mage_Wishlist_Model_Item) {
            $item = $itemId;
        } else {
            $item = $wishlist->getItem((int)$itemId);
        }
        if (!$item) {
            Mage::throwException(Mage::helper('wishlist')->__('Cannot specify your wishlist item.'));
        }

        $product = $item->getProduct();
        $productId = $product->getId();
        if ($productId) {
            if (!$params) {
                $params = new Varien_Object();
            } else if (is_array($params)) {
                $params = new Varien_Object($params);
            }
            $params->setCurrentConfig($item->getBuyRequest());
            $buyRequest = Mage::helper('catalog/product')->addParamsToBuyRequest($buyRequest, $params);

            $product->setWishlistStoreId($item->getStoreId());
            $items = $wishlist->getItemCollection();
            $isForceSetQuantity = true;
            foreach ($items as $_item) {
                /* @var $_item Mage_Wishlist_Model_Item */
                if ($_item->getProductId() == $product->getId()
                    && $_item->representProduct($product)
                    && $_item->getId() != $item->getId()
                ) {
                    // We do not add new wishlist item, but updating the existing one
                    $isForceSetQuantity = false;
                }
            }
            $resultItem = $wishlist->addNewItem($product, $buyRequest, $isForceSetQuantity);
            /**
             * Error message
             */
            if (is_string($resultItem)) {
                Mage::throwException(Mage::helper('checkout')->__($resultItem));
            }

            if ($resultItem->getId() != $itemId) {
                if ($resultItem->getDescription() != $item->getDescription()) {
                    $resultItem->setDescription($item->getDescription())->save();
                }
                $item->isDeleted(true);
                $wishlist->setDataChanges(true);
            } else {
                $resultItem->setQty($buyRequest->getQty() * 1);
                $resultItem->setOrigData('qty', 0);
            }

            $wishlist->save();
            return $resultItem;
        } else {
            Mage::throwException(Mage::helper('checkout')->__('The product does not exist.'));
        }
    }

    protected function _removeItem()
    {
        $wishlist = $this->_getWishlist();

        $id = (int)$this->_getRequest()->getParam('item');
        $item = Mage::getModel('wishlist/item')->load($id);
        if (!$item->getId()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('wishlist')->__('Item not found'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        /* @var $helper Mage_Wishlist_Helper_Data */
        $helper = Mage::helper('wishlist');

        try {
            $item->delete();
            $wishlist->save();
            $data = array(
                'messages' => array(
                    'success' => array(
                        array(
                            'code' => Jmango360_Japi_Model_Request::HTTP_OK,
                            'message' => Mage::helper('japi')->__('Wishlist item removed')
                        )
                    )
                ),
                'items' => $this->_getWishlistData()
            );
        } catch (Mage_Core_Exception $e) {
            throw new Jmango360_Japi_Exception(
                $helper->__('An error occurred while deleting the item from wishlist: %s', $e->getMessage()),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        } catch (Exception $e) {
            throw new Jmango360_Japi_Exception(
                $helper->__('An error occurred while deleting the item from wishlist.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        $helper->calculate();

        return $data;
    }

    protected function _updateItem()
    {
        $wishlist = $this->_getWishlist();
        $post = $this->_getRequest()->getParams();
        $updatedItems = 0;

        if ($post && isset($post['description']) && is_array($post['description'])) {
            foreach ($post['description'] as $itemId => $description) {
                /* @var $item Mage_Wishlist_Model_Item */
                $item = Mage::getModel('wishlist/item')->load($itemId);
                if ($item->getWishlistId() != $wishlist->getId()) {
                    continue;
                }

                // Extract new values
                $description = (string)$description;

                // Check that we need to save
                if ($item->getDescription() == $description) {
                    continue;
                }

                try {
                    $item->setDescription($description)->save();
                    $updatedItems++;
                } catch (Exception $e) {
                    throw new Jmango360_Japi_Exception(
                        Mage::helper('wishlist')->__(
                            'Can\'t save description %s',
                            Mage::helper('core')->escapeHtml($description)
                        ),
                        Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                    );
                }
            }
        }

        if ($post && isset($post['qty']) && is_array($post['qty'])) {
            foreach ($post['qty'] as $itemId => $qty) {
                /* @var $item Mage_Wishlist_Model_Item */
                $item = Mage::getModel('wishlist/item')->load($itemId);
                if ($item->getWishlistId() != $wishlist->getId()) {
                    continue;
                }

                // Extract new values
                $qty = $this->_processLocalizedQty($qty);

                if (is_null($qty)) {
                    $qty = $item->getQty();
                    if (!$qty) {
                        $qty = 1;
                    }
                } elseif (0 == $qty) {
                    try {
                        $item->delete();
                    } catch (Exception $e) {
                        throw new Jmango360_Japi_Exception(
                            Mage::helper('wishlist')->__('Can\'t delete item from wishlist'),
                            Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                        );
                    }
                }

                // Check that we need to save
                if ($item->getQty() == $qty) {
                    continue;
                }

                try {
                    $item->setQty($qty)->save();
                    $updatedItems++;
                } catch (Exception $e) {
                    throw new Jmango360_Japi_Exception(
                        Mage::helper('wishlist')->__(
                            'Can\'t save qty %s',
                            Mage::helper('core')->escapeHtml($qty)
                        ),
                        Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                    );
                }
            }
        }

        // save wishlist model for setting date of last update
        if ($updatedItems) {
            try {
                $wishlist->save();
                Mage::helper('wishlist')->calculate();
                $data = array(
                    'messages' => array(
                        'success' => array(
                            array(
                                'code' => Jmango360_Japi_Model_Request::HTTP_OK,
                                'message' => Mage::helper('wishlist')->__('Wishlist updated successfully')
                            )
                        )
                    )
                );
            } catch (Exception $e) {
                throw new Jmango360_Japi_Exception(
                    Mage::helper('wishlist')->__('Can\'t update wishlist'),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }
        } else {
            $data = array(
                'messages' => array(
                    'success' => array(
                        array(
                            'code' => Jmango360_Japi_Model_Request::HTTP_OK,
                            'message' => Mage::helper('wishlist')->__('No item updated')
                        )
                    )
                )
            );
        }

        return $data;
    }

    /**
     * Processes localized qty (entered by user at frontend) into internal php format
     *
     * @param string $qty
     * @return float|int|null
     */
    protected function _processLocalizedQty($qty)
    {
        if (!$this->_localFilter) {
            $this->_localFilter = new Zend_Filter_LocalizedToNormalized(
                array('locale' => Mage::app()->getLocale()->getLocaleCode())
            );
        }
        $qty = $this->_localFilter->filter((float)$qty);
        if ($qty < 0) {
            $qty = null;
        }
        return $qty;
    }

    protected function _addItem()
    {
        $wishlist = $this->_getWishlist();

        $productId = (int)$this->_getRequest()->getParam('product');
        /* @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product')->load($productId);
        if (!$product->getId()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Cannot specify product'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        /* @var $helper Mage_Wishlist_Helper_Data */
        $helper = Mage::helper('wishlist');

        try {
            $requestParams = $this->_getRequest()->getParams();
            $buyRequest = new Varien_Object($requestParams);

            $result = $wishlist->addNewItem($product, $buyRequest);
            if (is_string($result)) {
                throw new Jmango360_Japi_Exception($result, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            }
            $wishlist->save();

            Mage::dispatchEvent(
                'wishlist_add_product',
                array(
                    'wishlist' => $wishlist,
                    'product' => $product,
                    'item' => $result
                )
            );

            $helper->calculate();
            $data = array(
                'messages' => array(
                    'success' => array(
                        array(
                            'code' => Jmango360_Japi_Model_Request::HTTP_CREATED,
                            'message' => $helper->__('%s has been added to your wishlist.', $product->getName())
                        )
                    )
                ),
                'item' => $this->_convertWishlistItemToApiResponse($result)
            );
        } catch (Mage_Core_Exception $e) {
            throw new Jmango360_Japi_Exception(
                $helper->__('An error occurred while adding item to your wishlist: %s', $e->getMessage()),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        } catch (Exception $e) {
            throw new Jmango360_Japi_Exception(
                $helper->__('An error occurred while adding item to your wishlist'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        return $data;
    }

    protected function _getItems()
    {
        $page = (int)$this->_getRequest()->getParam('p', 1);
        $page = $page > 0 ? $page : 1;
        $limit = (int)$this->_getRequest()->getParam('limit', 10);
        return array('items' => $this->_getWishlistData($page, $limit));
    }

    protected function _getWishlistData($page = 1, $limit = 10)
    {
        $wishlist = $this->_getWishlist();
        $items = $this->_getWishlistItems($wishlist, $page, $limit);

        $data = array();
        foreach ($items as $item) {
            /* @var $item Mage_Wishlist_Model_Item */
            $data[] = $this->_convertWishlistItemToApiResponse($item);
        }

        return $data;
    }

    protected function _convertWishlistItemToApiResponse(Mage_Wishlist_Model_Item $item)
    {
        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');
        $data = $item->toArray();
        // MPLUGIN-758: Grouped product should return qty 1
        if ($item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type_Grouped::TYPE_CODE) {
            $data['qty'] = 1;
        }
        $data['price'] = $item->getProduct()->getFinalPrice();
        $data['options'] = $this->_getWishlistItemOptions($item);
        $data['product'] = $helper->convertProductIdToApiResponse($item->getProduct()->getId());
        if (is_array($data['product']) && $data['product']['type_id'] == 'configurable_scp') {
            $data['product']['type'] = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
            $data['product']['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
        }
        return $data;
    }

    protected function _getWishlistItemOptions(Mage_Wishlist_Model_Item $item)
    {
        $options = array();
        foreach ($item->getOptions() as $option) {
            if ($option->getCode() != 'info_buyRequest') {
                continue;
            }

            $data = $option->toArray();
            unset($data['option_id']);
            unset($data['code']);

            try {
                $value = unserialize($option->getValue());
                $data['value'] = $this->_processItemOptions($value, $item);
            } catch (Exception $e) {
                Mage::logException($e);
                $data['value'] = new stdClass();
            }

            $options = $data;
        }

        return empty($options) ? new stdClass() : $options;
    }

    protected function _processItemOptions($options = array(), Mage_Wishlist_Model_Item $item)
    {
        switch ($item->getProduct()->getTypeId()) {
            case 'bundle':
                if (isset($options['bundle_option']) && is_array($options['bundle_option'])) {
                    foreach ($options['bundle_option'] as $key => $value) {
                        if (empty($value)) {
                            unset($options['bundle_option'][$key]);
                        } elseif (!is_array($value)) {
                            $options['bundle_option'][$key] = array($value);
                        }
                    }
                }
                $options['bundle_option'] = empty($options['bundle_option']) ? new stdClass() : $options['bundle_option'];
                break;
            case 'grouped':
                $options['super_group'] = empty($options['super_group']) ? new stdClass() : $options['super_group'];
                break;
            case 'configurable':
                $options['super_attribute'] = empty($options['super_attribute']) ? new stdClass() : $options['super_attribute'];
                break;
        }

        if (isset($options['options'])) {
            foreach ($options['options'] as $key => $value) {
                if (empty($value)) {
                    unset($options['options'][$key]);
                } elseif (!is_array($value)) {
                    $options['options'][$key] = array($value);
                } elseif (isset($value['hour']) || isset($value['day']) || isset($value['type'])) {
                    unset($options['options'][$key]);
                }
            }
            $options['options'] = empty($options['options']) ? new stdClass() : $options['options'];
        }

        return $options;
    }

    /**
     * @param Mage_Wishlist_Model_Wishlist $wishlist
     * @return Mage_Wishlist_Model_Resource_Item_Collection
     */
    protected function _getItemCollection(Mage_Wishlist_Model_Wishlist $wishlist)
    {
        return Mage::getResourceModel('wishlist/item_collection')
            ->addWishlistFilter($wishlist)
            ->addStoreFilter($wishlist->getStore()->getId())
            ->setVisibilityFilter();
    }

    protected function _getWishlistItems(Mage_Wishlist_Model_Wishlist $wishlist, $page = 1, $limit = 10)
    {
        if (!$wishlist) return null;

        /* @var $collection Mage_Wishlist_Model_Resource_Item_Collection */
        $collection = $wishlist->getItemCollection()
            ->setInStockFilter(true)
            ->setOrder('added_at', 'DESC');

        /* @var $resource Mage_Core_Model_Resource */
        $resource = Mage::getSingleton('core/resource');
        $collection->getSelect()
            ->join(
                array('catalog_product' => $resource->getTableName('catalog/product')),
                sprintf(
                    'main_table.product_id = catalog_product.entity_id AND catalog_product.type_id IN (%s)',
                    join(',', array('"simple"', '"configurable"', '"grouped"', '"bundle"'))
                ),
                array('type_id')
            );

        $collection->getSelect()->limitPage($page, $limit);
        $collection->setCurPage($page);

        return $collection;
    }

    protected function _getWishlist($customerId = null)
    {
        if (!$this->_isAllow()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Wishlist not allowed'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        if (!$this->_wishlist) {
            if (!$customerId) {
                $customerId = $this->_getCustomerId();
            }

            /* @var Mage_Wishlist_Model_Wishlist $wishlist */
            $wishlist = Mage::getModel('wishlist/wishlist');
            $wishlist->loadByCustomer($customerId, true);

            if (!$wishlist->getId() || $wishlist->getCustomerId() != $customerId) {
                throw new Jmango360_Japi_Exception(
                    Mage::helper('japi')->__("Wishlist doesn't exist"),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }

            $this->_wishlist = $wishlist;
        }

        return $this->_wishlist;
    }

    protected function _isAllow()
    {
        return Mage::helper('wishlist')->isAllow();
    }

    protected function _getCustomerId()
    {
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        if (!$customerId) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Customer not logged in'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }
        return $customerId;
    }

    /**
     * @return Jmango360_Japi_Model_Request
     */
    protected function _getRequest()
    {
        return $this->_getServer()->getRequest();
    }

    /**
     * @return Jmango360_Japi_Model_Response
     */
    protected function _getResponse()
    {
        return $this->_getServer()->getResponse();
    }

    /**
     * @return Jmango360_Japi_Model_Server
     */
    protected function _getServer()
    {
        return Mage::helper('japi')->getServer();
    }
}
