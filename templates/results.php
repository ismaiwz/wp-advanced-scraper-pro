<div class="wrap">
    <h1><?php _e('Scraping Results', 'wp-advanced-scraper-pro'); ?></h1>
    
    <div class="wasp-results-container">
        <!-- Filters -->
        <div class="wasp-filters">
            <div class="wasp-bulk-actions">
                <select id="wasp-bulk-action">
                    <option value=""><?php _e('Bulk Actions', 'wp-advanced-scraper-pro'); ?></option>
                    <option value="create_posts"><?php _e('Create Posts', 'wp-advanced-scraper-pro'); ?></option>
                    <option value="mark_published"><?php _e('Mark as Published', 'wp-advanced-scraper-pro'); ?></option>
                    <option value="delete"><?php _e('Delete', 'wp-advanced-scraper-pro'); ?></option>
                </select>
                <button type="button" id="wasp-apply-bulk" class="button"><?php _e('Apply', 'wp-advanced-scraper-pro'); ?></button>
            </div>
            
            <div class="wasp-filter-controls">
                <select id="wasp-filter-task">
                    <option value=""><?php _e('All Tasks', 'wp-advanced-scraper-pro'); ?></option>
                    <?php foreach ($tasks as $task): ?>
                        <option value="<?php echo esc_attr($task->id); ?>"><?php echo esc_html($task->name); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select id="wasp-filter-status">
                    <option value=""><?php _e('All Statuses', 'wp-advanced-scraper-pro'); ?></option>
                    <option value="pending"><?php _e('Pending', 'wp-advanced-scraper-pro'); ?></option>
                    <option value="published"><?php _e('Published', 'wp-advanced-scraper-pro'); ?></option>
                    <option value="failed"><?php _e('Failed', 'wp-advanced-scraper-pro'); ?></option>
                </select>
                
                <button type="button" id="wasp-apply-filters" class="button"><?php _e('Filter', 'wp-advanced-scraper-pro'); ?></button>
            </div>
        </div>
        
        <!-- Results Table -->
        <?php if (!empty($results)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="wasp-select-all">
                        </td>
                        <th><?php _e('Title', 'wp-advanced-scraper-pro'); ?></th>
                        <th><?php _e('URL', 'wp-advanced-scraper-pro'); ?></th>
                        <th><?php _e('Task', 'wp-advanced-scraper-pro'); ?></th>
                        <th><?php _e('Status', 'wp-advanced-scraper-pro'); ?></th>
                        <th><?php _e('Date', 'wp-advanced-scraper-pro'); ?></th>
                        <th><?php _e('Actions', 'wp-advanced-scraper-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" class="wasp-result-checkbox" value="<?php echo esc_attr($result->id); ?>">
                            </th>
                            <td>
                                <strong><?php echo esc_html(wp_trim_words($result->title, 8)); ?></strong>
                                <?php if ($result->excerpt): ?>
                                    <br><small class="description"><?php echo esc_html(wp_trim_words($result->excerpt, 15)); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($result->url): ?>
                                    <a href="<?php echo esc_url($result->url); ?>" target="_blank" title="<?php echo esc_attr($result->url); ?>">
                                        <?php echo esc_html(wp_trim_words($result->url, 6)); ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($result->task_name); ?></td>
                            <td>
                                <span class="wasp-status-badge wasp-status-<?php echo esc_attr($result->status); ?>">
                                    <?php echo esc_html(ucfirst($result->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($result->scraped_at); ?></td>
                            <td class="wasp-actions">
                                <?php if ($result->post_id == 0): ?>
                                    <button class="button button-small wasp-create-post" data-result-id="<?php echo esc_attr($result->id); ?>">
                                        <?php _e('Create Post', 'wp-advanced-scraper-pro'); ?>
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo get_edit_post_link($result->post_id); ?>" class="button button-small" target="_blank">
                                        <?php _e('Edit Post', 'wp-advanced-scraper-pro'); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <button class="button button-small wasp-view-content" data-result-id="<?php echo esc_attr($result->id); ?>">
                                    <?php _e('View', 'wp-advanced-scraper-pro'); ?>
                                </button>
                                
                                <button class="button button-small wasp-delete-result" data-result-id="<?php echo esc_attr($result->id); ?>">
                                    <?php _e('Delete', 'wp-advanced-scraper-pro'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No results found.', 'wp-advanced-scraper-pro'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Content Modal -->
    <div id="wasp-content-modal" class="wasp-modal" style="display: none;">
        <div class="wasp-modal-content wasp-modal-large">
            <div class="wasp-modal-header">
                <h3><?php _e('Content Details', 'wp-advanced-scraper-pro'); ?></h3>
                <span class="wasp-modal-close">&times;</span>
            </div>
            <div class="wasp-modal-body">
                <div id="wasp-content-details"></div>
            </div>
        </div>
    </div>
</div>
