if (typeof KlarnaResponsive != "undefined") {
    /**
     * Gets the sidebar children elements as an object
     *
     * @param sidebarEl (optional)
     * @param getGroup
     * @returns {{cart: HTMLElement, shipping: HTMLElement, discount: HTMLElement}}
     */
    KlarnaResponsive.prototype.getSidebarElements = function (sidebarEl, getGroup) {
        var ref = sidebarEl || document,
            cartEl = document.getElementById('klarna_cart-container') ? document.getElementById('klarna_cart-container') : ref.querySelector('#klarna_cart-container'),
            shippingEl = document.getElementById('klarna_shipping') ? document.getElementById('klarna_shipping') : ref.querySelector('#klarna_shipping'),
            discountEl = document.getElementById('klarna_discount') ? document.getElementById('klarna_discount') : ref.querySelector('#klarna_discount'),
            groupedEls = {
                discount: discountEl,
                shipping: shippingEl,
                cart: cartEl
            },
            sidebarEls = groupedEls;

        sidebarEls.payment = document.getElementById('klarna_methods') ? document.getElementById('klarna_methods') : ref.querySelector('#klarna_methods');

        return getGroup ? groupedEls : sidebarEls;
    };

    KlarnaResponsive.prototype.setMobileLayout = function (el) {
        console.log('Override KlarnaResponsive:setMobileLayout');

        var groupedEls = this.getSidebarElements(el, true),
            sidebarEls = this.getSidebarElements(el),
            mainContentEl = document.getElementById('klarna_main'),
            iframeEl = document.getElementById('klarna_checkout'),
            tempEl = document.createDocumentFragment();

        for (var key in groupedEls) {
            if (groupedEls.hasOwnProperty(key) && groupedEls[key] != null) {
                tempEl.appendChild(groupedEls[key]);
            }
        }

        mainContentEl.insertBefore(tempEl, iframeEl);
        if (sidebarEls.payment) {
            mainContentEl.appendChild(sidebarEls.payment);
        }
    };
}

function japiKlarnaToggleOrderSummary(flag, button) {
    var $container = $$('.klarna_cart_wrapper')[0];
    if (!$container) return;
    var $button = $(button);
    $button.hide();
    $button.siblings().each(function (el) {
        $(el).show();
    });
    if (flag) {
        $container.addClassName('klarna_compact');
    } else {
        $container.removeClassName('klarna_compact');
    }
}