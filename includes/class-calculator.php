<?php
class EN13830_Calculator_Engine {
    
    public function calculate($data) {
        $P = floatval($data['wind_load']);
        $L = floatval($data['mullion_length']);
        $A = floatval($data['left_width']);
        $B = floatval($data['right_width']);
        $H2 = floatval($data['glazing_height']);
        $E = floatval($data['youngs_modulus']);
        $span_type = sanitize_text_field($data['span_type']);
        $L1 = isset($data['larger_span']) ? floatval($data['larger_span']) : 0;
        
        // checkbox → string 'true', 'false', 'on', or unset
        $use_en_deflection = isset($data['use_en_deflection']) && $data['use_en_deflection'] !== 'false' && $data['use_en_deflection'] !== false;

        $custom_deflection = isset($data['custom_deflection']) ? floatval($data['custom_deflection']) : 0;

        $L_mm = $L * 1000;
        $H2_mm = $H2 * 1000;
        $L1_mm = $L1 * 1000;  // FIXED: Added missing conversion

        // distributed load q
        $q = (($A + $B) / 2) * $P;

        // deflection f
        if ($use_en_deflection) {
            $f = $this->get_en13830_deflection_limit($span_type, $L_mm, $L1_mm);
            $deflection_source = "EN 13830";
        } else {
            if ($custom_deflection > 0) {
                $f = $custom_deflection;
                $deflection_source = "Custom";
            } else {
                return array('error' => 'Custom deflection value is missing or invalid.');
            }
        }

        // FIX: Pass $L1 parameter to the function
        $result = $this->calculate_base_ix_exact($span_type, $P, $A, $B, $L, $E, $f, $H2, $use_en_deflection, $L1);

        // Check if there was an error in the calculation
        if (isset($result['error'])) {
            return array('error' => $result['error']);
        }

        return array(
            'required_ix' => round($result['required_ix'], 2),
            'base_ix' => round($result['base_ix'], 2),
            'correction_factor' => round($result['correction_factor'], 4),
            'R_value' => round($result['R_value'], 4),
            'deflection_limit' => round($result['deflection_limit'], 2),
            'deflection_source' => $deflection_source,
            'distributed_load' => round($result['distributed_load'], 2),
            'span_type' => $span_type,
            'calculation_details' => $this->get_calculation_details(
                $span_type,
                $L_mm,
                $f,
                $result['R_value'],
                $result['correction_factor'],
                $deflection_source,
                $L1_mm  // FIXED: Added missing parameter
            ),
            'suggested_profiles' => $this->getSuggestedProfiles($result['required_ix'])
        );
    }

    private function get_en13830_deflection_limit($span_type, $L_mm, $L1_mm = 0) {
        // Determine effective length based on span type
        switch ($span_type) {
            case 'single':
                $effective_L = $L_mm;
                break;
            case 'equal_double':
                $effective_L = $L_mm / 2;
                break;
            case 'unequal_double':
                $effective_L = $L1_mm;
                break;
            default:
                $effective_L = $L_mm;
        }
        
        // Apply EN 13830 deflection limits using effective length
        if ($effective_L <= 3000) {
            return $effective_L / 200;
        } elseif ($effective_L > 3000 && $effective_L < 7500) {
            return 5 + ($effective_L / 300);
        } else {
            return $effective_L / 250;
        }
    }
    
