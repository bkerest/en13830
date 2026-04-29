jQuery(document).ready(function($) {
    
    // Initialize positive number enforcement
    enforcePositiveNumbers();
    
    // Prevent any form submission at document level
    $(document).on('submit', '#calculator-form', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
    });
    
    // Span type selection
    $('.span-option').on('click', function() {
        $('.span-option').removeClass('active');
        $(this).addClass('active');
        
        var spanType = $(this).data('span');
        $('#span_type').val(spanType);
        
        if (spanType === 'unequal_double') {
            $('#larger_span_row').show();
            $('#larger_span').prop('required', true);
        } else {
            $('#larger_span_row').hide();
            $('#larger_span').prop('required', false);
        }
        
        updateDiagram(spanType);
    });
    
    // Deflection toggle switch
    $('#use_en_deflection').on('change', function() {
        if ($(this).is(':checked')) {
            $('#custom_deflection_row').hide();
            $('#custom_deflection').prop('required', false);
        } else {
            $('#custom_deflection_row').show();
            $('#custom_deflection').prop('required', true);
        }
    });
    
    // Initialize
    $('.span-option[data-span="single"]').click();
    $('#use_en_deflection').trigger('change');
    
    // Calculate button click handler - MAIN FIX
    $(document).on('click', '.calculate-btn', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        
        console.log('Calculate button clicked');
        
        var formData = {
            action: 'calculate_ix',
            nonce: en13830_ajax.nonce,
            wind_load: $('#wind_load').val(),
            mullion_length: $('#mullion_length').val(),
            left_width: $('#left_width').val(),
            right_width: $('#right_width').val(),
            glazing_height: $('#glazing_height').val(),
            youngs_modulus: $('#youngs_modulus').val(),
            span_type: $('#span_type').val(),
            larger_span: $('#larger_span').val(),
            use_en_deflection: $('#use_en_deflection').is(':checked'),
            custom_deflection: $('#custom_deflection').val()
        };
        
        console.log('Form data:', formData);
        
        // Validate
        if (!validateForm(formData)) {
            alert('Please fill in all required fields.');
            return false;
        }
        
        // Show loading
        $('#loading').show();
        
        // AJAX request
        $.ajax({
            url: en13830_ajax.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('AJAX Success:', response);
                $('#loading').hide();
                
                if (response.success) {
                    displayResults(response.data);
                } else {
                    alert('Calculation failed: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                $('#loading').hide();
                alert('An error occurred: ' + error);
            }
        });
        
        return false;
    });
    
    function validateForm(data) {
        var required = ['wind_load', 'mullion_length', 'left_width', 'right_width', 'glazing_height', 'youngs_modulus'];
        
        for (var i = 0; i < required.length; i++) {
            if (!data[required[i]] || data[required[i]] === '') {
                return false;
            }
        }
        
        if (data.span_type === 'unequal_double' && (!data.larger_span || data.larger_span === '')) {
            return false;
        }
        
        if (!data.use_en_deflection && (!data.custom_deflection || data.custom_deflection === '')) {
            return false;
        }
        
        return true;
    }

    function displayResults(data) {
        $('#result-value').text(data.required_ix);
        
        var details = '<h4>Calculation Details:</h4>';
        details += '<p><strong>Span Type:</strong> ' + data.calculation_details.span_description + '</p>';
        details += '<p><strong>Deflection Limit:</strong> ' + data.calculation_details.deflection_limit_description + '</p>';
        details += '<p><strong>Distributed Load:</strong> ' + data.distributed_load + ' N/m</p>';
        details += '<p><strong>Base Ix:</strong> ' + data.base_ix + ' cm⁴</p>';
        details += '<p><strong>' + data.calculation_details.glazing_check + '</strong></p>';
        details += '<p><strong>' + data.calculation_details.correction_applied + '</strong></p>';
        
        $('#calculation-details').html(details);
        
        // Display suggested profiles if they exist
        if (data.suggested_profiles && data.suggested_profiles.length > 0) {
            displaySuggestedProfiles(data.suggested_profiles, data.required_ix);
        } else {
            // Remove existing suggestions if no profiles found
            $('#suggested-profiles-section').remove();
            
            // Show a message that no profiles were found
            var noProfilesHtml = '<div id="suggested-profiles-section" class="suggestion-table-wrapper">';
            noProfilesHtml += '<h3>Profile Suggestions</h3>';
            noProfilesHtml += '<p style="color: #666; font-style: italic;">No profiles found with Ix >= ' + data.required_ix + ' cm⁴</p>';
            noProfilesHtml += '</div>';
            $('.result-section').after(noProfilesHtml);
        }
    }
    
    function displaySuggestedProfiles(profiles, requiredIx) {
        var html = '<div id="suggested-profiles-section" class="suggestion-table-wrapper">';
        html += '<h3>Suggested Profiles (Ix >= ' + requiredIx + ' cm⁴)</h3>';
        html += '<table class="suggestion-table">';
        html += '<thead><tr>';
        html += '<th>Profile Code</th><th>System</th><th>Width (mm)</th><th>Depth (mm)</th>';
        html += '<th>Ix (cm⁴)</th><th>Iy (cm⁴)</th><th>Utilization (%)</th><th>Preview</th>';
        html += '</tr></thead><tbody>';
        
        profiles.forEach(function(profile) {
            var utilization = (requiredIx > 0) ? ((requiredIx / profile.ix) * 100) : 0;
            var utilizationClass = utilization > 90 ? 'high-utilization' : (utilization > 70 ? 'medium-utilization' : 'low-utilization');
            
            html += '<tr>';
            html += '<td><strong>' + (profile.code || '-') + '</strong></td>';
            html += '<td>' + (profile.system || '-') + '</td>';
            html += '<td>' + (profile.width || '-') + '</td>';
            html += '<td>' + (profile.depth || '-') + '</td>';
            html += '<td><strong>' + (profile.ix || '-') + '</strong></td>';
            html += '<td>' + (profile.iy || '-') + '</td>';
            html += '<td class="' + utilizationClass + '">' + utilization.toFixed(1) + '%</td>';
            html += '<td>';
            if (profile.image_url && profile.image_url !== '') {
                // Removed inline styles - let CSS handle the sizing
                html += '<img src="' + profile.image_url + '" alt="Profile ' + (profile.code || '') + '">';
            } else {
                html += '–';
            }
            html += '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table></div>';
        
        // Remove existing suggestions and add new ones
        $('#suggested-profiles-section').remove();
        $('.result-section').after(html);
    }
    
    function updateDiagram(spanType) {
        var diagramHtml = '';
        
        // Get the plugin URL from the existing working images in the span selector
        var existingImg = $('.span-option[data-span="single"] img');
        var pluginUrl = '';
        
        if (existingImg.length > 0) {
            var imgSrc = existingImg.attr('src');
            // Extract the base URL (everything before 'assets/images/')
            pluginUrl = imgSrc.substring(0, imgSrc.indexOf('assets/images/'));
        }
        
        switch(spanType) {
            case 'single':
                diagramHtml = '<div class="diagram-title">Single Span Loading Scheme</div>';
                diagramHtml += '<img src="' + pluginUrl + 'assets/images/single-span.png" alt="Single Span Loading Scheme" class="diagram-image" />';
                break;
            case 'equal_double':
                diagramHtml = '<div class="diagram-title">Equal Double Span Loading Scheme</div>';
                diagramHtml += '<img src="' + pluginUrl + 'assets/images/equal-double-span.png" alt="Equal Double Span Loading Scheme" class="diagram-image" />';
                break;
            case 'unequal_double':
                diagramHtml = '<div class="diagram-title">Unequal Double Span Loading Scheme</div>';
                diagramHtml += '<img src="' + pluginUrl + 'assets/images/unequal-double-span.png" alt="Unequal Double Span Loading Scheme" class="diagram-image" />';
                break;
        }
        
        $('#current-diagram').html(diagramHtml);
    }
    
    // Function to allow only positive numbers (including decimals)
    function enforcePositiveNumbers() {
        $('.number-input').on('input', function() {
            var value = this.value;
            
            // Remove any non-numeric characters except decimal point
            value = value.replace(/[^0-9.]/g, '');
            
            // Ensure only one decimal point
            var parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            
            // Remove leading zeros (except for decimals like 0.5)
            if (value.length > 1 && value[0] === '0' && value[1] !== '.') {
                value = value.substring(1);
            }
            
            // Update the input value
            this.value = value;
        });
        
        // Prevent negative numbers on keypress
        $('.number-input').on('keypress', function(e) {
            var char = String.fromCharCode(e.which);
            
            // Allow: backspace, delete, tab, escape, enter
            if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true)) {
                return;
            }
            
            // Ensure that it is a number or decimal point and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && 
                (e.keyCode !== 46 || $(this).val().indexOf('.') !== -1)) {
                e.preventDefault();
            }
            
            // Prevent minus sign
            if (char === '-') {
                e.preventDefault();
            }
        });
        
        // Prevent paste of invalid content
        $('.number-input').on('paste', function(e) {
            var paste = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
            
            // Check if pasted content is a valid positive number
            if (!/^\d*\.?\d*$/.test(paste) || parseFloat(paste) < 0) {
                e.preventDefault();
            }
        });
    }

});
