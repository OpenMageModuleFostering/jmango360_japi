<?php

/**
 * IFrame So Colissimo
 *
 * @category  LaPoste
 * @package   LaPoste_SoColissimoSimplicite
 * @copyright Copyright (c) 2010 La Poste
 * @author    Smile (http://www.smile.fr)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Jmango360_Japi_Block_Socolissimosimplicite_Iframe extends LaPoste_SoColissimoSimplicite_Block_Iframe
{
    /**
     * Retourne le contenu html de l'étape shipping_method
     *
     * @return string
     */
    public function getShippingMethodHtml()
    {
        $html = parent::getShippingMethodHtml();
        return str_replace('\\"', '\\\"', $html);
    }

    /**
     * Retourn le contenu html de l'étape payment
     *
     * @return string
     */
    public function getPaymentHtml()
    {
        $html = parent::getPaymentHtml();
        return str_replace('\\"', '\\\"', $html);
    }
}
