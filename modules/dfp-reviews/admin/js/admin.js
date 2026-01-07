jQuery(document).ready(function($) {
    $('#add-clinic').on('click', function() {
        var clinicCount = parseInt($('#clinic-count').val()) + 1;
        var clinicHtml = `
            <div class="clinic-section" data-clinic-id="${clinicCount}">
                <table class="form-table">
                    <tr><th colspan="2">
                        <h2>Clinic ${clinicCount}</h2>
                    </th></tr>
                    <tr>
                        <th>ID Place Google</th>
                        <td>
                            <input type="text" name="dfp_reviews_id_place_${clinicCount}" value="" class="regular-text">
                            <p class="description">Enter the Google Business Profile ID for Clinic ${clinicCount}.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Update Frequency</th>
                        <td>
                            <select name="dfp_reviews_update_frequency_${clinicCount}">
                                <option value="manual">Manual</option>
                                <option value="onceaday">Once a Day</option>
                                <option value="everythreedays">Once Every 3 Days</option>
                                <option value="weekly" selected>Once Weekly</option>
                                <option value="everyfifteendays">Once Every 15 Days</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Total Reviews</th>
                        <td>
                            <input type="text" name="dfp_reviews_total_reviews_${clinicCount}" value="0" readonly>
                            <p>Total reviews for Clinic ${clinicCount} on Google.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Stars</th>
                        <td>
                            <input type="number" step="0.1" name="dfp_reviews_total_stars_${clinicCount}" value="0" readonly class="regular-text">
                            <p class="description">Total of Stars for Clinic ${clinicCount}</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Clinic Name</th>
                        <td>
                            <input type="text" name="dfp_reviews_clinic_id_${clinicCount}" value="">
                            <p>Enter the custom Clinic Name for Clinic ${clinicCount}.</p>
                        </td>
                    </tr>
                </table>
            </div>
        `;
        $('#clinics-container').append(clinicHtml);
        $('#clinic-count').val(clinicCount);

        // Scroll to the new clinic section
        $('html, body').animate({
            scrollTop: $('#clinics-container .clinic-section:last').offset().top - 100
        }, 500);

        // Focus on the first input field of the new clinic
        $('#clinics-container .clinic-section:last').find('input:first').focus();

        // Show a notice that settings need to be saved
        if ($('.notice-new-clinic').length === 0) {
            $('.wrap h1').after(
                '<div class="notice notice-info notice-new-clinic is-dismissible">' +
                '<p><strong>New clinic added!</strong> Configure the settings below and click "Save Settings" when ready.</p>' +
                '</div>'
            );
        }
    });

    // Add loading states to forms
    $('#dfp-reviews-form').on('submit', function() {
        $(this).addClass('form-submitting');
        $(this).find('.button-primary').addClass('is-loading').prop('disabled', true);
    });

    $('.button-container-form').on('submit', function() {
        var button = $(this).find('input[type="submit"]');
        button.addClass('is-loading').prop('disabled', true);
        button.val('Updating...');
    });
});