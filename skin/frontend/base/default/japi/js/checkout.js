/**
 * Copyright 2015 JMango360
 */

if (!window.toogleVisibilityOnObjects) {
    var toogleVisibilityOnObjects = function (source, objects) {
        if ($(source) && $(source).checked) {
            objects.each(function (item) {
                if ($(item)) {
                    $(item).show();
                    $$('#' + item + ' .input-text').each(function (item) {
                        item.removeClassName('validation-passed');
                    });
                }
            });
        } else {
            objects.each(function (item) {
                if ($(item)) {
                    $(item).hide();
                    $$('#' + item + ' .input-text').each(function (sitem) {
                        sitem.addClassName('validation-passed');
                    });
                    $$('#' + item + ' .giftmessage-area').each(function (sitem) {
                        sitem.value = '';
                    });
                    $$('#' + item + ' .checkbox').each(function (sitem) {
                        sitem.checked = false;
                    });
                    $$('#' + item + ' .select').each(function (sitem) {
                        sitem.value = '';
                    });
                    $$('#' + item + ' .price-box').each(function (sitem) {
                        sitem.addClassName('no-display');
                    });
                }
            });
        }
    }
}

if (!window.toogleVisibility) {
    var toogleVisibility = function (objects, show) {
        objects.each(function (item) {
            if ($(item)) {
                if (show) {
                    $(item).show();
                    $(item).removeClassName('no-display');
                } else {
                    $(item).hide();
                    $(item).addClassName('no-display');
                }
            }
        });
    }
}

if (!window.toogleRequired) {
    var toogleRequired = function (source, objects) {
        if (!$(source).value.blank()) {
            objects.each(function (item) {
                $(item) && $(item).addClassName('required-entry');
            });
        } else {
            objects.each(function (item) {
                if (typeof shippingMethod != 'undefined' && shippingMethod.validator) {
                    shippingMethod.validator.reset(item);
                }
                $(item) && $(item).removeClassName('required-entry');
            });
        }
    }
}

