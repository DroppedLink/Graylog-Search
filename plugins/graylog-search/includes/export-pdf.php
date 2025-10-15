<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * PDF Export Handler
 * Generate professional PDF reports using mPDF
 */

// PDF export AJAX handler
add_action('wp_ajax_graylog_export_pdf', 'graylog_export_pdf_handler');
function graylog_export_pdf_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Get parameters
    $results = json_decode(stripslashes($_POST['results']), true);
    $filters = json_decode(stripslashes($_POST['filters']), true);
    $include_logo = isset($_POST['include_logo']) && $_POST['include_logo'] === 'true';
    $include_filters = isset($_POST['include_filters']) && $_POST['include_filters'] === 'true';
    $include_summary = isset($_POST['include_summary']) && $_POST['include_summary'] === 'true';
    
    if (empty($results)) {
        wp_send_json_error(array('message' => 'No results to export'));
        return;
    }
    
    try {
        // Check if mPDF is available (we'll use a WordPress-compatible approach)
        $pdf_content = graylog_generate_pdf_content($results, $filters, $include_logo, $include_filters, $include_summary);
        
        // For now, generate HTML-based PDF (browser print to PDF)
        // In production, integrate mPDF library
        wp_send_json_success(array(
            'html' => $pdf_content,
            'message' => 'PDF content generated'
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'PDF generation failed: ' . $e->getMessage()));
    }
}

