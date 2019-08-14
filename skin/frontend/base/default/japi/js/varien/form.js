if (typeof VarienForm != 'undefined') {
    VarienForm.prototype.bindElements = function () {
        var elements = Form.getElements(this.form);
        for (var row in elements) {
            if (elements[row].id) {
                Event.observe(elements[row], 'focus', this.elementFocus);
                Event.observe(elements[row], 'blur', this.elementBlur);
            }
        }

        var buttons = this.form.select('.buttons-set button');
        buttons.each(function (el) {
            this.initButton(el);
        }.bind(this));

        Event.observe(this.form, 'submit', this.onSubmit.bind(this));
    };

    VarienForm.prototype.initButton = function (el) {
        if (!el) return;
        if (el.ladda) return el.ladda;
        var $el = $(el);
        $el.addClassName('ladda-button');
        if (!$el.getAttribute('data-color')) $el.setAttribute('data-color', 'jmango');
        if (!$el.getAttribute('data-style')) $el.setAttribute('data-style', 'slide-up');
        if (!$el.getAttribute('data-size')) $el.setAttribute('data-size', 's');

        return el.ladda = Ladda.create(el);
    };

    VarienForm.prototype.onSubmit = function () {
        if (this.validator && this.validator.validate()) {
            this.form.select('button[type="submit"]').each(function (el) {
                el.ladda && el.ladda.start();
            });
        }
    };
}