if (typeof Checkout !== "undefined") {
    var JMCheckout = Class.create(Checkout, {
        initialize: function (accordion, urls) {
            this.accordion = accordion;
            this.progressUrl = urls.progress;
            this.reviewUrl = urls.review;
            this.saveMethodUrl = urls.saveMethod;
            this.failureUrl = urls.failure;
            this.shippingMethodUrl = urls.shippingMethodUrl;
            this.editAddressUrl = urls.editAddressUrl;
            this.billingForm = false;
            this.shippingForm = false;
            this.syncBillingShipping = false;
            this.method = 'guest';
            this.payment = '';
            this.loadWaiting = false;
            this.steps = ['billing', 'shipping', 'shipping_method', 'payment', 'review'];
            //We use billing as beginning step since progress bar tracks from billing
            this.currentStep = 'billing';
            this.redirectUrl = '';

            this.accordion.on('show.bs.collapse', '.step', function () {
                $(this).up().nextSiblings().invoke('removeClassName', 'allow');
            });

            this.accordion.on('shown.bs.collapse', '.step', function () {
                var parentEl = JMango(this).parents('.section');
                var top = parentEl.offset().top;

                if (typeof scrollTo == 'function') {
                    scrollTo(top);
                } else {
                    JMango('html, body').animate({
                        scrollTop: top
                    });
                }
            });

            if ($('register-customer-password')) {
                Element.hide('register-customer-password');
            }

            ['billing', 'shipping'].each(function (section) {
                /**
                 * Fix site with double #ID, you evil.
                 */
                if ($$('#' + section + '-buttons-container').length) {
                    $$('#' + section + '-buttons-container').each(function (buttonsSection) {
                        $(buttonsSection).select('button').each(function (button) {
                            $(button).setAttribute('id', section + '-button');
                            this.getLaddaButton(button);
                        }.bind(this));
                    }.bind(this));
                }
            }.bind(this));

            /**
             * Kega_Checkout: Bind some shortcut links
             */
            document.on('click', '#edit-billing-address', function () {
                this.gotoSection('billing');
            }.bind(this));
            document.on('click', '#edit-shipping-address', function () {
                this.gotoSection('billing');
            }.bind(this));
            document.on('click', '#edit-shipping-method', function () {
                this.gotoSection('shipping_method');
            }.bind(this));

            document.on('click', '.japi-address-edit-btn', function (e, btn) {
                this.gotoEditAddress(btn);
            }.bind(this))
        },

        gotoEditAddress: function (btn) {
            var addressSelect = $(btn).up('form').down('select.address-select');
            if (addressSelect) {
                var type;
                if (addressSelect.getAttribute('name').indexOf('billing') > -1) {
                    type = 'billing';
                } else if (addressSelect.getAttribute('name').indexOf('shipping') > -1) {
                    type = 'shipping';
                }
                var addressId = addressSelect.getValue();
                if (addressId) {
                    var url = this.editAddressUrl;
                    if (url.indexOf('?') > -1) {
                        url += '&';
                    } else {
                        url += '?';
                    }
                    window.location.href = url + 'id=' + addressId + '&is_checkout=1&type=' + type;
                }
            }
        },

        setLoadWaiting: function (step, keepDisabled) {
            var btn;
            if (step) {
                if (this.loadWaiting) {
                    this.setLoadWaiting(false);
                }
                btn = this.getLaddaButton(step);
                btn && btn.start();
            } else {
                if (this.loadWaiting) {
                    if (!keepDisabled) {
                        btn = this.getLaddaButton(this.loadWaiting);
                        btn && btn.stop();
                    }
                }
            }
            this.loadWaiting = step;
        },

        getLaddaButton: function (el) {
            if (!el) return;
            var $el;
            if (typeof el == 'string') {
                $el = $$('#' + el + '-buttons-container button')[0];
            } else {
                $el = $(el);
            }
            if (!$el) return;
            if ($el.ladda) return $el.ladda;
            $el.addClassName('ladda-button');
            if (!$el.getAttribute('data-color')) $el.setAttribute('data-color', 'jmango');
            if (!$el.getAttribute('data-style')) $el.setAttribute('data-style', 'slide-up');
            if (!$el.getAttribute('data-size')) $el.setAttribute('data-size', 's');
            return $el.ladda = Ladda.create($el);
        },

        reloadProgressBlock: function (toStep) {

        },

        gotoSection: function (section, reloadProgressBlock) {
            var sectionElement = $('opc-' + section);
            if (!sectionElement) return;
            this.currentStep = section;
            sectionElement.addClassName('allow');
            this.cleanList(sectionElement);
            this.initLaddaButtons(section);
            this.accordion.find('#checkout-step-' + section).collapse({parent: this.accordion}).collapse('show');
            var currentStepIndex = this.steps.indexOf(this.currentStep);
            for (var i = 0; i < currentStepIndex; i++) {
                this.allowSection(this.steps[i]);
            }
        },

        initLaddaButtons: function (section) {
            if (!section) return;
            var button = $$('#' + section + '-buttons-container button')[0];
            button && !button.hasClassName('ladda-button') && this.getLaddaButton(button);
        },

        allowSection: function (section) {
            $('opc-' + section) && $('opc-' + section).addClassName('allow');
            this.accordion.find('#checkout-step-' + section).collapse({
                parent: this.accordion,
                toggle: false
            });
        },

        cleanList: function (section) {
            section.select('.sp-methods dt').each(function (item) {
                if (item.innerHTML.trim() == '') $(item).remove();
            });
        },

        setStepResponse: function (response) {
            if (response.update_section) {
                var sectionElm = $('checkout-' + response.update_section.name + '-load');
                sectionElm.update(response.update_section.html);

                // style fix for TIG_PostNL module
                if (response.update_section.name == 'shipping-method') {
                    var postNLContainer = sectionElm.down('.postnl-container');
                    if (postNLContainer) {
                        postNLContainer.up('li').setStyle({display: 'block'});
                    }
                }
            }

            if (response.allow_sections) {
                response.allow_sections.each(function (e) {
                    this.allowSection(e);
                }.bind(this));
            }

            if (response.duplicateBillingInfo) {
                this.syncBillingShipping = true;
                shipping.setSameAsBilling(true);
            }

            if (response.goto_section) {
                this.gotoSection(response.goto_section, true);
                return true;
            }

            if (response.redirect) {
                location.href = response.redirect;
                return true;
            }

            return false;
        },

        japiRedirect: function (id, url) {
            var btn = this.getLaddaButton($(id));
            btn && btn.start();
            setLocation(url);
        },

        gotoShippingMethodSection: function () {
            if (Ajax.activeRequestCount) {
                return setTimeout(function () {
                    this.gotoShippingMethodSection();
                }.bind(this), 100);
            }

            if (typeof billing == 'undefined') return;
            var billingValidator = new Validation(billing.form);
            if (!billingValidator.validate()) return;

            if (typeof shippingMethod == 'undefined') return;
            checkout.gotoSection('shipping_method');
            this.loadShippingMethod(this.shippingMethodUrl);
        },

        loadShippingMethod: function (url) {
            if (this.loadWaiting != false || !url) return;
            var additionalElm = $('onepage-checkout-shipping-method-additional-load');
            additionalElm && additionalElm.hide();
            checkout.setLoadWaiting('shipping-method');
            new Ajax.Request(url, {
                method: 'get',
                onComplete: function () {
                    checkout.setLoadWaiting(false);
                    additionalElm && additionalElm.show();
                },
                onSuccess: function (transport) {
                    billing && billing.nextStep(transport);
                }
            });
        }
    });
}

