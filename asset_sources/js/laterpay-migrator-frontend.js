(function($) {$(function() {

    function laterpayMigratorFrontend() {
        var $o = {
                html                : $('html'),
                body                : $('body'),
                adminbar            : $('#wpadminbar'),
                sitenotice          : $('.lp_siiitenotice'),
                siteheader          : $('.site-header'),
                purchaseButton      : $('#lp_buySubscription'),
                fakePurchaseButton  : '#lp_fakeButton',
            },

            bindEvents = function() {
                $o.purchaseButton
                .on('mousedown', function() {
                    getPurchaseUrl();
                })
                .on('click', function(e) {e.preventDefault();});

                // adjust sitenotice position on resize
                $(window).resize(function() {
                    displaySitenotice();
                });
            },

            displaySitenotice = function() {
                var htmlMarginTop       = parseInt($o.html.css('margin-top'), 10) || 0,
                    siteheaderTop       = parseInt($o.siteheader.css('top'), 10) || 0,
                    // adminbarHeight      = $o.adminbar.is(':visible') ? $o.adminbar.outerHeight() : 0,
                    adminbarHeight      = $o.adminbar.outerHeight(),
                    sitenoticeHeight    = $o.sitenotice.outerHeight();

                // increase margin-top of HTML to make room for the sitenotice
                $o.html.css('margin-top', htmlMarginTop + sitenoticeHeight + 'px');

                // adjust top of the sitenotice to position it below the adminbar
                $o.sitenotice.css('top', adminbarHeight + 'px');

                // increase top of the site-header to position it below the sitenotice
                $o.siteheader.css('top', siteheaderTop + sitenoticeHeight + 'px');
            },

            getPurchaseUrl = function() {
                $.get(
                    lpMigratorVars.ajaxUrl,
                    {
                        action: 'laterpay_migrator_get_purchase_url'
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
                bindEvents();

                // render sitenotice with a delay to allow other Javascript-based position to complete before
                setTimeout(function() {displaySitenotice();}, 500);
            };

        init();
    }

    laterpayMigratorFrontend();

});})(jQuery);
