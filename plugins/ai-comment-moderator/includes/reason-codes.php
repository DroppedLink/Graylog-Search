<?php
/**
 * Reason Codes Manager
 * 
 * Centralized management of AI moderation reason codes
 * 
 * @package AI_Comment_Moderator
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Reason_Codes {
    
    /**
     * Get all reason codes with their descriptions
     * 
     * @return array Associative array of code => description
     */
    public static function get_codes() {
        return [
            1  => 'Obvious spam - automated/bot content',
            2  => 'Malicious links detected',
            3  => 'Toxic/abusive language',
            4  => 'Off-topic or irrelevant',
            5  => 'Multiple suspicious URLs',
            6  => 'Low-quality content',
            7  => 'Duplicate/repeated comment',
            8  => 'Suspicious user patterns',
            9  => 'Legitimate contribution',
            10 => 'Approved - high quality content'
        ];
    }
    
    /**
     * Get the label/description for a specific code
     * 
     * @param int $code The reason code (1-10)
     * @return string The reason description or 'Unknown' if invalid
     */
    public static function get_code_label($code) {
        $codes = self::get_codes();
        return isset($codes[$code]) ? $codes[$code] : 'Unknown';
    }
    
    /**
     * Validate a reason code
     * 
     * @param int $code The reason code to validate
     * @return bool True if valid (1-10), false otherwise
     */
    public static function is_valid_code($code) {
        return is_numeric($code) && $code >= 1 && $code <= 10;
    }
    
    /**
     * Validate that a reason code matches the decision
     * 
     * @param int $code The reason code
     * @param string $decision The AI decision (spam/approve/hold/toxic)
     * @return bool True if code matches decision type
     */
    public static function is_code_valid_for_decision($code, $decision) {
        if (!self::is_valid_code($code)) {
            return false;
        }
        
        $decision = strtolower($decision);
        $types = self::get_codes_by_type();
        
        // Map decisions to valid code groups
        switch ($decision) {
            case 'spam':
                return in_array($code, $types['spam']);
            
            case 'toxic':
            case 'hold':
                return in_array($code, $types['toxic']) || in_array($code, $types['off_topic']);
            
            case 'approve':
                return in_array($code, $types['legitimate']);
            
            default:
                return false;
        }
    }
    
    /**
     * Get codes grouped by decision type (for reference)
     * 
     * @return array Grouped codes
     */
    public static function get_codes_by_type() {
        return [
            'spam' => [1, 2, 5, 6, 7, 8],
            'toxic' => [3],
            'off_topic' => [4],
            'legitimate' => [9, 10]
        ];
    }
    
    /**
     * Format a code for display with optional label
     * 
     * @param int $code The reason code
     * @param bool $include_label Whether to include the label text
     * @return string Formatted code display
     */
    public static function format_code_display($code, $include_label = true) {
        if (!self::is_valid_code($code)) {
            return 'Unknown';
        }
        
        $display = '<span class="reason-code-badge code-' . $code . '">' . $code . '</span>';
        
        if ($include_label) {
            $display .= ' ' . self::get_code_label($code);
        }
        
        return $display;
    }
    
    /**
     * Get CSS classes for a reason code (for styling)
     * 
     * @param int $code The reason code
     * @return string CSS class names
     */
    public static function get_code_css_class($code) {
        $types = self::get_codes_by_type();
        
        if (in_array($code, $types['spam'])) {
            return 'reason-code-spam';
        } elseif (in_array($code, $types['toxic'])) {
            return 'reason-code-toxic';
        } elseif (in_array($code, $types['off_topic'])) {
            return 'reason-code-off-topic';
        } elseif (in_array($code, $types['legitimate'])) {
            return 'reason-code-legitimate';
        }
        
        return 'reason-code-unknown';
    }
}