var JMAgreement = Class.create({
    initialize: function (form) {
        this.form = form;
        this.initModals();
    },

    initModals: function () {
        this.form.select('div.modal').each(function (modal) {
            JMango(modal).find('a').click(function (e) {
                e.preventDefault();
            });
            JMango(modal).modal({
                show: false
            });
        });
    }
});

var JMDiscount = Class.create({
    initialize: function (form, saveUrl) {
        this.form = form;
        this.validator = new Validation(this.form);
        this.saveUrl = saveUrl;
        this.inputField = $(form).down('#coupon_code');
        this.removeField = $(form).down('#remove-coupone');
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
    },

    submit: function (isRemove) {
        if (isRemove) {
            this.inputField.removeClassName('required-entry');
            this.removeField.value = "1";
        } else {
            this.inputField.addClassName('required-entry');
            this.removeField.value = "0";
        }

        if (this.validator && this.validator.validate()) {
            checkout.setLoadWaiting($('coupon-button'));

            new Ajax.Request(this.saveUrl, {
                method: 'post',
                parameters: Form.serialize(this.form),
                onSuccess: function (transport) {
                    this.resetLoadWaiting();
                    if (transport.responseJSON) {
                        var data = transport.responseJSON;
                        if (data.success && data.html) {
                            $('checkout-review-load').update(data.html);
                            checkout.initLaddaButtons('review');
                        }
                        if (data.message) {
                            alert(data.message);
                        }
                    }
                }.bind(this),
                onComplete: this.onComplete,
                onFailure: this.onComplete
            });
        }
    },

    resetLoadWaiting: function () {
        checkout.setLoadWaiting(false);
    }
});

/**
 * Override ShippingMethod.nextStep() method
 * change position of payment.initWhatIsCvvListeners() method
 */