    private function calculate_base_ix_exact($span_type, $P, $A, $B, $L, $E, $f, $H2, $use_en_deflection, $L1 = 0) {
        error_log(">>> FINAL f used: " . $f);
        
        // q in N/m
        $q = (($A + $B) / 2) * $P;
        $q_mm = $q / 1000;

        // Convert to mm
        $L_mm = $L * 1000;
        $H2_mm = $H2 * 1000;

        if ($span_type === 'equal_double') {
            //$ix_base_mm4 = ($q_mm * pow($L_mm, 4)) / (2960 * $E * $f);
            //$ix_base_cm4 = $ix_base_mm4 / 10000;
            $ix_base_cm4 = (($q * pow($L, 4)) / (2960 * $E * $f))*pow(10, 5);
            $R = ($f / 12.0) * pow(($H2 / ($L / 2)), 2);
            $correction_factor = ($R < 1) ? 1 : $R;
            $corrected_ix_cm4 = $ix_base_cm4 * $correction_factor;

        } elseif ($span_type === 'unequal_double') {
            // Validation for unequal double span
            if ($L1 <= 0 || $L1 >= $L) {
                return array('error' => 'For unequal double span, L1 must be greater than 0 and less than total length L');
            }
            
            $L1_mm = $L1 * 1000;
            $L2 = ($L - $L1);
            $L2_mm = $L2 * 1000;

            $term = 9 * $L * $L1 - 3 * pow($L, 2) - 4 * pow($L1, 2);
            
            // Additional validation for the term
            if ($term <= 0) {
                return array('error' => 'Invalid span configuration: The calculation term is non-positive. Please check your L1 value.');
            }
            
            //$ix_base_mm4 = (($q_mm * pow($L1_mm, 2)) / (384 * $E * $f)) * $term;
            //$ix_base_cm4 = $ix_base_mm4 / 10000;
            $ix_base_cm4 = (($q * pow($L1, 2)) / (384 * $E * $f)) * $term * pow(10, 5);

            $R = ($f / 12.0) * pow(($H2 / $L1), 2);
            $correction_factor = ($R < 1) ? 1 : $R;
            $corrected_ix_cm4 = $ix_base_cm4 * $correction_factor;

        } else { // single span
            //$ix_base_mm4 = (5 * $q_mm * pow($L_mm, 4)) / (384 * $E * $f);
            //$ix_base_cm4 = $ix_base_mm4 / 10000;
            $ix_base_cm4 = ((5 * $q * pow($L, 4)) / (384 * $E * $f))*pow(10, 5);
            $R = ($f / 12.0) * pow(($H2 / $L), 2);
            $correction_factor = ($R < 1) ? 1 : $R;
            $corrected_ix_cm4 = $ix_base_cm4 * $correction_factor;
        }

        return array(
            'required_ix' => round($corrected_ix_cm4, 2),
            'base_ix' => round($ix_base_cm4, 2),
            'correction_factor' => round($correction_factor, 4),
            'R_value' => round($R, 4),
            'distributed_load' => round($q, 2),
            'deflection_limit' => round($f, 2)
        );
    }
    
    private function get_calculation_details($span_type, $L_mm, $f, $R, $correction_factor, $deflection_source, $L1_mm = 0) {  // FIXED: Added $L1_mm parameter
        $details = array();
        $details['span_description'] = $this->get_span_description($span_type);
        
        if ($deflection_source === "EN 13830") {
            $details['deflection_limit_description'] = $this->get_en_deflection_description($span_type, $L_mm, $L1_mm, $f);
        } else {
            $details['deflection_limit_description'] = "Custom deflection: " . round($f, 2) . " mm";
        }
        
        $details['glazing_check'] = "Glazing edge deflection ratio R = " . round($R, 4);
        
        if ($correction_factor > 1) {
            $details['correction_applied'] = "Correction factor R = " . round($correction_factor, 4) . " applied (R > 1)";
        } else {
            $details['correction_applied'] = "No correction factor needed (R ≤ 1)";
        }
        
        return $details;
    }
    
    private function get_span_description($span_type) {
        switch ($span_type) {
            case 'single':
                return 'Single Span Loading Scheme';
            case 'equal_double':
                return 'Equal Double Span Loading Scheme';
            case 'unequal_double':
                return 'Unequal Double Span Loading Scheme';
            default:
                return 'Unknown span type';
        }
    }
    
    private function get_en_deflection_description($span_type, $L_mm, $L1_mm, $f) {
        // Determine effective length and description based on span type
        switch ($span_type) {
            case 'single':
                $effective_L = $L_mm;
                $length_desc = "L";
                break;
            case 'equal_double':
                $effective_L = $L_mm / 2;
                $length_desc = "L/2";
                break;
            case 'unequal_double':
                $effective_L = $L1_mm;
                $length_desc = "L1";
                break;
            default:
                $effective_L = $L_mm;
                $length_desc = "L";
        }
        
        // Generate description based on effective length
        if ($effective_L <= 3000) {
            return "EN 13830: {$length_desc}/200 = " . round($f, 2) . " mm";
        } elseif ($effective_L > 3000 && $effective_L < 7500) {
            return "EN 13830: 5 + {$length_desc}/300 = " . round($f, 2) . " mm";
        } else {
            return "EN 13830: {$length_desc}/250 = " . round($f, 2) . " mm";
        }
    }
    
    private function getSuggestedProfiles($required_ix) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'en13830_profiles';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table
            WHERE ix >= %f
            ORDER BY ix ASC
            LIMIT 10
        ", $required_ix));
        
        return $results; // Return the raw results, not as ARRAY_A
    }

}
?>
