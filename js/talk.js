$(function() {
    var public_ajax_obj = {nonce: null,action: 'update',ajax_url: 'update_all.php'};
    var ajax_obj = null;
    function talk_to_server(method, server_options, success_callback, error_callback) {

        if (!server_options) {
            server_options = {};
        }

        // noinspection ES6ModulesDependencies
        var outvars = $.extend({}, server_options);
        // noinspection JSUnresolvedVariable
        outvars._ajax_nonce = public_ajax_obj.nonce;
        // noinspection JSUnresolvedVariable
        outvars.action = public_ajax_obj.action;
        outvars.method = method;
        // noinspection ES6ModulesDependencies
        // noinspection JSUnresolvedVariable
        ajax_obj = $.ajax({
            type: 'POST',
            beforeSend: function () {
                if (ajax_obj && (ajax_obj !== 'ToCancelPrevReq') && (ajax_obj.readyState < 4)) {
                    //    ajax_obj.abort();
                }
            },
            dataType: "json",
            url: public_ajax_obj.ajax_url,
            data: outvars,
            success: success_handler,
            error: error_handler
        });

        function success_handler(data) {

            // noinspection JSUnresolvedVariable
            if (data.valid) {
                if (data.hasOwnProperty('new_nonce') ) {
                    public_ajax_obj.nonce = data.new_nonce;
                }
                if (success_callback) {
                    success_callback(data);
                } else {
                    console.debug(data);
                }
            } else {
                if (error_callback) {
                    console.warn(data);
                    error_callback(null,data);
                } else {
                    console.debug(data);
                }

            }
        }

        /**
         *
         * @param {XMLHttpRequest} jqXHR
         * @param {Object} jqXHR.responseJSON
         * @param {string} textStatus
         * @param {string} errorThrown
         */
        function error_handler(jqXHR, textStatus, errorThrown) {
            if (errorThrown === 'abort' || errorThrown === 'undefined') return;
            var what = '';
            var message = '';
            if (jqXHR && jqXHR.responseText) {
                try {
                    what = $.parseJSON(jqXHR.responseText);
                    if (what !== null && typeof what === 'object') {
                        if (what.hasOwnProperty('message')) {
                            message = what.message;
                        } else {
                            message = jqXHR.responseText;
                        }
                    }
                } catch (err) {
                    message = jqXHR.responseText;
                }
            } else {
                message = "textStatus";
                console.info('Admin Ecomhub ajax failed but did not return json information, check below for details', what);
                console.error(jqXHR, textStatus, errorThrown);
            }

            if (error_callback) {
                console.warn(message);
                error_callback(message,null);
            } else {
                console.warn(message);
            }


        }
    }

});