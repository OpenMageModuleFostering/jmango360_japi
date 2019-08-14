/**
 * Copyright 2015 JMango360
 */

if (typeof Checkout !== "undefined") {
    var JMCheckout = Class.create(Checkout, {
        initialize: function (accordion, urls) {
            this.accordion = accordion;
            this.progressUrl = urls.progress;
            this.reviewUrl = urls.review;
            this.saveMethodUrl = urls.saveMethod;
            this.failureUrl = urls.failure;
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
                var parentEl = jQuery(this).parents('.section');
                var top = parentEl.offset().top;

                if (typeof scrollTo == 'function') {
                    scrollTo(top);
                } else {
                    jQuery('html, body').animate({
                        scrollTop: top
                    });
                }
            });

            if ($('register-customer-password')) {
                Element.hide('register-customer-password');
            }

            ['billing', 'shipping'].each(function (section) {
                $(section + '-buttons-container').select('button').each(function (button) {
                    $(button).setAttribute('id', section + '-button');
                    this.getLaddaButton(button);
                }.bind(this));
            }.bind(this));
        },

        setLoadWaiting: function (step, keepDisabled) {
            var btn;
            if (step) {
                if (this.loadWaiting) {
                    this.setLoadWaiting(false);
                }
                btn = this.getLaddaButton($(step + '-button'));
                btn && btn.start();
            } else {
                if (this.loadWaiting) {
                    if (!keepDisabled) {
                        btn = this.getLaddaButton($(this.loadWaiting + '-button'));
                        btn && btn.stop();
                    }
                }
            }
            this.loadWaiting = step;
        },

        getLaddaButton: function (el) {
            if (!el) return;
            if (el.ladda) return el.ladda;
            var $el = $(el);
            $el.addClassName('ladda-button');
            if (!$el.getAttribute('data-color')) $el.setAttribute('data-color', 'jmango');
            if (!$el.getAttribute('data-style')) $el.setAttribute('data-style', 'slide-up');
            if (!$el.getAttribute('data-size')) $el.setAttribute('data-size', 's');
            return el.ladda = Ladda.create(el);
        },

        reloadProgressBlock: function (toStep) {

        },

        gotoSection: function (section, reloadProgressBlock) {
            this.currentStep = section;
            var sectionElement = $('opc-' + section);

            sectionElement.addClassName('allow');
            this.cleanList(sectionElement);
            this.accordion.find('#checkout-step-' + section).collapse({parent: this.accordion}).collapse('show');

            var currentStepIndex = this.steps.indexOf(this.currentStep);
            for (var i = 0; i < currentStepIndex; i++) {
                this.allowSection(this.steps[i]);
            }
        },

        allowSection: function (section) {
            $('opc-' + section).addClassName('allow');
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
            jQuery(modal).modal({
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
            checkout.setLoadWaiting('coupon');

            new Ajax.Request(this.saveUrl, {
                method: 'post',
                parameters: Form.serialize(this.form),
                onSuccess: function (transport) {
                    this.resetLoadWaiting();
                    if (transport.responseJSON) {
                        var data = transport.responseJSON;
                        if (data.success && data.html) {
                            $('checkout-review-load').update(data.html);
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

    ShippingMethod.prototype.load = function (url) {
        if (checkout.loadWaiting != false || !url) return;
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
}

if (typeof Review !== 'undefined') {
    Review.prototype.save = function () {
        if (checkout.loadWaiting != false) return;
        checkout.setLoadWaiting('review');
        var params = Form.serialize(payment.form);
        if (this.agreementsForm) {
            params += '&' + Form.serialize(this.agreementsForm);
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
}