if (typeof ShippingMethod !== 'undefined') {
    ShippingMethod.prototype.nextStep = function (transport) {
        if (transport && transport.responseText) {
            try {
                response = eval('(' + transport.responseText + ')');
            } catch (e) {
                response = {};
            }
        }

        if (response.error) {
            alert(response.message);
            return false;
        }

        if (response.update_section) {
            $('checkout-' + response.update_section.name + '-load').update(response.update_section.html);
        }

        if (response.goto_section) {
            checkout.gotoSection(response.goto_section);
            checkout.reloadProgressBlock();
            return;
        }

        if (response.payment_methods_html) {
            $('checkout-payment-method-load').update(response.payment_methods_html);
        }

        checkout.setShippingMethod();

        payment.initWhatIsCvvListeners();
    };
}

/**
 * Override Payment.initWhatIsCvvListeners() method
 * Add condition check element exist
 */
if (typeof Payment !== 'undefined') {
    Payment.prototype.initWhatIsCvvListeners = function () {
        $$('.cvv-what-is-this').each(function (element) {
            element && Event.observe(element, 'click', toggleToolTip);
        });
    };

    Payment.prototype.nextStep = function (transport) {
        if (transport && transport.responseText) {
            try {
                response = eval('(' + transport.responseText + ')');
            } catch (e) {
                response = {};
            }
        }

        /*
         * if there is an error in payment, need to show error message
         */
        if (response.error) {
            if (response.fields) {
                var fields = response.fields.split(',');
                for (var i = 0; i < fields.length; i++) {
                    var field = null;
                    if (field = $(fields[i])) {
                        Validation.ajaxError(field, response.error);
                    }
                }
                return;
            }
            if (typeof(response.message) == 'string') {
                alert(response.message);
            } else {
                alert(response.error);
            }
            return;
        }

        if (response.redirect) {
            checkout.redirectUrl = response.redirect;
        }

        checkout.setStepResponse(response);
        //checkout.setPayment();
    };

    /**
     * Kega_Checkout: Add additional validation functions to payment step
     */
    Payment.prototype.addMoreValidateFunction = function (code, func) {
        if (!this.moreValidateFunc) this.moreValidateFunc = $H({});
        this.moreValidateFunc.set(code, func);
    };

    /**
     * Kega_Checkout: Validate additional validation functions
     */
    Payment.prototype.moreValidate = function () {
        var validateResult = true;
        if (!this.moreValidateFunc) this.moreValidateFunc = $H({});
        (this.moreValidateFunc).each(function (validate) {
            if ((validate.value)() == false) {
                validateResult = false;
            }
        }.bind(this));
        return validateResult;
    };

    /**
     * Kega_Checkout: Run additional validations
     */
    Payment.prototype.save = function () {
        if (checkout.loadWaiting != false) return;
        var validator = new Validation(this.form);
        if (this.validate() && this.moreValidate() && validator.validate()) {
            checkout.setLoadWaiting('payment');
            var request = new Ajax.Request(
                this.saveUrl,
                {
                    method: 'post',
                    onComplete: this.onComplete,
                    onSuccess: this.onSave,
                    onFailure: checkout.ajaxFailure.bind(checkout),
                    parameters: Form.serialize(this.form)
                }
            );
        }
    };
}

if (typeof Review !== 'undefined') {
    Review.prototype.save = function () {
        if (checkout.loadWaiting != false) return;
        if (!this.moreValidate()) return;
        checkout.setLoadWaiting('review');
        var params = Form.serialize(payment.form);
        if (this.agreementsForm) {
            params += '&' + Form.serialize(this.agreementsForm);
        }
        if (this.moreForms) {
            for (var i = 0; i < this.moreForms.length; i++) {
                params += '&' + Form.serialize(this.moreForms[i]);
            }
        }
        params += '&redirect=' + checkout.redirectUrl;
        new Ajax.Request(this.saveUrl, {
            method: 'post',
            parameters: params,
            onComplete: this.onComplete,
            onSuccess: this.onSave,
            onFailure: checkout.ajaxFailure.bind(checkout)
        });
    };

    Review.prototype.resetLoadWaiting = function (transport) {
        checkout.setLoadWaiting(false, this.isSuccess);
    };

    Review.prototype.addMoreValidateFunction = function (code, func) {
        if (!this.moreValidateFunc) this.moreValidateFunc = $H({});
        this.moreValidateFunc.set(code, func);
    };

    Review.prototype.moreValidate = function () {
        var validateResult = true;
        if (!this.moreValidateFunc) this.moreValidateFunc = $H({});
        (this.moreValidateFunc).each(function (validate) {
            if ((validate.value)() == false) {
                validateResult = false;
            }
        }.bind(this));
        return validateResult;
    };

    Review.prototype.addMoreFormToSubmit = function (formId) {
        if (!this.moreForms) this.moreForms = [];
        this.moreForms.push($(formId));
    };
}

