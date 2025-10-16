<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Prompt_Manager {
    
    /**
     * Get all prompts
     */
    public static function get_prompts($active_only = false) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_comment_prompts';
        $where = $active_only ? 'WHERE is_active = 1' : '';
        
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY name ASC");
    }
    
    /**
     * Get a single prompt by ID
     */
    public static function get_prompt($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_comment_prompts';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    /**
     * Create a new prompt
     */
    public static function create_prompt($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_comment_prompts';
        
        $prompt_data = array(
            'name' => sanitize_text_field($data['name']),
            'prompt_text' => wp_kses_post($data['prompt_text']),
            'action_approve' => sanitize_text_field($data['action_approve']),
            'action_spam' => sanitize_text_field($data['action_spam']),
            'action_trash' => sanitize_text_field($data['action_trash']),
            'category' => sanitize_text_field($data['category']),
            'is_active' => isset($data['is_active']) ? 1 : 0
        );
        
        $result = $wpdb->insert($table, $prompt_data);
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update an existing prompt
     */
    public static function update_prompt($id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_comment_prompts';
        
        $prompt_data = array(
            'name' => sanitize_text_field($data['name']),
            'prompt_text' => wp_kses_post($data['prompt_text']),
            'action_approve' => sanitize_text_field($data['action_approve']),
            'action_spam' => sanitize_text_field($data['action_spam']),
            'action_trash' => sanitize_text_field($data['action_trash']),
            'category' => sanitize_text_field($data['category']),
            'is_active' => isset($data['is_active']) ? 1 : 0
        );
        
        return $wpdb->update($table, $prompt_data, array('id' => $id));
    }
    
    /**
     * Delete a prompt
     */
    public static function delete_prompt($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_comment_prompts';
        return $wpdb->delete($table, array('id' => $id));
    }
    
    /**
     * Export prompts to JSON
     */
    public static function export_prompts($prompt_ids = array()) {
        $prompts = array();
        
        if (empty($prompt_ids)) {
            // Export all prompts
            $prompts = self::get_prompts();
        } else {
            // Export specific prompts
            foreach ($prompt_ids as $id) {
                $prompt = self::get_prompt($id);
                if ($prompt) {
                    $prompts[] = $prompt;
                }
            }
        }
        
        // Convert to array format
        $export_data = array(
            'version' => '2.1.0',
            'exported_at' => current_time('mysql'),
            'prompts' => array()
        );
        
        foreach ($prompts as $prompt) {
            $export_data['prompts'][] = array(
                'name' => $prompt->name,
                'prompt_text' => $prompt->prompt_text,
                'action_approve' => $prompt->action_approve,
                'action_spam' => $prompt->action_spam,
                'action_trash' => $prompt->action_trash,
                'category' => $prompt->category,
                'is_active' => $prompt->is_active
            );
        }
        
        return json_encode($export_data, JSON_PRETTY_PRINT);
    }
    
    /**
     * Import prompts from JSON
     */
    public static function import_prompts($json_data, $overwrite = false) {
        $data = json_decode($json_data, true);
        
        if (!$data || !isset($data['prompts'])) {
            return array(
                'success' => false,
                'error' => 'Invalid import data format'
            );
        }
        
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($data['prompts'] as $prompt_data) {
            // Check if prompt with same name exists
            global $wpdb;
            $table = $wpdb->prefix . 'ai_comment_prompts';
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table WHERE name = %s",
                $prompt_data['name']
            ));
            
            if ($existing && !$overwrite) {
                $skipped++;
                continue;
            }
            
            if ($existing && $overwrite) {
                // Update existing
                $result = self::update_prompt($existing->id, $prompt_data);
                if ($result !== false) {
                    $imported++;
                } else {
                    $errors++;
                }
            } else {
                // Create new
                $result = self::create_prompt($prompt_data);
                if ($result) {
                    $imported++;
                } else {
                    $errors++;
                }
            }
        }
        
        return array(
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        );
    }
    
    /**
     * Process prompt template with variables (Enhanced with context)
     */
    public static function process_prompt_template($prompt_text, $comment_id) {
        global $wpdb;
        
        // Check if this is a remote comment
        $remote_comment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ai_remote_comments WHERE id = %d",
            $comment_id
        ));
        
        if ($remote_comment) {
            // Handle remote comment
            $comment = (object) array(
                'comment_content' => $remote_comment->comment_content,
                'comment_author' => $remote_comment->comment_author,
                'comment_author_email' => $remote_comment->comment_author_email,
                'comment_author_url' => '',
                'comment_date' => $remote_comment->comment_date,
                'comment_post_ID' => $remote_comment->post_id
            );
            
            // For remote comments, we don't have local post data
            $post = null;
            $categories = '';
            $tags = '';
            $previous_comments = 0; // Can't query remote site efficiently
            
            // Get remote site name
            $remote_site = $wpdb->get_row($wpdb->prepare(
                "SELECT site_name, site_url FROM {$wpdb->prefix}ai_remote_sites WHERE id = %d",
                $remote_comment->site_id
            ));
            
            $site_name = $remote_site ? $remote_site->site_name : 'Remote Site';
            $site_url = $remote_site ? $remote_site->site_url : '';
            $post_title = $remote_comment->post_title ?: 'Unknown Post';
            
        } else {
            // Handle local comment
            $comment = get_comment($comment_id);
            if (!$comment) {
                return $prompt_text;
            }
            
            $post = get_post($comment->comment_post_ID);
            
            // Get post categories and tags
            $categories = '';
            $tags = '';
            if ($post) {
                $cats = get_the_category($post->ID);
                $categories = !empty($cats) ? implode(', ', wp_list_pluck($cats, 'name')) : '';
                
                $post_tags = get_the_tags($post->ID);
                $tags = !empty($post_tags) ? implode(', ', wp_list_pluck($post_tags, 'name')) : '';
            }
            
            // Get author's previous comment count
            $previous_comments = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_author_email = %s AND comment_ID != %d",
                $comment->comment_author_email,
                $comment_id
            ));
            
            $site_name = get_bloginfo('name');
            $site_url = get_site_url();
            $post_title = $post ? $post->post_title : 'Unknown Post';
        }
        
        // Get context analysis (v2.1.0+)
        $context = AI_Comment_Moderator_Context_Analyzer::get_full_context($comment_id);
        
        // Build variables array with legacy and new context variables
        $variables = array(
            // Legacy variables
            '{comment_content}' => $comment->comment_content,
            '{author_name}' => $comment->comment_author,
            '{author_email}' => $comment->comment_author_email,
            '{author_url}' => $comment->comment_author_url,
            '{comment_date}' => $comment->comment_date,
            '{post_title}' => $post_title,
            '{post_url}' => $post ? get_permalink($post->ID) : '',
            '{post_content}' => $post ? wp_trim_words($post->post_content, 100) : '',
            '{post_excerpt}' => $post ? get_the_excerpt($post) : '',
            '{post_categories}' => $categories,
            '{post_tags}' => $tags,
            '{comment_count}' => $post ? get_comments_number($post->ID) : 0,
            '{author_previous_comments}' => $previous_comments,
            '{comment_id}' => $comment_id,
            '{site_name}' => $site_name,
            '{site_url}' => $site_url,
            
            // New context variables (v2.1.0+)
            '{comment_sentiment}' => $context['sentiment'],
            '{comment_language}' => $context['language'],
            '{thread_depth}' => $context['thread']['thread_depth'],
            '{thread_sentiment}' => $context['thread']['thread_sentiment'],
            '{sibling_count}' => $context['thread']['sibling_count'],
            '{time_of_day}' => $context['time']['time_of_day'],
            '{day_of_week}' => $context['time']['day_of_week'],
            '{is_weekend}' => $context['time']['is_weekend'] ? 'yes' : 'no',
            '{site_context}' => $context['site']['category'],
            '{site_description}' => $context['site']['description'],
            '{user_history}' => sprintf(
                '%d total, %d approved, %d spam',
                $context['user_history']['total_comments'],
                $context['user_history']['approved_comments'],
                $context['user_history']['spam_comments']
            ),
            '{user_reputation}' => $context['user_history']['reputation'],
            '{is_new_user}' => $context['user_history']['is_new_user'] ? 'yes' : 'no'
        );
        
        // Replace variables
        $processed = str_replace(array_keys($variables), array_values($variables), $prompt_text);
        
        // Append reason code instructions (v2.2.0+)
        $processed .= "\n\n[IMPORTANT] Your response must include:
1. Decision: APPROVE, SPAM, or HOLD
2. Confidence: 0-100
3. Reason Code: Choose ONE number (1-10) MATCHING your decision:

For SPAM decisions, use codes:
   1 = Obvious spam/bot
   2 = Malicious links
   5 = Multiple suspicious URLs
   6 = Low quality
   7 = Duplicate
   8 = Suspicious patterns

For TOXIC/HOLD decisions, use codes:
   3 = Toxic language
   4 = Off-topic

For APPROVE decisions, use codes:
   9 = Legitimate contribution
   10 = High quality content

Format: Decision: [APPROVE/SPAM/HOLD] | Confidence: [0-100] | Code: [1-10] | Reason: [brief explanation]

CRITICAL: Your reason code MUST match your decision (e.g., don't use code 9 for SPAM)";
        
        return $processed;
    }
    
    /**
     * Parse AI response and determine action (enhanced with reason codes v2.2.0+)
     */
    public static function parse_ai_response($response, $prompt) {
        $original_response = $response;
        $response_upper = strtoupper(trim($response));
        
        // Initialize result
        $result = array(
            'decision' => 'unclear',
            'action' => $prompt->action_approve,
            'confidence' => 0.1,
            'reason_code' => null,
            'reason_text' => null
        );
        
        // Extract reason code (Code: 5)
        if (preg_match('/Code:\s*(\d+)/i', $original_response, $code_match)) {
            $code = (int)$code_match[1];
            // Validate code is 1-10
            if ($code >= 1 && $code <= 10) {
                $result['reason_code'] = $code;
            }
        }
        
        // Validate reason code matches decision (after decision is determined below)
        // This will be checked after we know the decision
        
        // Extract reason explanation (Reason: text)
        if (preg_match('/Reason:\s*(.+?)(?:\||$)/is', $original_response, $reason_match)) {
            $result['reason_text'] = trim($reason_match[1]);
        }
        
        // Extract confidence (Confidence: 85)
        if (preg_match('/Confidence:\s*(\d+)/i', $original_response, $conf_match)) {
            $confidence = (int)$conf_match[1];
            $result['confidence'] = $confidence / 100.0; // Convert to 0-1 scale
        }
        
        // Look for key decision words in the response
        if (strpos($response_upper, 'SPAM') !== false) {
            $result['decision'] = 'spam';
            $result['action'] = $prompt->action_spam;
            if (!isset($conf_match)) {
                $result['confidence'] = self::calculate_confidence($response_upper, 'spam');
            }
        } elseif (strpos($response_upper, 'TOXIC') !== false || strpos($response_upper, 'INAPPROPRIATE') !== false || strpos($response_upper, 'RUDE') !== false) {
            $result['decision'] = 'toxic';
            $result['action'] = $prompt->action_trash;
            if (!isset($conf_match)) {
                $result['confidence'] = self::calculate_confidence($response_upper, 'toxic');
            }
        } elseif (strpos($response_upper, 'APPROVE') !== false || strpos($response_upper, 'GOOD') !== false || strpos($response_upper, 'ACCEPTABLE') !== false) {
            $result['decision'] = 'approve';
            $result['action'] = $prompt->action_approve;
            if (!isset($conf_match)) {
                $result['confidence'] = self::calculate_confidence($response_upper, 'approve');
            }
        } elseif (strpos($response_upper, 'HOLD') !== false) {
            $result['decision'] = 'hold';
            $result['action'] = 'hold';
            if (!isset($conf_match)) {
                $result['confidence'] = 0.5;
            }
        }
        
        // Validate reason code matches decision (v2.2.0+)
        if (!empty($result['reason_code']) && !empty($result['decision'])) {
            if (!AI_Comment_Moderator_Reason_Codes::is_code_valid_for_decision($result['reason_code'], $result['decision'])) {
                // Invalid combination - log warning and clear the code
                error_log(sprintf(
                    'AI Comment Moderator: Invalid reason code %d for decision "%s". Code cleared.',
                    $result['reason_code'],
                    $result['decision']
                ));
                $result['reason_code'] = null;
                $result['reason_text'] = 'Invalid code/decision combination';
            }
        }
        
        return $result;
    }
    
    /**
     * Calculate confidence score based on response content
     */
    private static function calculate_confidence($response, $decision_type) {
        $response = strtolower($response);
        
        // Base confidence scores
        $confidence_map = array(
            'spam' => 0.8,
            'toxic' => 0.8,
            'approve' => 0.7
        );
        
        $base_confidence = isset($confidence_map[$decision_type]) ? $confidence_map[$decision_type] : 0.5;
        
        // Adjust based on certainty indicators
        if (strpos($response, 'definitely') !== false || strpos($response, 'clearly') !== false) {
            $base_confidence += 0.1;
        } elseif (strpos($response, 'maybe') !== false || strpos($response, 'possibly') !== false) {
            $base_confidence -= 0.2;
        } elseif (strpos($response, 'unsure') !== false || strpos($response, 'uncertain') !== false) {
            $base_confidence -= 0.3;
        }
        
        return max(0.1, min(1.0, $base_confidence));
    }
    
    /**
     * Get available action options
     */
    public static function get_action_options() {
        return array(
            'approve' => 'Approve Comment',
            'spam' => 'Mark as Spam',
            'trash' => 'Move to Trash',
            'hold' => 'Hold for Moderation'
        );
    }
    
    /**
     * Get available categories
     */
    public static function get_categories() {
        return array(
            'general' => 'General Moderation',
            'spam' => 'Spam Detection',
            'toxicity' => 'Toxicity Detection',
            'quality' => 'Quality Assessment',
            'custom' => 'Custom'
        );
    }
    
    /**
     * Get default prompt templates
     */
    public static function get_default_templates() {
        return array(
            'spam_detection' => array(
                'name' => 'Spam Detection',
                'prompt' => 'Analyze this comment for spam characteristics. Comment: "{comment_content}" by {author_name} ({author_email}) on post "{post_title}". Respond with: SPAM if it\'s spam, APPROVE if it\'s legitimate. Consider promotional content, irrelevant links, repetitive text, and suspicious patterns.',
                'category' => 'spam'
            ),
            'toxicity_detection' => array(
                'name' => 'Toxicity Detection',
                'prompt' => 'Evaluate this comment for toxic, rude, or inappropriate content. Comment: "{comment_content}" by {author_name} on post "{post_title}". Respond with: TOXIC if inappropriate, APPROVE if acceptable. Look for harassment, hate speech, personal attacks, or offensive language.',
                'category' => 'toxicity'
            ),
            'quality_assessment' => array(
                'name' => 'Quality Assessment',
                'prompt' => 'Assess the quality and relevance of this comment. Comment: "{comment_content}" by {author_name} on post "{post_title}". Respond with: APPROVE for constructive comments, SPAM for low-quality or irrelevant content. Consider whether it adds value to the discussion.',
                'category' => 'quality'
            ),
            'general_moderation' => array(
                'name' => 'General Moderation',
                'prompt' => 'Review this comment for overall appropriateness. Comment: "{comment_content}" by {author_name} ({author_email}) on post "{post_title}". Respond with: APPROVE for good comments, SPAM for promotional/irrelevant content, TOXIC for inappropriate content. Provide brief reasoning.',
                'category' => 'general'
            )
        );
    }
}

