(function($) {$(function() {

    function laterpayMigratorFrontend() {
        var $o = {
                body                : $('body'),
                sitenotice          : $('.lp_sitenotice'),
                purchaseButton      : $('#lp_buySubscription'),
                fakePurchaseButton  : '#lp_fakeButton',
            },

            bindEvents = function() {
                $o.purchaseButton
                .on('mousedown', function() {
                    getPurchaseUrl();
                })
                .on('click', function(e) {e.preventDefault();});
            },

            displaySitenotice = function() {
                var spaceForSitenotice = (parseInt($o.body.style.paddingTop, 10) || 0 ) + $o.sitenotice.outerHeight();

                // increase the top padding of the body by the height of the sitenotice
                // so the sitenotice can be absolute positioned at top:0 without overlap
                $('body').css('padding-top', spaceForSitenotice + 'px');
            },

            getPurchaseUrl = function() {
                $.get(
                    lpMigratorVars.ajaxUrl,
                    {
                        action  : 'laterpay_migrator_get_purchase_url'
                    },
                    function(r) {
                        if (r.success) {
                            $($o.fakePurchaseButton).attr('data-laterpay', r.url);

                            // fire purchase event on hidden fake button
                            YUI().use('node', 'node-event-simulate', function(Y) {
                                Y.one($o.fakePurchaseButton).simulate('click');
                            });
                        }
                    },
                    'json'
                );
            },

            init = function() {
                displaySitenotice();
                bindEvents();
            };

        init();
    }

    laterpayMigratorFrontend();

});})(jQuery);
