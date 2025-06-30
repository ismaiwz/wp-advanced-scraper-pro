<?php
/**
 * Plugin Name: WP Advanced Scraper Pro
 * Plugin URI: https://github.com/ismaiwz/wp-advanced-scraper-pro
 * Description: Profesyonel web scraping eklentisi - Sitemap, RSS, HTML scraping ve otomatik post oluşturma
 * Version: 3.0.0
 * Author: İsmail Wazir
 * License: GPL v2 or later
 * Text Domain: wp-advanced-scraper-pro
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Güvenlik kontrolü
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// Plugin sabitleri
define('WASP_VERSION', '3.0.0');
define('WASP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WASP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WASP_PLUGIN_FILE', __FILE__);

/**
 * Ana Plugin Sınıfı
 */
class WP_Advanced_Scraper_Pro {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Database version
     */
    private $db_version = '3.0';
    
    /**
     * Singleton pattern
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(WASP_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(WASP_PLUGIN_FILE, array($this, 'deactivate'));
        
        // WordPress hooks
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_wasp_test_url', array($this, 'ajax_test_url'));
        add_action('wp_ajax_wasp_start_scraping', array($this, 'ajax_start_scraping'));
        add_action('wp_ajax_wasp_get_progress', array($this, 'ajax_get_progress'));
        add_action('wp_ajax_wasp_create_post', array($this, 'ajax_create_post'));
        add_action('wp_ajax_wasp_bulk_action', array($this, 'ajax_bulk_action'));
        add_action('wp_ajax_wasp_delete_task', array($this, 'ajax_delete_task'));
        add_action('wp_ajax_wasp_get_result_details', array($this, 'ajax_get_result_details'));
        add_action('wp_ajax_wasp_clear_logs', array($this, 'ajax_clear_logs'));
        
        // Cron hooks
        add_action('wasp_run_scraping_task', array($this, 'run_scraping_task'));
        add_action('wasp_scheduled_tasks', array($this, 'run_scheduled_tasks'));
    }
    
    /**
     * Plugin initialization
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('wp-advanced-scraper-pro', false, dirname(plugin_basename(WASP_PLUGIN_FILE)) . '/languages');
        
        // Create database tables
        $this->create_tables();
        
        // Schedule cron if not exists
        if (!wp_next_scheduled('wasp_scheduled_tasks')) {
            wp_schedule_event(time(), 'hourly', 'wasp_scheduled_tasks');
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_tables();
        $this->set_default_options();
        
        // Clear any existing cron
        wp_clear_scheduled_hook('wasp_scheduled_tasks');
        wp_schedule_event(time(), 'hourly', 'wasp_scheduled_tasks');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('wasp_scheduled_tasks');
        wp_clear_scheduled_hook('wasp_run_scraping_task');
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'wasp_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'wasp_timeout' => 30,
            'wasp_delay' => 2,
            'wasp_max_concurrent' => 5,
            'wasp_auto_publish' => 0,
            'wasp_default_category' => 1,
            'wasp_default_author' => 1,
            'wasp_enable_images' => 1,
            'wasp_enable_logging' => 1
        );
        
        foreach ($defaults as $option => $value) {
            add_option($option, $value);
        }
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tasks table
        $table_tasks = $wpdb->prefix . 'wasp_tasks';
        $sql_tasks = "CREATE TABLE IF NOT EXISTS $table_tasks (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url text NOT NULL,
            scrape_type varchar(50) DEFAULT 'sitemap',
            selectors longtext,
            filters longtext,
            schedule_type varchar(20) DEFAULT 'manual',
            schedule_interval varchar(20) DEFAULT 'hourly',
            last_run datetime DEFAULT NULL,
            next_run datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            total_scraped int(11) DEFAULT 0,
            total_published int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY schedule_type (schedule_type),
            KEY next_run (next_run)
        ) $charset_collate;";
        
        // Results table
        $table_results = $wpdb->prefix . 'wasp_results';
        $sql_results = "CREATE TABLE IF NOT EXISTS $table_results (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            task_id bigint(20) unsigned NOT NULL,
            title text,
            content longtext,
            excerpt text,
            url text,
            image_url text,
            author varchar(255),
            publish_date datetime DEFAULT NULL,
            meta_data longtext,
            scraped_at datetime DEFAULT CURRENT_TIMESTAMP,
            post_id bigint(20) unsigned DEFAULT 0,
            status varchar(20) DEFAULT 'pending',
            hash varchar(32),
            PRIMARY KEY (id),
            KEY task_id (task_id),
            KEY status (status),
            KEY hash (hash),
            KEY post_id (post_id),
            KEY scraped_at (scraped_at)
        ) $charset_collate;";
        
        // Logs table
        $table_logs = $wpdb->prefix . 'wasp_logs';
        $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            task_id bigint(20) unsigned DEFAULT NULL,
            level varchar(20) DEFAULT 'info',
            message text,
            data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY task_id (task_id),
            KEY level (level),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_tasks);
        dbDelta($sql_results);
        dbDelta($sql_logs);
        
        update_option('wasp_db_version', $this->db_version);
    }
    
    /**
     * Add admin menu
     */
    public function admin_menu() {
        $capability = 'manage_options';
        
        // Main menu
        add_menu_page(
            __('Advanced Scraper Pro', 'wp-advanced-scraper-pro'),
            __('Scraper Pro', 'wp-advanced-scraper-pro'),
            $capability,
            'wasp-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-download',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'wasp-dashboard',
            __('Dashboard', 'wp-advanced-scraper-pro'),
            __('Dashboard', 'wp-advanced-scraper-pro'),
            $capability,
            'wasp-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'wasp-dashboard',
            __('Tasks', 'wp-advanced-scraper-pro'),
            __('Tasks', 'wp-advanced-scraper-pro'),
            $capability,
            'wasp-tasks',
            array($this, 'tasks_page')
        );
        
        add_submenu_page(
            'wasp-dashboard',
            __('Results', 'wp-advanced-scraper-pro'),
            __('Results', 'wp-advanced-scraper-pro'),
            $capability,
            'wasp-results',
            array($this, 'results_page')
        );
        
        add_submenu_page(
            'wasp-dashboard',
            __('Logs', 'wp-advanced-scraper-pro'),
            __('Logs', 'wp-advanced-scraper-pro'),
            $capability,
            'wasp-logs',
            array($this, 'logs_page')
        );
        
        add_submenu_page(
            'wasp-dashboard',
            __('Settings', 'wp-advanced-scraper-pro'),
            __('Settings', 'wp-advanced-scraper-pro'),
            $capability,
            'wasp-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        if (strpos($hook, 'wasp-') === false) {
            return;
        }
        
        wp_enqueue_script('wasp-admin', WASP_PLUGIN_URL . 'assets/admin.js', array('jquery'), WASP_VERSION, true);
        wp_enqueue_style('wasp-admin', WASP_PLUGIN_URL . 'assets/admin.css', array(), WASP_VERSION);
        
        wp_localize_script('wasp-admin', 'waspAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wasp_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'wp-advanced-scraper-pro'),
                'testing_url' => __('Testing URL...', 'wp-advanced-scraper-pro'),
                'starting_task' => __('Starting task...', 'wp-advanced-scraper-pro'),
                'creating_post' => __('Creating post...', 'wp-advanced-scraper-pro'),
                'processing' => __('Processing...', 'wp-advanced-scraper-pro'),
                'error' => __('Error', 'wp-advanced-scraper-pro'),
                'success' => __('Success', 'wp-advanced-scraper-pro')
            )
        ));
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $stats = $this->get_dashboard_stats();
        include WASP_PLUGIN_PATH . 'templates/dashboard.php';
    }
    
    /**
     * Tasks page
     */
    public function tasks_page() {
        $this->handle_task_form();
        $tasks = $this->get_tasks();
        include WASP_PLUGIN_PATH . 'templates/tasks.php';
    }
    
    /**
     * Results page
     */
    public function results_page() {
        $results = $this->get_results();
        $tasks = $this->get_tasks();
        include WASP_PLUGIN_PATH . 'templates/results.php';
    }
    
    /**
     * Logs page
     */
    public function logs_page() {
        $logs = $this->get_logs();
        include WASP_PLUGIN_PATH . 'templates/logs.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $this->handle_settings_form();
        include WASP_PLUGIN_PATH . 'templates/settings.php';
    }
    
    /**
     * Handle task form submission
     */
    private function handle_task_form() {
        if (!isset($_POST['wasp_task_submit']) || !wp_verify_nonce($_POST['wasp_nonce'], 'wasp_task_form')) {
            return;
        }
        
        $task_data = array(
            'name' => sanitize_text_field($_POST['task_name']),
            'url' => esc_url_raw($_POST['task_url']),
            'scrape_type' => sanitize_text_field($_POST['scrape_type']),
            'selectors' => sanitize_textarea_field($_POST['selectors']),
            'filters' => sanitize_textarea_field($_POST['filters']),
            'schedule_type' => sanitize_text_field($_POST['schedule_type']),
            'schedule_interval' => sanitize_text_field($_POST['schedule_interval']),
            'status' => 'active'
        );
        
        if ($task_data['schedule_type'] === 'scheduled') {
            $task_data['next_run'] = $this->calculate_next_run($task_data['schedule_interval']);
        }
        
        global $wpdb;
        $result = $wpdb->insert($wpdb->prefix . 'wasp_tasks', $task_data);
        
        if ($result) {
            $this->log('success', 'Task created: ' . $task_data['name'], $wpdb->insert_id);
            $this->add_admin_notice('Task created successfully!', 'success');
        } else {
            $this->add_admin_notice('Failed to create task.', 'error');
        }
    }
    
    /**
     * Handle settings form submission
     */
    private function handle_settings_form() {
        if (!isset($_POST['wasp_settings_submit']) || !wp_verify_nonce($_POST['wasp_nonce'], 'wasp_settings_form')) {
            return;
        }
        
        $settings = array(
            'wasp_user_agent' => sanitize_text_field($_POST['wasp_user_agent']),
            'wasp_timeout' => intval($_POST['wasp_timeout']),
            'wasp_delay' => intval($_POST['wasp_delay']),
            'wasp_max_concurrent' => intval($_POST['wasp_max_concurrent']),
            'wasp_auto_publish' => isset($_POST['wasp_auto_publish']) ? 1 : 0,
            'wasp_default_category' => intval($_POST['wasp_default_category']),
            'wasp_default_author' => intval($_POST['wasp_default_author']),
            'wasp_enable_images' => isset($_POST['wasp_enable_images']) ? 1 : 0,
            'wasp_enable_logging' => isset($_POST['wasp_enable_logging']) ? 1 : 0
        );
        
        foreach ($settings as $option => $value) {
            update_option($option, $value);
        }
        
        $this->add_admin_notice('Settings saved successfully!', 'success');
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total tasks
        $stats['total_tasks'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wasp_tasks");
        
        // Active tasks
        $stats['active_tasks'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wasp_tasks WHERE status = 'active'");
        
        // Total results
        $stats['total_results'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wasp_results");
        
        // Published posts
        $stats['published_posts'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wasp_results WHERE post_id > 0");
        
        // Recent activity
        $stats['recent_logs'] = $wpdb->get_results("
            SELECT l.*, t.name as task_name 
            FROM {$wpdb->prefix}wasp_logs l 
            LEFT JOIN {$wpdb->prefix}wasp_tasks t ON l.task_id = t.id 
            ORDER BY l.created_at DESC 
            LIMIT 10
        ");
        
        return $stats;
    }
    
    /**
     * Get tasks
     */
    private function get_tasks($limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}wasp_tasks 
            ORDER BY created_at DESC 
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Get results
     */
    private function get_results($filters = array(), $limit = 50) {
        global $wpdb;
        
        $where_conditions = array('1=1');
        $where_values = array();
        
        if (!empty($filters['task_id'])) {
            $where_conditions[] = 'r.task_id = %d';
            $where_values[] = intval($filters['task_id']);
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = 'r.status = %s';
            $where_values[] = sanitize_text_field($filters['status']);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $where_values[] = $limit;
        
        $query = "
            SELECT r.*, t.name as task_name 
            FROM {$wpdb->prefix}wasp_results r 
            LEFT JOIN {$wpdb->prefix}wasp_tasks t ON r.task_id = t.id 
            WHERE $where_clause
            ORDER BY r.scraped_at DESC 
            LIMIT %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $where_values));
    }
    
    /**
     * Get logs
     */
    private function get_logs($filters = array(), $limit = 100) {
        global $wpdb;
        
        $where_conditions = array('1=1');
        $where_values = array();
        
        if (!empty($filters['level'])) {
            $where_conditions[] = 'l.level = %s';
            $where_values[] = sanitize_text_field($filters['level']);
        }
        
        if (!empty($filters['task_id'])) {
            $where_conditions[] = 'l.task_id = %d';
            $where_values[] = intval($filters['task_id']);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $where_values[] = $limit;
        
        $query = "
            SELECT l.*, t.name as task_name 
            FROM {$wpdb->prefix}wasp_logs l 
            LEFT JOIN {$wpdb->prefix}wasp_tasks t ON l.task_id = t.id 
            WHERE $where_clause
            ORDER BY l.created_at DESC 
            LIMIT %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $where_values));
    }
    
    /**
     * AJAX: Test URL
     */
    public function ajax_test_url() {
        check_ajax_referer('wasp_nonce', 'nonce');
        
        $url = esc_url_raw($_POST['url']);
        $scrape_type = sanitize_text_field($_POST['scrape_type']);
        
        if (empty($url)) {
            wp_send_json_error('URL is required');
        }
        
        $response = $this->make_request($url);
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to fetch URL: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        
        $analysis = $this->analyze_content($body, $scrape_type);
        
        wp_send_json_success(array(
            'status_code' => $status_code,
            'content_length' => strlen($body),
            'content_type' => $content_type,
            'analysis' => $analysis
        ));
    }
    
    /**
     * AJAX: Start scraping
     */
    public function ajax_start_scraping() {
        check_ajax_referer('wasp_nonce', 'nonce');
        
        $task_id = intval($_POST['task_id']);
        
        if (empty($task_id)) {
            wp_send_json_error('Task ID is required');
        }
        
        // Schedule the scraping task
        wp_schedule_single_event(time(), 'wasp_run_scraping_task', array($task_id));
        
        $this->log('info', 'Scraping task started manually', $task_id);
        
        wp_send_json_success('Scraping started successfully');
    }
    
    /**
     * AJAX: Get progress
     */
    public function ajax_get_progress() {
        check_ajax_referer('wasp_nonce', 'nonce');
        
        $task_id = intval($_POST['task_id']);
        $progress = get_transient('wasp_progress_' . $task_id);
        
        if ($progress === false) {
            wp_send_json_error('No progress data found');
        }
        
        wp_send_json_success($progress);
    }
    
    /**
     * AJAX: Create post
     */
    public function ajax_create_post() {
        check_ajax_referer('wasp_nonce', 'nonce');
        
        $result_id = intval($_POST['result_id']);
        
        if (empty($result_id)) {
            wp_send_json_error('Result ID is required');
        }
        
        $post_id = $this->create_post_from_result($result_id);
        
        if ($post_id) {
            wp_send_json_success(array(
                'post_id' => $post_id,
                'edit_url' => get_edit_post_link($post_id),
                'view_url' => get_permalink($post_id)
            ));
        } else {
            wp_send_json_error('Failed to create post');
        }
    }
    
    /**
     * AJAX: Bulk action
     */
    public function ajax_bulk_action() {
        check_ajax_referer('wasp_nonce', 'nonce');
        
        $action = sanitize_text_field($_POST['action_type']);
        $result_ids = array_map('intval', $_POST['result_ids']);
        
        if (empty($result_ids)) {
            wp_send_json_error('No items selected');
        }
        
        $processed = 0;
        
        foreach ($result_ids as $result_id) {
            switch ($action) {
                case 'create_posts':
                    if ($this->create_post_from_result($result_id)) {
                        $processed++;
                    }
                    break;
                case 'delete':
                    if ($this->delete_result($result_id)) {
                        $processed++;
                    }
                    break;
                case 'mark_published':
                    if ($this->update_result_status($result_id, 'published')) {
                        $processed++;
                    }
                    break;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%d items processed', $processed)
        ));
    }
    
    /**
     * AJAX: Delete task
     */
    public function ajax_delete_task() {
        check_ajax_referer('wasp_nonce', 'nonce');
        
        $task_id = intval($_POST['task_id']);
        
        if (empty($task_id)) {
            wp_send_json_error('Task ID is required');
        }
        
        global $wpdb;
        
        // Delete related data
        $wpdb->delete($wpdb->prefix . 'wasp_results', array('task_id' => $task_id));
        $wpdb->delete($wpdb->prefix . 'wasp_logs', array('task_id' => $task_id));
        $result = $wpdb->delete($wpdb->prefix . 'wasp_tasks', array('id' => $task_id));
        
        if ($result) {
            wp_send_json_success('Task deleted successfully');
        } else {
            wp_send_json_error('Failed to delete task');
        }
    }
    
    /**
     * AJAX: Get result details
     */
    public function ajax_get_result_details() {
        check_ajax_referer('wasp_nonce', 'nonce');
        
        $result_id = intval($_POST['result_id']);
        
        if (empty($result_id)) {
            wp_send_json_error('Result ID is required');
        }
        
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wasp_results WHERE id = %d",
            $result_id
        ));
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Result not found');
        }
    }
    
    /**
     * AJAX: Clear logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer('wasp_nonce', 'nonce');
        
        global $wpdb;
        $result = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}wasp_logs");
        
        if ($result !== false) {
            wp_send_json_success('Logs cleared successfully');
        } else {
            wp_send_json_error('Failed to clear logs');
        }
    }
    
    /**
     * Run scraping task
     */
    public function run_scraping_task($task_id) {
        global $wpdb;
        
        $task = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wasp_tasks WHERE id = %d AND status = 'active'",
            $task_id
        ));
        
        if (!$task) {
            return false;
        }
        
        $this->log('info', 'Starting scraping task: ' . $task->name, $task_id);
        
        // Set progress
        $this->set_progress($task_id, 0, 'Starting scraping...');
        
        try {
            $scraper = new WASP_Scraper($task, $this);
            $results = $scraper->scrape();
            
            if ($results !== false) {
                $count = is_array($results) ? count($results) : 0;
                $this->log('success', sprintf('Scraping completed: %d items found', $count), $task_id);
                
                // Update task statistics
                $wpdb->update(
                    $wpdb->prefix . 'wasp_tasks',
                    array(
                        'last_run' => current_time('mysql'),
                        'total_scraped' => $task->total_scraped + $count
                    ),
                    array('id' => $task_id)
                );
            } else {
                $this->log('error', 'Scraping failed', $task_id);
            }
            
        } catch (Exception $e) {
            $this->log('error', 'Scraping error: ' . $e->getMessage(), $task_id);
        }
        
        // Clear progress
        delete_transient('wasp_progress_' . $task_id);
    }
    
    /**
     * Run scheduled tasks
     */
    public function run_scheduled_tasks() {
        global $wpdb;
        
        $tasks = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}wasp_tasks 
            WHERE schedule_type = 'scheduled' 
            AND status = 'active' 
            AND (next_run <= NOW() OR next_run IS NULL)
        ");
        
        foreach ($tasks as $task) {
            // Schedule the task
            wp_schedule_single_event(time(), 'wasp_run_scraping_task', array($task->id));
            
            // Update next run time
            $next_run = $this->calculate_next_run($task->schedule_interval);
            $wpdb->update(
                $wpdb->prefix . 'wasp_tasks',
                array('next_run' => $next_run),
                array('id' => $task->id)
            );
        }
    }
    
    /**
     * Make HTTP request
     */
    public function make_request($url) {
        $args = array(
            'timeout' => get_option('wasp_timeout', 30),
            'user-agent' => get_option('wasp_user_agent'),
            'sslverify' => false,
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ),
            'redirection' => 5
        );
        
        // Try HTTPS first, then HTTP
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response) && strpos($url, 'https://') === 0) {
            $http_url = str_replace('https://', 'http://', $url);
            $response = wp_remote_get($http_url, $args);
        }
        
        return $response;
    }
    
    /**
     * Create post from result
     */
    public function create_post_from_result($result_id) {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wasp_results WHERE id = %d",
            $result_id
        ));
        
        if (!$result || $result->post_id > 0) {
            return false;
        }
        
        $post_data = array(
            'post_title' => $result->title,
            'post_content' => $result->content,
            'post_excerpt' => $result->excerpt,
            'post_status' => get_option('wasp_auto_publish') ? 'publish' : 'draft',
            'post_type' => 'post',
            'post_author' => get_option('wasp_default_author', 1),
            'post_category' => array(get_option('wasp_default_category', 1)),
            'meta_input' => array(
                'wasp_scraped_url' => $result->url,
                'wasp_scraped_at' => $result->scraped_at,
                'wasp_task_id' => $result->task_id,
                'wasp_result_id' => $result->id
            )
        );
        
        if (!empty($result->author)) {
            $post_data['meta_input']['wasp_original_author'] = $result->author;
        }
        
        if (!empty($result->publish_date)) {
            $post_data['post_date'] = $result->publish_date;
        }
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Update result
            $wpdb->update(
                $wpdb->prefix . 'wasp_results',
                array('post_id' => $post_id, 'status' => 'published'),
                array('id' => $result_id)
            );
            
            // Update task stats
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}wasp_tasks SET total_published = total_published + 1 WHERE id = %d",
                $result->task_id
            ));
            
            // Set featured image
            if (!empty($result->image_url) && get_option('wasp_enable_images')) {
                $this->set_featured_image($post_id, $result->image_url);
            }
            
            $this->log('success', 'Post created: ' . $result->title . ' (ID: ' . $post_id . ')', $result->task_id);
            
            return $post_id;
        }
        
        return false;
    }
    
    /**
     * Set featured image from URL
     */
    private function set_featured_image($post_id, $image_url) {
        if (empty($image_url) || !filter_var($image_url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        $response = wp_remote_get($image_url, array('timeout' => 30));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        
        $extension = '';
        if (strpos($content_type, 'jpeg') !== false) {
            $extension = '.jpg';
        } elseif (strpos($content_type, 'png') !== false) {
            $extension = '.png';
        } elseif (strpos($content_type, 'gif') !== false) {
            $extension = '.gif';
        } elseif (strpos($content_type, 'webp') !== false) {
            $extension = '.webp';
        }
        
        if (empty($extension)) {
            return false;
        }
        
        $filename = 'wasp-image-' . time() . $extension;
        $upload = wp_upload_bits($filename, null, $image_data);
        
        if (!$upload['error']) {
            $attachment = array(
                'post_mime_type' => $content_type,
                'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $attach_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
            
            if ($attach_id) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                wp_update_attachment_metadata($attach_id, $attach_data);
                set_post_thumbnail($post_id, $attach_id);
                
                return $attach_id;
            }
        }
        
        return false;
    }
    
    /**
     * Delete result
     */
    private function delete_result($result_id) {
        global $wpdb;
        
        return $wpdb->delete($wpdb->prefix . 'wasp_results', array('id' => $result_id));
    }
    
    /**
     * Update result status
     */
    private function update_result_status($result_id, $status) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'wasp_results',
            array('status' => $status),
            array('id' => $result_id)
        );
    }
    
    /**
     * Analyze content
     */
    private function analyze_content($content, $scrape_type) {
        $analysis = array();
        
        switch ($scrape_type) {
            case 'sitemap':
                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($content);
                if ($xml !== false) {
                    if (isset($xml->url)) {
                        $analysis['url_count'] = count($xml->url);
                        $analysis['type'] = 'urlset';
                    } elseif (isset($xml->sitemap)) {
                        $analysis['sitemap_count'] = count($xml->sitemap);
                        $analysis['type'] = 'sitemapindex';
                    }
                }
                break;
                
            case 'rss':
            case 'xml':
                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($content);
                if ($xml !== false) {
                    if (isset($xml->channel->item)) {
                        $analysis['item_count'] = count($xml->channel->item);
                        $analysis['type'] = 'rss';
                    } elseif (isset($xml->entry)) {
                        $analysis['item_count'] = count($xml->entry);
                        $analysis['type'] = 'atom';
                    }
                }
                break;
                
            case 'html':
                $dom = new DOMDocument();
                libxml_use_internal_errors(true);
                @$dom->loadHTML($content);
                $analysis['title_count'] = $dom->getElementsByTagName('title')->length;
                $analysis['h1_count'] = $dom->getElementsByTagName('h1')->length;
                $analysis['p_count'] = $dom->getElementsByTagName('p')->length;
                $analysis['img_count'] = $dom->getElementsByTagName('img')->length;
                break;
        }
        
        return $analysis;
    }
    
    /**
     * Calculate next run time
     */
    private function calculate_next_run($interval) {
        switch ($interval) {
            case 'hourly':
                return date('Y-m-d H:i:s', strtotime('+1 hour'));
            case 'twicedaily':
                return date('Y-m-d H:i:s', strtotime('+12 hours'));
            case 'daily':
                return date('Y-m-d H:i:s', strtotime('+1 day'));
            case 'weekly':
                return date('Y-m-d H:i:s', strtotime('+1 week'));
            default:
                return date('Y-m-d H:i:s', strtotime('+1 hour'));
        }
    }
    
    /**
     * Set progress
     */
    public function set_progress($task_id, $progress, $message, $details = '') {
        set_transient('wasp_progress_' . $task_id, array(
            'progress' => $progress,
            'message' => $message,
            'details' => $details,
            'timestamp' => time()
        ), 3600);
    }
    
    /**
     * Log message
     */
    public function log($level, $message, $task_id = null, $data = null) {
        if (!get_option('wasp_enable_logging', 1)) {
            return;
        }
        
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'wasp_logs',
            array(
                'task_id' => $task_id,
                'level' => $level,
                'message' => $message,
                'data' => $data ? json_encode($data) : null,
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Add admin notice
     */
    private function add_admin_notice($message, $type = 'info') {
        add_action('admin_notices', function() use ($message, $type) {
            echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
        });
    }
}

/**
 * Scraper class
 */
class WASP_Scraper {
    
    private $task;
    private $plugin;
    private $selectors;
    private $filters;
    
    public function __construct($task, $plugin) {
        $this->task = $task;
        $this->plugin = $plugin;
        $this->selectors = $this->parse_selectors($task->selectors);
        $this->filters = $this->parse_filters($task->filters);
    }
    
    /**
     * Main scraping method
     */
    public function scrape() {
        switch ($this->task->scrape_type) {
            case 'sitemap':
                return $this->scrape_sitemap();
            case 'rss':
            case 'xml':
                return $this->scrape_feed();
            case 'html':
                return $this->scrape_html();
            default:
                return false;
        }
    }
    
    /**
     * Scrape sitemap
     */
    private function scrape_sitemap() {
        $this->plugin->set_progress($this->task->id, 10, 'Fetching sitemap...');
        
        $urls = $this->get_sitemap_urls($this->task->url);
        
        if (empty($urls)) {
            $this->plugin->log('error', 'No URLs found in sitemap', $this->task->id);
            return false;
        }
        
        $this->plugin->log('info', sprintf('Found %d URLs in sitemap', count($urls)), $this->task->id);
        
        $results = array();
        $total = count($urls);
        $processed = 0;
        
        foreach ($urls as $url) {
            $processed++;
            $progress = 10 + (($processed / $total) * 80);
            
            $this->plugin->set_progress(
                $this->task->id, 
                $progress, 
                sprintf('Scraping page %d of %d', $processed, $total),
                'Current URL: ' . $url
            );
            
            $page_data = $this->scrape_page($url);
            
            if ($page_data && $this->apply_filters($page_data)) {
                $results[] = $page_data;
                $this->plugin->log('info', 'Page scraped: ' . $page_data['title'], $this->task->id);
            }
            
            // Rate limiting
            $delay = get_option('wasp_delay', 2);
            if ($delay > 0) {
                sleep($delay);
            }
        }
        
        $this->plugin->set_progress($this->task->id, 90, 'Saving results...');
        
        $saved = $this->save_results($results);
        
        $this->plugin->set_progress($this->task->id, 100, 'Completed!');
        
        return $results;
    }
    
    /**
     * Scrape RSS/XML feed
     */
    private function scrape_feed() {
        $this->plugin->set_progress($this->task->id, 20, 'Fetching feed...');
        
        $response = $this->plugin->make_request($this->task->url);
        
        if (is_wp_error($response)) {
            $this->plugin->log('error', 'Failed to fetch feed: ' . $response->get_error_message(), $this->task->id);
            return false;
        }
        
        $xml_content = wp_remote_retrieve_body($response);
        
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_content);
        
        if ($xml === false) {
            $this->plugin->log('error', 'Failed to parse XML feed', $this->task->id);
            return false;
        }
        
        $results = array();
        
        // RSS feed
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $data = array(
                    'title' => (string)$item->title,
                    'content' => (string)$item->description,
                    'url' => (string)$item->link,
                    'date' => (string)$item->pubDate,
                    'author' => (string)$item->author
                );
                
                if ($this->apply_filters($data)) {
                    $results[] = $data;
                }
            }
        }
        // Atom feed
        elseif (isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                $data = array(
                    'title' => (string)$entry->title,
                    'content' => (string)$entry->content,
                    'url' => (string)$entry->link['href'],
                    'date' => (string)$entry->published,
                    'author' => (string)$entry->author->name
                );
                
                if ($this->apply_filters($data)) {
                    $results[] = $data;
                }
            }
        }
        
        $this->save_results($results);
        
        return $results;
    }
    
    /**
     * Scrape HTML page
     */
    private function scrape_html() {
        $this->plugin->set_progress($this->task->id, 20, 'Fetching HTML page...');
        
        $page_data = $this->scrape_page($this->task->url);
        
        if ($page_data && $this->apply_filters($page_data)) {
            $this->save_results(array($page_data));
            return array($page_data);
        }
        
        return false;
    }
    
    /**
     * Get URLs from sitemap
     */
    private function get_sitemap_urls($sitemap_url) {
        $urls = array();
        
        $response = $this->plugin->make_request($sitemap_url);
        
        if (is_wp_error($response)) {
            return $urls;
        }
        
        $xml_content = wp_remote_retrieve_body($response);
        
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_content);
        
        if ($xml === false) {
            return $urls;
        }
        
        // Sitemap index
        if (isset($xml->sitemap)) {
            foreach ($xml->sitemap as $sitemap) {
                $sub_urls = $this->get_sitemap_urls((string)$sitemap->loc);
                $urls = array_merge($urls, $sub_urls);
            }
        }
        // URL set
        elseif (isset($xml->url)) {
            foreach ($xml->url as $url_entry) {
                $urls[] = (string)$url_entry->loc;
            }
        }
        
        return $urls;
    }
    
    /**
     * Scrape single page
     */
    private function scrape_page($url) {
        $response = $this->plugin->make_request($url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $html = wp_remote_retrieve_body($response);
        
        if (empty($html)) {
            return false;
        }
        
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        $data = array('url' => $url);
        
        foreach ($this->selectors as $field => $selector) {
            $data[$field] = $this->extract_content($xpath, $selector, $field);
        }
        
        // Fallback extraction
        if (empty($data['title'])) {
            $data['title'] = $this->extract_title($xpath);
        }
        
        if (empty($data['content'])) {
            $data['content'] = $this->extract_content_fallback($xpath);
        }
        
        return $data;
    }
    
    /**
     * Extract content using selector
     */
    private function extract_content($xpath, $selector, $field) {
        $selectors = explode(',', $selector);
        
        foreach ($selectors as $sel) {
            $sel = trim($sel);
            
            try {
                $elements = $xpath->query('//' . $sel);
                
                if ($elements && $elements->length > 0) {
                    $element = $elements->item(0);
                    
                    if ($field === 'image' && $element->tagName === 'img') {
                        return $element->getAttribute('src');
                    } elseif ($field === 'date' && $element->hasAttribute('datetime')) {
                        return $element->getAttribute('datetime');
                    } else {
                        $content = trim($element->textContent);
                        if (!empty($content)) {
                            return $content;
                        }
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        return '';
    }
    
    /**
     * Extract title with fallback
     */
    private function extract_title($xpath) {
        $selectors = array('title', 'h1', 'h2', '*[@class*="title"]', '*[@id*="title"]');
        
        foreach ($selectors as $selector) {
            try {
                $elements = $xpath->query('//' . $selector);
                if ($elements && $elements->length > 0) {
                    $title = trim($elements->item(0)->textContent);
                    if (!empty($title) && strlen($title) > 5) {
                        return $title;
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        return 'Untitled';
    }
    
    /**
     * Extract content with fallback
     */
    private function extract_content_fallback($xpath) {
        $selectors = array(
            'article',
            'main',
            '*[@class*="content"]',
            '*[@class*="post"]',
            '*[@class*="entry"]',
            'div[contains(@class, "text")]'
        );
        
        foreach ($selectors as $selector) {
            try {
                $elements = $xpath->query('//' . $selector);
                if ($elements && $elements->length > 0) {
                    $content = '';
                    foreach ($elements as $element) {
                        $text = trim($element->textContent);
                        if (strlen($text) > 100) {
                            $content .= $text . ' ';
                        }
                    }
                    
                    $content = trim($content);
                    if (strlen($content) > 200) {
                        return $content;
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        // Last resort: get all paragraphs
        try {
            $paragraphs = $xpath->query('//p');
            $content = '';
            foreach ($paragraphs as $p) {
                $content .= trim($p->textContent) . "\n\n";
            }
            return trim($content);
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Parse selectors
     */
    private function parse_selectors($selectors_text) {
        $selectors = array();
        $lines = explode("\n", trim($selectors_text));
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, ':') === false) {
                continue;
            }
            
            list($key, $value) = explode(':', $line, 2);
            $selectors[trim($key)] = trim($value);
        }
        
        return $selectors;
    }
    
    /**
     * Parse filters
     */
    private function parse_filters($filters_text) {
        $filters = array();
        $lines = explode("\n", trim($filters_text));
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, ':') === false) {
                continue;
            }
            
            list($key, $value) = explode(':', $line, 2);
            $filters[trim($key)] = trim($value);
        }
        
        return $filters;
    }
    
    /**
     * Apply filters
     */
    private function apply_filters($data) {
        if (empty($this->filters)) {
            return true;
        }
        
        // Minimum words
        if (isset($this->filters['min_words'])) {
            $word_count = str_word_count(strip_tags($data['content'] ?? ''));
            if ($word_count < intval($this->filters['min_words'])) {
                return false;
            }
        }
        
        // Maximum words
        if (isset($this->filters['max_words'])) {
            $word_count = str_word_count(strip_tags($data['content'] ?? ''));
            if ($word_count > intval($this->filters['max_words'])) {
                return false;
            }
        }
        
        // Minimum title length
        if (isset($this->filters['min_title_length'])) {
            if (strlen($data['title'] ?? '') < intval($this->filters['min_title_length'])) {
                return false;
            }
        }
        
        // Exclude words
        if (isset($this->filters['exclude_words'])) {
            $exclude_words = explode(',', $this->filters['exclude_words']);
            $content = strtolower($data['content'] ?? $data['title'] ?? '');
            
            foreach ($exclude_words as $word) {
                if (strpos($content, strtolower(trim($word))) !== false) {
                    return false;
                }
            }
        }
        
        // Required words
        if (isset($this->filters['required_words'])) {
            $required_words = explode(',', $this->filters['required_words']);
            $content = strtolower($data['content'] ?? $data['title'] ?? '');
            
            foreach ($required_words as $word) {
                if (strpos($content, strtolower(trim($word))) === false) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Save results
     */
    private function save_results($results) {
        global $wpdb;
        
        $saved = 0;
        
        foreach ($results as $result) {
            $hash = md5(($result['title'] ?? '') . ($result['content'] ?? ''));
            
            // Check for duplicates
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}wasp_results WHERE hash = %s",
                $hash
            ));
            
            if ($existing) {
                continue;
            }
            
            $data = array(
                'task_id' => $this->task->id,
                'title' => $result['title'] ?? '',
                'content' => $result['content'] ?? '',
                'excerpt' => wp_trim_words($result['content'] ?? '', 30),
                'url' => $result['url'] ?? '',
                'image_url' => $result['image'] ?? '',
                'author' => $result['author'] ?? '',
                'publish_date' => $this->parse_date($result['date'] ?? ''),
                'meta_data' => json_encode($result),
                'scraped_at' => current_time('mysql'),
                'status' => 'pending',
                'hash' => $hash
            );
            
            $insert_result = $wpdb->insert($wpdb->prefix . 'wasp_results', $data);
            
            if ($insert_result) {
                $saved++;
                
                // Auto-publish if enabled
                if (get_option('wasp_auto_publish')) {
                    $this->plugin->create_post_from_result($wpdb->insert_id);
                }
            }
        }
        
        $this->plugin->log('info', sprintf('%d results saved', $saved), $this->task->id);
        
        return $saved;
    }
    
    /**
     * Parse date
     */
    private function parse_date($date_string) {
        if (empty($date_string)) {
            return null;
        }
        
        $timestamp = strtotime($date_string);
        if ($timestamp === false) {
            return null;
        }
        
        return date('Y-m-d H:i:s', $timestamp);
    }
}

// Initialize plugin
WP_Advanced_Scraper_Pro::get_instance();