// Add prompts management page functionality to settings.php
add_action('admin_init', 'ai_comment_moderator_handle_prompt_actions');
function ai_comment_moderator_handle_prompt_actions() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle prompt creation
    if (isset($_POST['create_prompt'])) {
        check_admin_referer('ai_moderator_prompt_nonce');
        
        $result = AI_Comment_Moderator_Prompt_Manager::create_prompt($_POST);
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Prompt created successfully!</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Failed to create prompt.</p></div>';
            });
        }
    }
    
    // Handle prompt updates
    if (isset($_POST['update_prompt'])) {
        check_admin_referer('ai_moderator_prompt_nonce');
        
        $prompt_id = intval($_POST['prompt_id']);
        $result = AI_Comment_Moderator_Prompt_Manager::update_prompt($prompt_id, $_POST);
        if ($result !== false) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Prompt updated successfully!</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Failed to update prompt.</p></div>';
            });
        }
    }
    
    // Handle prompt deletion
    if (isset($_GET['delete_prompt']) && isset($_GET['_wpnonce'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_prompt_' . $_GET['delete_prompt'])) {
            $prompt_id = intval($_GET['delete_prompt']);
            $result = AI_Comment_Moderator_Prompt_Manager::delete_prompt($prompt_id);
            if ($result) {
                wp_redirect(admin_url('admin.php?page=ai-comment-moderator-prompts&deleted=1'));
                exit;
            }
        }
    }
}