document.observe('dom:loaded', function () {
    if (typeof SocoShippingMethod !== 'undefined') {
        SocoShippingMethod.prototype.freezeSteps = function () {
            this.savedAllowedSteps = [];

            var steps = $('checkoutSteps').children;
            for (var i = 0; i < steps.length; i++) {
                if (steps[i].hasClassName('allow')) {
                    this.savedAllowedSteps[i] = true;
                    steps[i].removeClassName('allow');
                } else {
                    this.savedAllowedSteps[i] = false;
                }
            }

            /**
             * Hide default "Continue" button
             */
            $('shipping-method-buttons-container').hide();

            /**
             * Style "Annuler So Colissimo" button
             */
            var $btn = $$('#socolissimosimplicite_iframe_wrapper button')[0];
            if ($btn) {
                $btn.addClassName('ladda-button');
                $btn.setAttribute('data-size', 's');
            }
        };

        SocoShippingMethod.prototype.unfreezeSteps = function () {
            if (typeof(this.savedAllowedSteps) !== 'undefined') {
                var steps = $('checkoutSteps').children;
                for (var i = 0; i < steps.length; i++) {
                    if (this.savedAllowedSteps[i] === true) {
                        steps[i].addClassName('allow');
                    }
                }
            }

            /**
             * Show default "Continue" button
             */
            $('shipping-method-buttons-container').show();
        };
    }
});

Event.observe(document, "dom:loaded", function () {
    if (typeof Checkout != 'undefined' && Checkout.prototype.disallowSection) {
        Checkout.addMethods({
            setStepResponse: function (response) {
                //$$('.main-container').first().scrollTo();
                if (response.update_section) {
                    $('checkout-' + response.update_section.name + '-load').update(response.update_section.html);
                    this.assignDisallowEvent('checkout-' + response.update_section.name + '-load');
                }
                //JMANGO360: Disable product's link in cart table
                if ($$('.cart-table').length) {
                    $$('.cart-table').each(function (table) {
                        $(table).select('a').each(function (a) {
                            $(a).observe('click', function (e) {
                                e.preventDefault();
                            });
                        });
                    });
                }
                //END
                if (response.allow_sections) {
                    response.allow_sections.each(function (e) {
                        $('opc-' + e).addClassName('allow');
                    });
                }
                if (response.duplicateBillingInfo) {
                    this.syncBillingShipping = true;
                    shipping.setSameAsBilling(true);
                }
                if (response.goto_section) {
                    this.gotoSection(response.goto_section, false);
                    //JMANGO360: Apply scroll to section
                    var top = JMango('#opc-' + response.goto_section).offset().top;
                    if (typeof scrollTo == 'function') {
                        scrollTo(top);
                    } else {
                        JMango('html, body').animate({
                            scrollTop: top
                        });
                    }
                    //END
                    if (response.messages) {
                        response.messages.forEach(function (message, i) {
                            jsHelper.displayMessage(message.message, message.type);
                        });
                    }
                    return true;
                }
                if (response.redirect) {
                    location.href = response.redirect;
                    return true;
                }
                return false;
            }
        });
    }
});