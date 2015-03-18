(function($) {$(function() {

    function laterpayMigratorBackend() {
        var $o = {
                // file upload
                files               : undefined,
                fileUploadForm      : $('#laterpay_migrator_file_upload_form'),
                fileInput           : $('.lp_upload__input'),
                // activation
                activateButton      : $('#laterpay_migrator_activate_button'),
                mainForm            : $('#laterpay_migrator_main_form'),
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
                    success: function(data)
                    {
                        // message
                    }
                });
            },

            activateMigration = function() {
                $.post(
                    lpMigratorVars.ajaxUrl,
                    $o.mainForm.serializeArray(),
                    function(data) {
                        // message
                        // refresh block with state and etc.
                    },
                    'json'
                )
            },

            init = function() {
                bindEvents();
            };

        init();
    }

    laterpayMigratorBackend();

});})(jQuery);