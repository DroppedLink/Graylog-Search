<?php
if (!defined('WP_UNINSTALL_PLUGIN')) { die; }

// Remove new keys
delete_option('sp_environments');
delete_option('sp_cache_ttl');
delete_option('sp_logs_tail');

// Remove legacy keys
delete_option('pv_environments');
delete_option('pv_cache_ttl');
delete_option('pv_logs_tail');


