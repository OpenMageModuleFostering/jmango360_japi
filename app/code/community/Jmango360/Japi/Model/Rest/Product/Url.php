<?php

class Jmango360_Japi_Model_Rest_Product_Url extends Jmango360_Japi_Model_Rest_Product
{
    public function getProductId()
    {
        $url = $this->_getRequest()->getParam('url', null);
        if ($url) {
            $request = new Mage_Core_Controller_Request_Http($url);
            if (Mage::getEdition() == Mage::EDITION_ENTERPRISE) {
                /* @var $rewrite Enterprise_UrlRewrite_Model_Url_Rewrite */
                $rewrite = Mage::getModel('enterprise_urlrewrite/url_rewrite');

                $requestPath = $this->_getRequestPath($request);
                $paths = $this->_getSystemPaths($requestPath);
                if (count($paths)) {
                    $rewrite->loadByRequestPath($paths);
                    if ($rewrite->getId()) {
                        /**
                         * Reinit $request so we can check with new url
                         * because EE not store product ID in rewrite table
                         */
                        $request = new Mage_Core_Controller_Request_Http(Mage::getUrl($rewrite->getTargetPath()));
                    }
                }
            } else {
                /* @var $rewrite Mage_Core_Model_Url_Rewrite */
                $rewrite = Mage::getModel('core/url_rewrite');
                $rewrite->setStoreId(Mage::app()->getStore()->getId());

                $requestCases = $this->_getRequestCases($request);
                $rewrite->loadByRequestPath($requestCases);
            }

            if ($rewrite->getId() && $rewrite->getProductId()) {
                return array('product' => $this->_getProductData($rewrite->getProductId()));
            } else {
                $routers = array();

                $routersInfo = Mage::app()->getStore()->getConfig(Mage_Core_Controller_Varien_Front::XML_STORE_ROUTERS_PATH);
                $front = new Mage_Core_Controller_Varien_Front();
                foreach ($routersInfo as $routerCode => $routerInfo) {
                    if (isset($routerInfo['class']) && $routerInfo['class'] == 'Mage_Core_Controller_Varien_Router_Standard') {
                        /* @var $router Mage_Core_Controller_Varien_Router_Abstract */
                        $router = new $routerInfo['class'];
                        $router->setFront($front);
                        if (isset($routerInfo['area'])) {
                            $router->collectRoutes($routerInfo['area'], $routerCode);
                        }
                        $routers[$routerCode] = $router;
                    }
                }

                /**
                 * Fake a POST request to prevent "_checkShouldBeSecure" return response
                 * it will break our logic
                 */
                $request->setPost('xxx', 1);
                $request->setPathInfo()->setDispatched(false);
                $i = 0;
                while (!$request->isDispatched() && $i++ < 100) {
                    foreach ($routers as $router) {
                        /** @var $router Mage_Core_Controller_Varien_Router_Abstract */
                        if ($router->match($request)) {
                            break;
                        }
                    }
                }

                if ($request->isDispatched()
                    && $request->getModuleName() == 'catalog'
                    && $request->getControllerName() == 'product'
                    && $request->getActionName() == 'view'
                ) {
                    return array('product' => $this->_getProductData($request->getParam('id')));
                }
            }
        }

        return array('product' => null);
    }

    /**
     * Return product data
     *
     * @param $productId
     * @return array|null
     */
    protected function _getProductData($productId)
    {
        if (!$productId) return null;
        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');
        return $helper->convertProductIdToApiResponseV2($productId);
    }

    /**
     * Get system path from request path
     * (Copied from EE)
     *
     * @param string $requestPath
     * @return array
     */
    protected function _getSystemPaths($requestPath)
    {
        $parts = explode('/', $requestPath);
        $suffix = array_pop($parts);
        if (false !== strrpos($suffix, '.')) {
            $suffix = substr($suffix, 0, strrpos($suffix, '.'));
        }
        $paths = array('request' => $requestPath, 'suffix' => $suffix);
        if (count($parts)) {
            $paths['whole'] = implode('/', $parts) . '/' . $suffix;
        }

        return $paths;
    }

    /**
     * Get request path from requested path info
     * (Copied from EE)
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @return string
     */
    protected function _getRequestPath($request)
    {
        $pathInfo = $request->getPathInfo();
        $requestPath = trim($pathInfo, '/');

        return $requestPath;
    }

    /**
     * Prepare request cases.
     *
     * We have two cases of incoming paths - with and without slashes at the end ("/somepath/" and "/somepath").
     * Each of them matches two url rewrite request paths
     * - with and without slashes at the end ("/somepath/" and "/somepath").
     * Choose any matched rewrite, but in priority order that depends on same presence of slash and query params.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @return array
     */
    protected function _getRequestCases($request)
    {
        $pathInfo = $request->getPathInfo();
        $requestPath = trim($pathInfo, '/');
        $origSlash = (substr($pathInfo, -1) == '/') ? '/' : '';
        // If there were final slash - add nothing to less priority paths. And vice versa.
        $altSlash = $origSlash ? '' : '/';

        $requestCases = array();
        // Query params in request, matching "path + query" has more priority
        $queryString = $this->_getQueryString();
        if ($queryString) {
            $requestCases[] = $requestPath . $origSlash . '?' . $queryString;
            $requestCases[] = $requestPath . $altSlash . '?' . $queryString;
        }
        $requestCases[] = $requestPath . $origSlash;
        $requestCases[] = $requestPath . $altSlash;

        return $requestCases;
    }

    /**
     * Prepare and return QUERY_STRING
     *
     * @return bool|string
     */
    protected function _getQueryString()
    {
        if (!empty($_SERVER['QUERY_STRING'])) {
            $queryParams = array();
            parse_str($_SERVER['QUERY_STRING'], $queryParams);
            $hasChanges = false;
            foreach ($queryParams as $key => $value) {
                if (substr($key, 0, 3) === '___') {
                    unset($queryParams[$key]);
                    $hasChanges = true;
                }
            }
            if ($hasChanges) {
                return http_build_query($queryParams);
            } else {
                return $_SERVER['QUERY_STRING'];
            }
        }

        return false;
    }
}