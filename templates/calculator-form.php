<div id="en13830-calculator" class="en13830-calculator-container">
    <div class="calculator-header">
        <h3>ELVIAL Beam Ix Calculator</h3>
        <h5> Based on EN 13830</h5>
        <h6> Only for reference!</h6>
    </div>

    <div class="calculator-content">
        <div class="input-section">
            <div class="span-selector">
                <h4>Select Span Configuration:</h4>
                <div class="span-options">
                    <div class="span-option" data-span="single">
                        <img src="<?php echo EN13830_PLUGIN_URL; ?>assets/images/single-span.png" alt="Single Span" />
                        <p>Single Span</p>
                    </div>
                    <div class="span-option" data-span="equal_double">
                        <img src="<?php echo EN13830_PLUGIN_URL; ?>assets/images/equal-double-span.png" alt="Equal Double Span" />
                        <p>Equal Double Span</p>
                    </div>
                    <div class="span-option" data-span="unequal_double">
                        <img src="<?php echo EN13830_PLUGIN_URL; ?>assets/images/unequal-double-span.png" alt="Unequal Double Span" />
                        <p>Unequal Double Span</p>
                    </div>
                </div>
            </div>
            
            <form id="calculator-form" class="calculator-form">
                <div class="form-row">
                    <label for="wind_load">P = Design wind load (N/m² or Pa):</label>
                    <input type="text" id="wind_load" name="wind_load" class="number-input" required>
                </div>
                
                <div class="form-row">
                    <label for="mullion_length">L = Mullion length (m):</label>
                    <input type="text" id="mullion_length" name="mullion_length" class="number-input" required>
                </div>
                
                <div class="form-row" id="larger_span_row" style="display: none;">
                    <label for="larger_span">L1 = Larger span (m):</label>
                    <input type="text" id="larger_span" name="larger_span" class="number-input">
                </div>
                
                <div class="form-row">
                    <label for="left_width">A = Left portion width (m):</label>
                    <input type="text" id="left_width" name="left_width" class="number-input" required>
                </div>
                
                <div class="form-row">
                    <label for="right_width">B = Right portion width (m):</label>
                    <input type="text" id="right_width" name="right_width" class="number-input" required>
                </div>
                
                <div class="form-row">
                    <label for="glazing_height">H2 = Biggest height of glazing panel (m):</label>
                    <input type="text" id="glazing_height" name="glazing_height" class="number-input" required>
                </div>
                
                <div class="form-row">
                    <label for="youngs_modulus">E = Young's Modulus (N/mm²):</label>
                    <input type="text" id="youngs_modulus" name="youngs_modulus" class="number-input" value="70000" required>
                </div>
                
                <div class="form-row">
                    <label class="toggle-label">
                        <input type="checkbox" id="use_en_deflection" name="use_en_deflection" value="1" checked>
                        <span class="toggle-slider"></span>
                        Deflection According to EN (mm)
                    </label>
                </div>
                
                <div class="form-row" id="custom_deflection_row" style="display: none;">
                    <label for="custom_deflection">Custom deflection value (mm):</label>
                    <input type="text" id="custom_deflection" name="custom_deflection" class="number-input">
                </div>
                
                <input type="hidden" id="span_type" name="span_type" value="single">
                
                <div class="button-group">
                    <button type="button" class="calculate-btn">Calculate</button>
                </div>
            </form>

        </div>
        
        <div class="diagram-section">
            <div id="current-diagram" class="diagram-container">
                <!-- Diagram will be loaded here -->
            </div>
        </div>
    </div>
    
    <div class="result-section">
        <div class="result-display">
            <label>Required Ix = </label>
            <span id="result-value">0.00</span>
            <span class="unit">cm⁴</span>
        </div>
        <div id="calculation-details" class="calculation-details"></div>
    </div>
    
    <div id="loading" class="loading" style="display: none;">
        <div class="spinner"></div>
        <p>Calculating...</p>
    </div>
</div>
