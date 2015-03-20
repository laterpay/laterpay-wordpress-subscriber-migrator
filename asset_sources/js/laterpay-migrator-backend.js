(function($) {$(function() {

    function laterpayMigratorBackend() {
        var $o = {
                // activation
                activateButton              : $('#lp_js_startMigration'),
                mainForm                    : $('#lp_js_migratorMainForm'),

                // file upload
                files                       : undefined,
                fileUploadForm              : $('#lp_js_uploadForm'),
                fileInput                   : $('#lp_js_fileInput'),

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
                .on('change', function() {
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
                    url: lpMigratorVars.ajaxUrl,
                    type: 'POST',
                    data: data,
                    cache: false,
                    dataType: 'json',
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        if (data.success) {
                            location.reload();
                        } else {
                            setMessage(lpMigratorVars.i18nUploadFailed, false);
                        }
                    }
                });
            },

            activateMigration = function() {
                $.post(
                    lpMigratorVars.ajaxUrl,
                    $o.mainForm.serializeArray(),
                    function(data) {
console.log(data);
                        setMessage(lpMigratorVars.i18nSetupModeActivated, false);
// TODO: refresh block with state etc.
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
