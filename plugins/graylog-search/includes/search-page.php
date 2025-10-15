<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Search page content
function graylog_search_page() {
    $api_url = get_option('graylog_api_url', '');
    $api_token = get_option('graylog_api_token', '');
    
    // Check if settings are configured
    if (empty($api_url) || empty($api_token)) {
        ?>
        <div class="wrap">
            <h1>Graylog Search</h1>
            <div class="notice notice-warning">
                <p><strong>Configuration Required:</strong> Please configure your Graylog API settings first.</p>
                <p><a href="<?php echo admin_url('admin.php?page=graylog-search-settings'); ?>" class="button button-primary">Go to Settings</a></p>
            </div>
        </div>
        <?php
        return;
    }
    ?>
    <div class="wrap graylog-search-wrap">
        <h1>Graylog Log Search</h1>
        
        <!-- Quick Filters, Saved Searches, and Recent Searches -->
        <div class="graylog-search-helpers" style="display: flex; gap: 20px; margin-bottom: 20px;">
            
            <!-- Quick Filters -->
            <div class="graylog-helper-panel" style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #dee2e6;">
                <h3 style="margin: 0 0 10px 0; font-size: 14px;">⚡ Quick Filters</h3>
                <div id="quick-filters-list" style="display: flex; flex-wrap: wrap; gap: 5px;">
                    <button class="button button-small quick-filter-btn" data-name="Errors (Last Hour)">
                        🔴 Errors (1h)
                    </button>
                    <button class="button button-small quick-filter-btn" data-name="Warnings (Last Hour)">
                        ⚠️ Warnings (1h)
                    </button>
                    <button class="button button-small quick-filter-btn" data-name="Errors (Today)">
                        🔴 Errors (24h)
                    </button>
                    <button class="button button-small quick-filter-btn" data-name="All Logs (Last Hour)">
                        📋 All (1h)
                    </button>
                </div>
            </div>
            
            <!-- Saved Searches -->
            <div class="graylog-helper-panel" style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #dee2e6;">
                <h3 style="margin: 0 0 10px 0; font-size: 14px;">💾 Saved Searches <button class="button button-small" id="save-current-search-btn" style="float: right; padding: 2px 8px;">Save Current</button></h3>
                <div id="saved-searches-list" style="max-height: 150px; overflow-y: auto;">
                    <p style="color: #666; font-size: 12px; margin: 0;">No saved searches yet</p>
                </div>
            </div>
            
            <!-- Recent Searches -->
            <div class="graylog-helper-panel" style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #dee2e6;">
                <h3 style="margin: 0 0 10px 0; font-size: 14px;">🕒 Recent Searches</h3>
                <div id="recent-searches-list" style="max-height: 150px; overflow-y: auto;">
                    <p style="color: #666; font-size: 12px; margin: 0;">No recent searches</p>
                </div>
            </div>
            
        </div>
        
        <div class="graylog-search-form-container">
            <form id="graylog-search-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="search_fqdn">Hostname</label>
                        </th>
                        <td>
                            <textarea id="search_fqdn" 
                                      name="search_fqdn" 
                                      class="regular-text"
                                      rows="3"
                                      placeholder="e.g., server01 or server01.example.com&#10;Multiple hostnames: one per line or comma-separated"></textarea>
                            <p class="description">Search by hostname or FQDN (one per line or comma-separated)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="search_terms">Additional Search Terms</label>
                        </th>
                        <td>
                            <textarea id="search_terms" 
                                      name="search_terms" 
                                      class="regular-text"
                                      rows="3"
                                      placeholder="error, warning, specific text&#10;Multiple terms: one per line or comma-separated"></textarea>
                            <p class="description">Enter additional keywords or phrases (one per line or comma-separated)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="filter_out">Filter Out (Exclude)</label>
                        </th>
                        <td>
                            <textarea id="filter_out" 
                                      name="filter_out" 
                                      class="regular-text"
                                      rows="3"
                                      placeholder="debug, info&#10;Multiple terms: one per line or comma-separated"></textarea>
                            <p class="description">Exclude messages containing these terms (one per line or comma-separated)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="time_range">Time Range</label>
                        </th>
                        <td>
                            <select id="time_range" name="time_range" class="regular-text">
                                <option value="3600">Last Hour</option>
                                <option value="86400" selected>Last Day</option>
                                <option value="604800">Last Week</option>
                            </select>
                            <p class="description">Select the time range for your search</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="result_limit">Result Limit</label>
                        </th>
                        <td>
                            <select id="result_limit" name="result_limit" class="regular-text">
                                <option value="50">50 results</option>
                                <option value="100" selected>100 results</option>
                                <option value="250">250 results</option>
                                <option value="500">500 results</option>
                            </select>
                            <p class="description">Maximum number of results to return</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-search"></span> Search Logs
                    </button>
                    <button type="button" id="clear-search" class="button button-secondary button-large">
                        <span class="dashicons dashicons-no"></span> Clear
                    </button>
                    
                    <span class="auto-refresh-controls">
                        <label>
                            <input type="checkbox" id="auto-refresh-toggle">
                            Auto-refresh every
                        </label>
                        <select id="auto-refresh-interval">
                            <option value="5">5s</option>
                            <option value="10" selected>10s</option>
                            <option value="30">30s</option>
                            <option value="60">60s</option>
                        </select>
                    </span>
                </p>
            </form>
        </div>
        
        <div id="search-results-container" style="display: none;">
            <hr>
            <div class="results-toolbar">
                <h2 style="margin: 0;">Search Results <span id="result-count"></span></h2>
                <div class="results-toolbar-actions">
                    <div class="parse-controls">
                        <label title="Enable parsing of structured log formats">
                            <input type="checkbox" id="parse-toggle"> Parse
                        </label>
                        <div class="parse-format-options" style="display: none;">
                            <label><input type="checkbox" class="parse-format" value="json" checked> JSON</label>
                            <label><input type="checkbox" class="parse-format" value="kv" checked> key=value</label>
                            <label><input type="checkbox" class="parse-format" value="cef" checked> CEF</label>
                            <label><input type="checkbox" class="parse-format" value="leef" checked> LEEF</label>
                        </div>
                    </div>
                    <div class="export-controls">
                        <button class="export-btn" title="Export visible results">
                            <span class="dashicons dashicons-download"></span> Export
                        </button>
                        <div class="export-menu" style="display: none;">
                            <button class="export-csv">CSV</button>
                            <button class="export-json">JSON</button>
                            <button class="export-txt">Text</button>
                            <button class="export-copy">📋 Copy</button>
                        </div>
                    </div>
                    <div class="timezone-controls">
                        <select id="timezone-selector" class="timezone-selector" title="Select timezone for timestamps">
                            <option value="UTC">🌐 UTC/GMT</option>
                            <?php
                            $timezones = graylog_get_available_timezones();
                            foreach ($timezones as $group => $zones) {
                                if ($group !== 'UTC/GMT') {
                                    echo '<optgroup label="' . esc_attr($group) . '">';
                                    foreach ($zones as $value => $label) {
                                        echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                                    }
                                    echo '</optgroup>';
                                }
                            }
                            ?>
                        </select>
                        <button id="timezone-toggle-btn" class="timezone-toggle-btn" title="Toggle between original and converted times">
                            Show Original
                        </button>
                    </div>
                    <button class="resolve-all-ips-btn" title="Resolve all IP addresses to hostnames">
                        <span class="dashicons dashicons-admin-site"></span>
                        Resolve All IPs
                        <span class="ip-count">0</span>
                    </button>
                </div>
            </div>
            <div class="active-filters-container"></div>
            <div id="search-results"></div>
        </div>
        
        <div id="search-loading" style="display: none;">
            <div class="graylog-loading">
                <span class="spinner is-active"></span>
                <p>Searching logs...</p>
            </div>
        </div>
        
        <div id="search-error" style="display: none;">
            <div class="notice notice-error">
                <p id="error-message"></p>
            </div>
        </div>
    </div>
    <?php
}

