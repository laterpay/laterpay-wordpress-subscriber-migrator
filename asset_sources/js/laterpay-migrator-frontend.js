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
                    renderSitenotice();
                });
            },

            renderSitenotice = function() {
                var siteheaderTop       = parseInt($o.siteheader.css('top'), 10) || 0,
                    adminbarHeight      = $o.adminbar.outerHeight(),
                    sitenoticeHeight    = $o.sitenotice.outerHeight(),
                    htmlMarginTop;

                // get margin-top of HTML element
                if ($('html[data-migrator-init]').length === 0) {
                    // get initial margin-top value from HTML element
                    htmlMarginTop = parseInt($o.html.css('margin-top'), 10) || 0;

                    // save initial margin-top value in data attribute to avoid repeated incrementing on resize
                    $o.html.attr('data-migrator-init', htmlMarginTop);
                } else {
                    // get initial margin-top value from data attribute
                    htmlMarginTop = parseInt($o.html.attr('data-migrator-init'), 10);
                }

                // increase margin-top of HTML element to make room for the sitenotice
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

                // render sitenotice with a delay to allow other Javascript-based positioning to complete before
                setTimeout(function() {renderSitenotice();}, 300);
            };

        init();
    }

    laterpayMigratorFrontend();

});})(jQuery);
