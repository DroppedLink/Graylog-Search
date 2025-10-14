<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}

delete_option('pv_environments');
delete_option('pv_cache_ttl');
delete_option('pv_logs_tail');