// Generate PDF content (HTML)
function graylog_generate_pdf_content($results, $filters, $include_logo, $include_filters, $include_summary) {
    $html = '<!DOCTYPE html><html><head>';
    $html .= '<meta charset="UTF-8">';
    $html .= '<title>Graylog Search Report</title>';
    $html .= '<style>';
    $html .= '
        @page { margin: 2cm; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10pt; 
            line-height: 1.4;
            color: #333;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            padding-bottom: 15px;
            border-bottom: 2px solid #0073aa;
        }
        .logo { 
            max-width: 200px; 
            max-height: 80px; 
            margin-bottom: 10px;
        }
        h1 { 
            color: #0073aa; 
            font-size: 24pt; 
            margin: 10px 0;
        }
        .report-date {
            color: #666;
            font-size: 9pt;
            margin-top: 5px;
        }
        .filters-section {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid #0073aa;
        }
        .filters-section h2 {
            margin-top: 0;
            font-size: 14pt;
            color: #0073aa;
        }
        .filter-item {
            margin: 8px 0;
            font-size: 9pt;
        }
        .filter-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 120px;
        }
        .summary-section {
            background: #e8f4f8;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary-section h2 {
            margin-top: 0;
            font-size: 14pt;
            color: #0073aa;
        }
        .summary-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 10px;
        }
        .stat-box {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 3px;
            min-width: 100px;
        }
        .stat-number {
            font-size: 20pt;
            font-weight: bold;
            color: #0073aa;
        }
        .stat-label {
            font-size: 8pt;
            color: #666;
            margin-top: 5px;
        }
        .results-section h2 {
            font-size: 14pt;
            color: #0073aa;
            margin-bottom: 15px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px;
            font-size: 8pt;
        }
        th { 
            background: #0073aa; 
            color: white; 
            padding: 8px; 
            text-align: left;
            font-weight: bold;
        }
        td { 
            padding: 6px 8px; 
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        tr:nth-child(even) { 
            background: #f9f9f9; 
        }
        .timestamp {
            white-space: nowrap;
            color: #666;
        }
        .source {
            font-weight: bold;
            color: #0073aa;
        }
        .message {
            word-break: break-word;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }
        .page-break {
            page-break-after: always;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    ';
    $html .= '</style></head><body>';
    
    // Header
    $html .= '<div class="header">';
    
    // Logo (if included and available)
    if ($include_logo) {
        $logo_url = get_option('graylog_report_logo', '');
        if ($logo_url) {
            $html .= '<img src="' . esc_url($logo_url) . '" class="logo" alt="Company Logo">';
        }
    }
    
    $html .= '<h1>Graylog Search Report</h1>';
    $html .= '<div class="report-date">Generated: ' . date('F j, Y - g:i A') . '</div>';
    $html .= '<div class="report-date">Generated by: ' . wp_get_current_user()->display_name . '</div>';
    $html .= '</div>';
    
    // Filters section
    if ($include_filters && !empty($filters)) {
        $html .= '<div class="filters-section">';
        $html .= '<h2>Search Parameters</h2>';
        
        if (!empty($filters['fqdn'])) {
            $html .= '<div class="filter-item"><span class="filter-label">Hostname:</span> ' . esc_html($filters['fqdn']) . '</div>';
        }
        if (!empty($filters['search_terms'])) {
            $html .= '<div class="filter-item"><span class="filter-label">Search Terms:</span> ' . esc_html($filters['search_terms']) . '</div>';
        }
        if (!empty($filters['filter_out'])) {
            $html .= '<div class="filter-item"><span class="filter-label">Filter Out:</span> ' . esc_html($filters['filter_out']) . '</div>';
        }
        if (!empty($filters['time_range'])) {
            $html .= '<div class="filter-item"><span class="filter-label">Time Range:</span> ' . graylog_format_time_range($filters['time_range']) . '</div>';
        }
        
        $html .= '</div>';
    }
    
    // Summary section
    if ($include_summary) {
        $html .= '<div class="summary-section">';
        $html .= '<h2>Summary</h2>';
        
        // Calculate stats
        $total_results = count($results);
        $unique_sources = count(array_unique(array_column($results, 'source')));
        $time_span = graylog_calculate_time_span($results);
        
        $html .= '<div class="summary-stats">';
        $html .= '<div class="stat-box"><div class="stat-number">' . $total_results . '</div><div class="stat-label">Total Results</div></div>';
        $html .= '<div class="stat-box"><div class="stat-number">' . $unique_sources . '</div><div class="stat-label">Unique Sources</div></div>';
        $html .= '<div class="stat-box"><div class="stat-number">' . $time_span . '</div><div class="stat-label">Time Span</div></div>';
        $html .= '</div>';
        
        $html .= '</div>';
    }
    
    // Results table
    $html .= '<div class="results-section">';
    $html .= '<h2>Log Entries (' . count($results) . ' results)</h2>';
    $html .= '<table>';
    $html .= '<thead><tr>';
    $html .= '<th style="width: 15%;">Timestamp</th>';
    $html .= '<th style="width: 15%;">Source</th>';
    $html .= '<th style="width: 70%;">Message</th>';
    $html .= '</tr></thead>';
    $html .= '<tbody>';
    
    $row_count = 0;
    foreach ($results as $result) {
        $row_count++;
        
        // Add page break every 30 rows
        if ($row_count > 1 && $row_count % 30 === 0) {
            $html .= '</tbody></table>';
            $html .= '<div class="page-break"></div>';
            $html .= '<table>';
            $html .= '<thead><tr>';
            $html .= '<th style="width: 15%;">Timestamp</th>';
            $html .= '<th style="width: 15%;">Source</th>';
            $html .= '<th style="width: 70%;">Message</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';
        }
        
        $html .= '<tr>';
        $html .= '<td class="timestamp">' . esc_html($result['timestamp']) . '</td>';
        $html .= '<td class="source">' . esc_html($result['source'] ?? 'N/A') . '</td>';
        $html .= '<td class="message">' . esc_html($result['message']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    $html .= '</div>';
    
    // Footer
    $html .= '<div class="footer">';
    $html .= 'Generated by Graylog Search Plugin for WordPress<br>';
    $html .= 'Page ' . ceil($row_count / 30) . ' of ' . ceil(count($results) / 30);
    $html .= '</div>';
    
    $html .= '</body></html>';
    
    return $html;
}

// Format time range helper
function graylog_format_time_range($minutes) {
    if ($minutes < 60) {
        return $minutes . ' minutes';
    } elseif ($minutes < 1440) {
        return round($minutes / 60, 1) . ' hours';
    } elseif ($minutes < 10080) {
        return round($minutes / 1440, 1) . ' days';
    } else {
        return round($minutes / 10080, 1) . ' weeks';
    }
}

// Calculate time span helper
function graylog_calculate_time_span($results) {
    if (empty($results)) {
        return 'N/A';
    }
    
    $timestamps = array_column($results, 'timestamp');
    if (empty($timestamps)) {
        return 'N/A';
    }
    
    // Convert to unix timestamps
    $unix_timestamps = array_map(function($ts) {
        return strtotime($ts);
    }, $timestamps);
    
    $min = min($unix_timestamps);
    $max = max($unix_timestamps);
    $diff = $max - $min;
    
    if ($diff < 60) {
        return $diff . ' seconds';
    } elseif ($diff < 3600) {
        return round($diff / 60) . ' minutes';
    } elseif ($diff < 86400) {
        return round($diff / 3600, 1) . ' hours';
    } else {
        return round($diff / 86400, 1) . ' days';
    }
}

// Upload logo handler
add_action('wp_ajax_graylog_upload_report_logo', 'graylog_upload_report_logo_handler');
function graylog_upload_report_logo_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    if (empty($_FILES['logo'])) {
        wp_send_json_error(array('message' => 'No file uploaded'));
        return;
    }
    
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $file = $_FILES['logo'];
    
    // Validate file type
    $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
    if (!in_array($file['type'], $allowed_types)) {
        wp_send_json_error(array('message' => 'Invalid file type. Please upload JPG, PNG, or GIF.'));
        return;
    }
    
    // Upload file
    $upload = wp_handle_upload($file, array('test_form' => false));
    
    if (isset($upload['error'])) {
        wp_send_json_error(array('message' => $upload['error']));
        return;
    }
    
    // Save logo URL
    update_option('graylog_report_logo', $upload['url']);
    
    wp_send_json_success(array(
        'message' => 'Logo uploaded successfully',
        'url' => $upload['url']
    ));
}

// Get logo URL
add_action('wp_ajax_graylog_get_report_logo', 'graylog_get_report_logo_handler');
function graylog_get_report_logo_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $logo_url = get_option('graylog_report_logo', '');
    
    wp_send_json_success(array('logo_url' => $logo_url));
}

// Delete logo
add_action('wp_ajax_graylog_delete_report_logo', 'graylog_delete_report_logo_handler');
function graylog_delete_report_logo_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    delete_option('graylog_report_logo');
    
    wp_send_json_success(array('message' => 'Logo deleted successfully'));
}

