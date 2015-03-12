(function($) {$(function() {

    function laterpayMigratorFrontend() {
        var $o = {
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
            };

        bindEvents();
    }

    laterpayMigratorFrontend();

});})(jQuery);
