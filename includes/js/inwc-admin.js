jQuery(document).ready(function($) {

    /** @since 1.0 Functions for report signal  */
    $('#signals_meta_box #btn-report').click(function(e) {
        e.preventDefault();
        $('html, body').css('overflow', 'hidden');
        $('#signals_meta_box #report-overlay').fadeIn();
        $('#signals_meta_box #report-popup').fadeIn(200);
    });

    $('#signals_meta_box #close-popup').click(function(e) {
        e.preventDefault();
        $('html, body').css('overflow', 'auto');
        $('#signals_meta_box #report-overlay').fadeOut();
        $('#signals_meta_box #report-popup').fadeOut(200);
    });

    $('#signals_meta_box #description').on('input', function () {
        if ($(this).val() !== '') {
            $(this).next('.signal-err').remove()
            $(this).removeClass('signal-err-field')
        }
    })

    $("#signals_meta_box #facebook-url").on("input", function() {
        if ($(this).val() !== '') {
            $(this).next('.signal-err').remove()
            $(this).removeClass('signal-err-field')
        }
    });

    $("#signals_meta_box #website-url").on("input", function() {
        if ($(this).val() !== '') {
            $(this).next('.signal-err').remove()
            $(this).removeClass('signal-err-field')
        }
    });

    $(document).click(function(e) {
        if (!$(e.target).closest('#signals_meta_box #report-popup').length && !$(e.target).is('#btn-report')) {
            $('html, body').css('overflow', 'auto');
            $('#signals_meta_box #report-overlay').fadeOut();
            $('#signals_meta_box #report-popup').fadeOut(200);
        }
    });

    $('#signals_meta_box #btn-report-post').click(function(e) {
        e.preventDefault();

        $formValidator = true;

        // Validate FB URL
        var inputValueFB = $("#signals_meta_box #facebook-url").val().trim()
        const regexFB_URL = /^https:\/\/(www\.)?facebook\.com\//;
        if (!regexFB_URL.test(inputValueFB) && inputValueFB !== '') {
            if (!$("#signals_meta_box #facebook-url").parent().find('.signal-err').length) {
                $('#signals_meta_box #facebook-url').addClass('signal-err-field').after(`<p class="signal-err" style="margin: 0; color: #d63638">${translate_obj.validateFB_URL}</p>`)
            }
            $formValidator = false;
        }

        // Validate Website URL
        var inputValueWebsite_URL = $("#signals_meta_box #website-url").val().trim();
        const regexWebsite_URL = /^(https?:\/\/)/;

        if (!regexWebsite_URL.test(inputValueWebsite_URL) && inputValueWebsite_URL !== '') {
            if (!$("#signals_meta_box #website-url").parent().find('.signal-err').length) {
                $('#signals_meta_box #website-url').addClass('signal-err-field').after(`<p class="signal-err" style="margin: 0; color: #d63638">${translate_obj.validateWebsite_URL}</p>`)
            }
            $formValidator = false;
        }

        // Validate description field required
        if ($('#signals_meta_box #description').val() === '') {
            if (!$("#signals_meta_box #description").parent().find('.signal-err').length) {
                $('#signals_meta_box #description').addClass('signal-err-field').after(`<p class="signal-err" style="margin: 0; color: #d63638">${translate_obj.required}</p>`)
            }
            $formValidator = false;
        }

        // Validate FORM
        if (!$formValidator) {
            return
        }

        var phoneValue = '';

        if ($('#phone').hasClass('field-with-choose-code') && $('#phone').val() !== '') {
            phoneValue = $('.iti__selected-dial-code').text().trim().replace(/^\+/, '') + $('#phone').val().replace(/^0/, '');
        } else {
            phoneValue = $('#phone').val().replace(/^\+/, '');
        }


        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'inwc_form_submission_signal',
                nonce: $('#inwc_submission_nonce').val(),
                firstName:  $('#first-name').val(),
                lastName:  $('#last-name').val(),
                phone:  phoneValue,
                email:  $('#email').val(),
                websiteUrl:  $('#website-url').val(),
                facebookUrl:  $('#facebook-url').val(),
                description:  $('#description').val(),
            },
            success: function(response) {

                if (response.success) {
                    $('#signals_meta_box #report-overlay').fadeOut();
                    $('#signals_meta_box #report-popup').fadeOut(200);
                    Swal.fire({
                        // title: "",
                        text: JSON.parse(response.data).message,
                        icon: "success",
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 4000
                    });
                } else {
                    $('#signals_meta_box #report-overlay').fadeOut();
                    $('#signals_meta_box #report-popup').fadeOut(200);
                    Swal.fire({
                        title: translate_obj.oops,
                        text: JSON.parse(response.data.body).message,
                        icon: "error",
                        position: "center",
                        showConfirmButton: true,
                        // timer: 3000
                    });
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX errors
                console.error('AJAX error:', error);
            }
        });
    });

    /** @since 1.0 add country code option in field for phone  */
    $('.field-with-choose-code').intlTelInput({
        separateDialCode: true,
        initialCountry: 'bg',
        preferredCountries: ['bg', 'us', 'gb']
    });


    $("#inwc_settings_turn_on").on("change", function() {
        const textOn = "On";
        const textOff = "Off";
        const label = $(this).next(".switch-label");
        label.next(".on-text").text(this.checked ? textOn : textOff);
    });

    // Trigger the change event on page load to set the initial state
    $("#inwc_settings_turn_on").trigger("change");


    /** @since 1.1 function for copy server IP  */
    $("#inwc_server_ip").click(function() {
        var serverIp = $("#inwc_server_ip").text();
        inwc_copyToClipboard(serverIp);
    });
    function inwc_copyToClipboard(text) {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(text).select();
        document.execCommand("copy");
        $temp.remove();

        var dialog = $("#inwc_clipboard-alert");
        dialog.html(translate_obj.ip_copied);
        dialog.dialog({
            autoOpen: true,
            draggable: false,
            resizable: false,
            modal: false,
            title: false,
            closeOnEscape: false,
            minHeight: "auto",
            dialogClass: "inwc-dialog-class"
        });

        dialog.closest('.ui-dialog').find('.ui-dialog-titlebar').hide();

        setTimeout(function() {
            dialog.dialog("close");
        }, 1500);
    }

});