// Update the prompts page function in settings.php
function ai_comment_moderator_prompts_page() {
    $action = isset($_GET['action']) ? $_GET['action'] : 'list';
    $prompt_id = isset($_GET['prompt']) ? intval($_GET['prompt']) : 0;
    
    if ($action === 'edit' && $prompt_id) {
        ai_comment_moderator_edit_prompt_page($prompt_id);
    } elseif ($action === 'new') {
        ai_comment_moderator_new_prompt_page();
    } else {
        ai_comment_moderator_list_prompts_page();
    }
}

function ai_comment_moderator_list_prompts_page() {
    $prompts = AI_Comment_Moderator_Prompt_Manager::get_prompts();
    
    if (isset($_GET['deleted'])) {
        echo '<div class="notice notice-success"><p>Prompt deleted successfully!</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1>
            Manage Prompts
            <a href="<?php echo admin_url('admin.php?page=ai-comment-moderator-prompts&action=new'); ?>" class="page-title-action">Add New</a>
        </h1>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prompts)): ?>
                <tr>
                    <td colspan="4">No prompts found. <a href="<?php echo admin_url('admin.php?page=ai-comment-moderator-prompts&action=new'); ?>">Create your first prompt</a>.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($prompts as $prompt): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($prompt->name); ?></strong>
                        <div class="row-actions">
                            <span class="edit">
                                <a href="<?php echo admin_url('admin.php?page=ai-comment-moderator-prompts&action=edit&prompt=' . $prompt->id); ?>">Edit</a> |
                            </span>
                            <span class="delete">
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ai-comment-moderator-prompts&delete_prompt=' . $prompt->id), 'delete_prompt_' . $prompt->id); ?>" 
                                   onclick="return confirm('Are you sure you want to delete this prompt?')">Delete</a>
                            </span>
                        </div>
                    </td>
                    <td><?php echo esc_html(ucfirst($prompt->category)); ?></td>
                    <td>
                        <?php if ($prompt->is_active): ?>
                            <span class="status-active">Active</span>
                        <?php else: ?>
                            <span class="status-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-config">
                            <small>
                                Approve: <?php echo esc_html($prompt->action_approve); ?><br>
                                Spam: <?php echo esc_html($prompt->action_spam); ?><br>
                                Trash: <?php echo esc_html($prompt->action_trash); ?>
                            </small>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function ai_comment_moderator_new_prompt_page() {
    $templates = AI_Comment_Moderator_Prompt_Manager::get_default_templates();
    $categories = AI_Comment_Moderator_Prompt_Manager::get_categories();
    $actions = AI_Comment_Moderator_Prompt_Manager::get_action_options();
    
    ?>
    <div class="wrap">
        <h1>Add New Prompt</h1>
        
        <form method="post" action="<?php echo admin_url('admin.php?page=ai-comment-moderator-prompts'); ?>">
            <?php wp_nonce_field('ai_moderator_prompt_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Template</th>
                    <td>
                        <select id="prompt-template" onchange="loadTemplate()">
                            <option value="">Custom Prompt</option>
                            <?php foreach ($templates as $key => $template): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($template['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Start with a template or create a custom prompt</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Name</th>
                    <td>
                        <input type="text" name="name" id="prompt-name" class="regular-text" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Category</th>
                    <td>
                        <select name="category" id="prompt-category">
                            <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Prompt Text</th>
                    <td>
                        <textarea name="prompt_text" id="prompt-text" rows="8" class="large-text" required></textarea>
                        <p class="description">
                            Available variables: {comment_content}, {author_name}, {author_email}, {post_title}, {comment_date}, {site_name}
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Actions</th>
                    <td>
                        <table class="action-config-table">
                            <tr>
                                <td><label>When AI says APPROVE:</label></td>
                                <td>
                                    <select name="action_approve">
                                        <?php foreach ($actions as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($key, 'approve'); ?>><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><label>When AI says SPAM:</label></td>
                                <td>
                                    <select name="action_spam">
                                        <?php foreach ($actions as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($key, 'spam'); ?>><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><label>When AI says TOXIC/TRASH:</label></td>
                                <td>
                                    <select name="action_trash">
                                        <?php foreach ($actions as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($key, 'trash'); ?>><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Status</th>
                    <td>
                        <label>
                            <input type="checkbox" name="is_active" value="1" checked />
                            Active (available for use)
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Create Prompt', 'primary', 'create_prompt'); ?>
        </form>
    </div>
    
    <script>
    var templates = <?php echo json_encode($templates); ?>;
    
    function loadTemplate() {
        var select = document.getElementById('prompt-template');
        var templateKey = select.value;
        
        if (templateKey && templates[templateKey]) {
            var template = templates[templateKey];
            document.getElementById('prompt-name').value = template.name;
            document.getElementById('prompt-text').value = template.prompt;
            document.getElementById('prompt-category').value = template.category;
        }
    }
    </script>
    <?php
}

function ai_comment_moderator_edit_prompt_page($prompt_id) {
    $prompt = AI_Comment_Moderator_Prompt_Manager::get_prompt($prompt_id);
    if (!$prompt) {
        echo '<div class="wrap"><h1>Prompt not found</h1><p><a href="' . admin_url('admin.php?page=ai-comment-moderator-prompts') . '">Back to prompts</a></p></div>';
        return;
    }
    
    $categories = AI_Comment_Moderator_Prompt_Manager::get_categories();
    $actions = AI_Comment_Moderator_Prompt_Manager::get_action_options();
    
    ?>
    <div class="wrap">
        <h1>Edit Prompt</h1>
        
        <form method="post" action="<?php echo admin_url('admin.php?page=ai-comment-moderator-prompts'); ?>">
            <?php wp_nonce_field('ai_moderator_prompt_nonce'); ?>
            <input type="hidden" name="prompt_id" value="<?php echo esc_attr($prompt->id); ?>" />
            
            <table class="form-table">
                <tr>
                    <th scope="row">Name</th>
                    <td>
                        <input type="text" name="name" value="<?php echo esc_attr($prompt->name); ?>" class="regular-text" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Category</th>
                    <td>
                        <select name="category">
                            <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($prompt->category, $key); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Prompt Text</th>
                    <td>
                        <textarea name="prompt_text" rows="8" class="large-text" required><?php echo esc_textarea($prompt->prompt_text); ?></textarea>
                        <p class="description">
                            Available variables: {comment_content}, {author_name}, {author_email}, {post_title}, {comment_date}, {site_name}
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Actions</th>
                    <td>
                        <table class="action-config-table">
                            <tr>
                                <td><label>When AI says APPROVE:</label></td>
                                <td>
                                    <select name="action_approve">
                                        <?php foreach ($actions as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($prompt->action_approve, $key); ?>><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><label>When AI says SPAM:</label></td>
                                <td>
                                    <select name="action_spam">
                                        <?php foreach ($actions as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($prompt->action_spam, $key); ?>><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><label>When AI says TOXIC/TRASH:</label></td>
                                <td>
                                    <select name="action_trash">
                                        <?php foreach ($actions as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($prompt->action_trash, $key); ?>><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Status</th>
                    <td>
                        <label>
                            <input type="checkbox" name="is_active" value="1" <?php checked($prompt->is_active, 1); ?> />
                            Active (available for use)
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Update Prompt', 'primary', 'update_prompt'); ?>
        </form>
    </div>
    <?php
}
