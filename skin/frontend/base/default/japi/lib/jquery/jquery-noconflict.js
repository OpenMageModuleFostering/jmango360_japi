/**
 * Copyright 2015 JMango360
 */

if (window.jQuery) {
    var JMango = jQuery.noConflict();
    ['collapse', 'dropdown', 'modal', 'tooltip', 'popover'].each(function (plugin) {
        JMango(window).on('hide.bs.' + plugin, function (event) {
            event.target['hide'] = undefined;
            setTimeout(function () {
                delete event.target['hide'];
            }, 0);
        });
    });
}
