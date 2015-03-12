(function($) {$(function() {

    function laterpayMigratorSettings() {
        var $o = {
                fileInput    : $('#lp_migrator_fileInput'),
                uploadButton : $('#lp_migrator_startUpload'),
                uploadForm   : $('#lp_migrator_uploadForm'),
            },

            bindEvents = function() {
                $o.fileInput
                .on('change', prepareDownload);

                $o.uploadForm
                .on('submit', uploadFile);
            },

            prepareDownload = function(e) {
                $o.file = e.target.files;
            },

            uploadFile = function(e) {
                e
                .stopPropagation()
                .preventDefault();

                var data = new FormData();

                $.each($o.file, function(key, value) {
                    data.append(key, value);
                });

                $.ajax({
                    url         : lpMigratorVars.ajaxUrl,
                    type        : 'POST',
                    data        : {
                                    action  : 'laterpay_migrator_upload_file',
                                    data    : data,
                                  },
                    cache       : false,
                    dataType    : 'json',
                    processData : false,
                    contentType : false,
                    success     : function(data) {
                                        if (data.success) {
                                            // TODO: Show success message
                                            // Process uploaded data
                                            submitForm(e, data);
                                        }
                                    }
                });
            };

        bindEvents();
    }

    laterpayMigratorSettings();

});})(jQuery);
