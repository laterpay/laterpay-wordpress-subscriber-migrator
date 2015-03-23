(function($) {$(function() {

    function laterpayMigratorBackend() {
        var $o = {
                // activation
                activateButton              : $('#lp_js_startMigration'),
                statusLabels                : $('.lp_status-indicator__label'),
                mainForm                    : $('#lp_js_migratorMainForm'),

                // file upload
                files                       : undefined,
                fileUploadForm              : $('#lp_js_uploadForm'),
                fileInput                   : $('#lp_js_fileInput'),
                fileUploadButton            : $('#lp_js_uploadButton'),

                // sitenotice configuration inputs
                sitenoticeInputs            : $('.lp_js_sitenoticeInput'),
                sitenoticeTextInput         : $('#lp_js_sitenoticeTextInput'),
                sitenoticeButtonTextInput   : $('#lp_js_sitenoticeButtonTextInput'),
                sitenoticeBgColorInput      : $('#lp_js_sitenoticeBgColorInput'),
                sitenoticeTextColorInput    : $('#lp_js_sitenoticeTextColorInput'),

                // sitenotice preview
                sitenotice                  : $('#lp_js_browserSitenotice'),
                sitenoticeText              : $('#lp_js_browserSitenoticeText'),
                sitenoticeButton            : $('#lp_js_browserSitenoticeButton'),
            },

            bindEvents = function() {
                // bind file upload
                $o.fileInput
                .change(function() {
                    $o.files = event.target.files;
                    uploadFile();
                });

                // bind activation
                $o.activateButton
                .mousedown(function() {
                    activateMigration();
                })
                .click(function(e) {e.preventDefault();});

                // live update sitenotice preview
                // (function is only triggered 800ms after the keyup)
                $o.sitenoticeInputs
                .keyup(
                    debounce(function() {
                        updateSitenoticePreview();
                    }, 800)
                );
            },

            uploadFile = function() {
                var data = new FormData($o.fileUploadForm.get(0));
                data.append('file', $o.files[0]);

                $.ajax({
                    url         : lpMigratorVars.ajaxUrl,
                    type        : 'POST',
                    data        : data,
                    cache       : false,
                    dataType    : 'json',
                    processData : false,
                    contentType : false,
                    beforeSend  : function() {
                                    $o.fileUploadButton
                                    .attr('data', $o.fileUploadButton.text())
                                    .html('<div class="lp_loading-indicator"></div>');
                                },
                    success     : function(data) {
                                    if (data.success) {
                                        location.reload();
                                    } else {
                                        setMessage(data.message, false);
                                    }
                                },
                    complete    : function() {
                                    $o.fileUploadButton.text($o.fileUploadButton.attr('data'));
                                },
                });
            },

            activateMigration = function() {
                $.post(
                    lpMigratorVars.ajaxUrl,
                    $o.mainForm.serializeArray(),
                    function(response) {
                        setMessage(response.message, response.success);
                        if ( response.data ) {
                            // switch button text and input value
                            // TODO: dirty code to make switcher work, need to refactor
                            var button = $('input[type=radio][name=laterpay_migrator_status][value="' +
                                           response.data.value + '"]');
                            $o.activateButton.text(response.data.text);
                            button.prop('checked', true);
                            $o.statusLabels.removeClass('lp_is-active');
                            button.parent('label').addClass('lp_is-active');
                            if ( response.data.value ) {
                                if ( response.data.value === 'setup' ) {
                                    $('input[name=migration_active]').val(0);
                                } else {
                                    $('input[name=migration_active]').val(1);
                                }
                            }
                        }
                    },
                    'json'
                );
            },

            updateSitenoticePreview = function() {
                var sitenoticeBgColor       = $o.sitenoticeBgColorInput.val(),
                    sitenoticeTextColor     = $o.sitenoticeTextColorInput.val(),
                    sitenoticeText          = $o.sitenoticeTextInput.val(),
                    sitenoticeButtonText    = $o.sitenoticeButtonTextInput.val();

                if (sitenoticeBgColor !== '') {
                    $o.sitenotice
                    .css({'background': $o.sitenoticeBgColorInput.val()});
                }

                if (sitenoticeTextColor !== '') {
                    $o.sitenoticeText
                    .css({'color': $o.sitenoticeTextColorInput.val()});
                }

                if (sitenoticeText !== '') {
                    $o.sitenoticeText
                    .text($o.sitenoticeTextInput.val());
                }

                if (sitenoticeButtonText !== '') {
                    $o.sitenoticeButton
                    .text($o.sitenoticeButtonTextInput.val());
                }
            },

            // throttle the execution of a function by a given delay
            debounce = function(fn, delay) {
                var timer;
                return function() {
                    var context = this,
                        args    = arguments;

                    clearTimeout(timer);

                    timer = setTimeout(function() {
                                fn.apply(context, args);
                            }, delay);
                };
            },

            init = function() {
                bindEvents();
            };

        init();
    }

    laterpayMigratorBackend();

});})(jQuery);
