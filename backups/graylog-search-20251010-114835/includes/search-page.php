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
        
        <div class="graylog-search-form-container">
            <form id="graylog-search-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="search_fqdn">FQDN (Hostname)</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="search_fqdn" 
                                   name="search_fqdn" 
                                   class="regular-text"
                                   placeholder="e.g., server01.example.com">
                            <p class="description">Search by Fully Qualified Domain Name</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="search_terms">Additional Search Terms</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="search_terms" 
                                   name="search_terms" 
                                   class="regular-text"
                                   placeholder="error, warning, specific text">
                            <p class="description">Enter additional keywords or phrases (space-separated)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="filter_out">Filter Out (Exclude)</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="filter_out" 
                                   name="filter_out" 
                                   class="regular-text"
                                   placeholder="debug, info">
                            <p class="description">Exclude messages containing these terms (space-separated)</p>
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
                </p>
            </form>
        </div>
        
        <div id="search-results-container" style="display: none;">
            <hr>
            <h2>Search Results <span id="result-count"></span></h2>
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

