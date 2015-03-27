<?php
    if ( ! defined( 'ABSPATH' ) ) {
        // prevent direct access to this file
        exit;
    }
?>

<div class="lp_page wp-core-ui">

    <div id="lp_js_flashMessage" class="lp_flash-message" style="display:none;">
        <p></p>
    </div>

    <div class="lp_navigation">
        <?php if ( ! $laterpay['plugin_is_in_live_mode'] ): ?>
            <a href="<?php echo add_query_arg( array( 'page' => $laterpay['admin_menu']['account']['url'] ), admin_url( 'admin.php' ) ); ?>"
               class="lp_plugin-mode-indicator"
               data-icon="h">
                <h2 class="lp_plugin-mode-indicator__title"><?php _e( 'Test mode', 'laterpay-migrator' ); ?></h2>
                <span class="lp_plugin-mode-indicator__text"><?php _e( 'Earn money in <i>live mode</i>', 'laterpay-migrator' ); ?></span>
            </a>
        <?php endif; ?>
        <?php echo $laterpay['top_nav']; ?>
    </div>

    <div class="lp_pagewrap">

        <div class="lp_clearfix">
            <div class="lp_statistics-row lp_right">
                <ul class="lp_statistics-row__list">
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'Number of valid subscriber records in imported CSV file', 'laterpay-migrator' ); ?>">
                        <big class="lp_statistics-row__value"><?php echo $laterpay['subscriptions_state']['valid']; ?></big>
                        <?php _e( 'Valid', 'laterpay-migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'Number of incomplete subscriber records in imported CSV file', 'laterpay-migrator' ); ?>">
                        <big class="lp_statistics-row__value"><?php echo $laterpay['subscriptions_state']['invalid']; ?></big>
                        <?php _e( 'Invalid', 'laterpay-migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'Number of subscribers who have already switched to LaterPay', 'laterpay-migrator' ); ?>">
                        <big class="lp_statistics-row__value"><?php echo $laterpay['subscriptions_state']['migrated']; ?></big>
                        <?php _e( 'Migrated', 'laterpay-migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'Number of subscribers who have not switched to LaterPay yet', 'laterpay-migrator' ); ?>">
                        <big class="lp_statistics-row__value"><?php echo $laterpay['subscriptions_state']['remaining']; ?></big>
                        <?php _e( 'Remaining', 'laterpay-migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'Last expiry in imported subscriber records', 'laterpay-migrator' ); ?>">
                        <big class="lp_statistics-row__value"><?php echo $laterpay['subscriptions_state']['expiry'] ? $laterpay['subscriptions_state']['expiry'] : __( 'n/a', 'laterpay-migrator' ); ?></big>
                        <?php _e( 'Last Expiry', 'laterpay-migrator' ); ?>
                    </li>
                </ul>
            </div>

            <div class="lp_mt+">
                <div class="lp_status-indicator <?php echo $laterpay['status_class']; ?>">
                    <span class="lp_status-indicator__label lp_status--setting-up<?php if ( ! $laterpay['migration_is_active'] && ! $laterpay['migration_is_completed'] ) { echo ' lp_is-active'; } ?>">
                        <?php _e( 'Setup', 'laterpay-migrator' ); ?>
                    </span>
                    <span class="lp_status-indicator__label lp_status--migrating<?php if ( $laterpay['migration_is_active'] ) { echo ' lp_is-active'; } ?>">
                        <?php _e( 'Migrating', 'laterpay-migrator' ); ?>
                    </span>
                    <span class="lp_status-indicator__label lp_status--completed<?php if ( $laterpay['migration_is_completed'] ) { echo ' lp_is-active'; } ?>">
                        <?php _e( 'Complete', 'laterpay-migrator' ); ?>
                    </span>
                </div>

                <?php if ( $laterpay['products'] ): ?>
                    <a href="#"
                        id="lp_js_switchPluginStatus"
                        class="button button-primary" <?php if ( ! $laterpay['products'] ) { echo 'disabled'; } ?>
                        data-setup="<?php echo __( 'Start Migration', 'laterpay-migrator' ); ?>"
                        data-migrating="<?php echo __( 'Pause Migration', 'laterpay-migrator' ); ?>"
                        <?php if ( $laterpay['migration_is_completed'] ) { echo 'style="display:none;"'; } ?>>
                        <?php if ( ! $laterpay['migration_is_active'] ) {
                                _e( 'Start Migration', 'laterpay-migrator' );
                              } elseif ( $laterpay['migration_is_active'] ) {
                                _e( 'Pause Migration', 'laterpay-migrator' );
                              }
                        ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>


        <div<?php if ( $laterpay['products'] ) { echo ' class="lp_has-imported-data"'; } ?>>
            <h2><?php _e( 'Subscriber Data CSV Import', 'laterpay-migrator' ); ?></h2>
            <form id="lp_js_uploadForm"<?php if ( $laterpay['products'] ) { echo ' class="lp_has-imported-data"'; } ?> method="post">
                <input type="hidden" name="action" value="laterpay_migrator_file_upload">
                <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay-migrator' ); } ?>
                <div class="lp_greybox">
                    <table class="lp_upload">
                        <tbody>
                            <tr>
                                <td>
                                    <div class="lp_upload__input-value-wrapper">
                                        <span class="lp_upload__input-value"></span>
                                    </div>
                                    <span class="lp_upload__input-wrapper">
                                        <span id="lp_js_uploadButton" class="lp_upload__button button button-primary"><?php _e( 'Select CSV File to Upload', 'laterpay-migrator' ); ?></span>
                                        <input type="file" id="lp_js_fileInput" class="lp_upload__input" name="file" size="10" accept=".csv">
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </form>

            <div class="lp_layout lp_empty-state">
                <div class="lp_layout__item lp_1/4">
                    <h3><?php _e( 'Required Data Format', 'laterpay-migrator' ); ?></h3>
                    <ul class="lp_list--bulleted">
                        <li class="lp_list__item"><?php _e( 'Text qualified by double quotes', 'laterpay-migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'Fields delimited by semicolons', 'laterpay-migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'File encoded in UTF-8', 'laterpay-migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'No first line with field names', 'laterpay-migrator' ); ?></li>
                    </ul>
                </div><div class="lp_layout__item lp_1/4">
                    <h3><?php _e( 'Required Data per Record (in that order!)', 'laterpay-migrator' ); ?></h3>
                    <ul class="lp_list--bulleted">
                        <li class="lp_list__item"><?php _e( 'Email address', 'laterpay-migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'First name', 'laterpay-migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'Family name', 'laterpay-migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'Expiry date of subscription (dd-mm-yyyy)', 'laterpay-migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'Subscribed product', 'laterpay-migrator' ); ?></li>
                    </ul>
                </div><div class="lp_layout__item lp_1/4">
                    <?php echo sprintf(
                            __( 'You can %s and fill in your real data.', 'laterpay-migrator' ),
                            '<a href="' . $laterpay['example_url'] . '">' .
                                __( 'download a template CSV file here', 'laterpay-migrator' ) .
                            '</a>'
                    ); ?>
                </div>
            </div>
        </div>

        <a href="#" id="lp_js_toggleFileUploadVisibility" class="lp_upload-visibility-toggle lp_inline-block lp_mt lp_mb+"<?php if ( ! $laterpay['products'] ) { echo ' style="display:none;"'; } ?>>
            <?php _e( 'Delete existing and import new subscriber data', 'laterpay-migrator' ); ?>
        </a>
        <hr class="lp_form-group-separator<?php if ( $laterpay['products'] ) { echo ' lp_has-imported-data'; } ?>">


        <?php if ( $laterpay['products'] ): ?>
            <form id="lp_js_migratorMainForm" method="post">
                <input type="hidden" name="action" value="laterpay_migrator_activate">
                <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay-migrator' ); } ?>

                <div>
                    <h2><?php _e( 'Subscription Mapping', 'laterpay-migrator' ); ?></h2>
                    <table class="lp_table">
                        <thead class="lp_table__header-row">
                        <th class="lp_table__heading">
                            <?php _e( 'Subscribed Product', 'laterpay-migrator' ); ?>
                        </th>
                        <th class="lp_table__heading">
                            &#10142;
                        </th>
                        <th class="lp_table__heading">
                            <?php _e( 'Time Pass to Offer', 'laterpay-migrator' ); ?>
                        </th>
                        <th class="lp_table__heading">
                            <?php _e( 'Role to Assign', 'laterpay-migrator' ); ?>
                        </th>
                        <th class="lp_table__heading">
                            <?php _e( 'Role to Remove', 'laterpay-migrator' ); ?>
                        </th>
                        </thead>
                        <tbody>
                        <?php foreach ( $laterpay['products'] as $product ) : ?>
                            <?php
                                $mapping = false;
                                if ( $laterpay['products_mapping'] && isset( $laterpay['products_mapping'][$product] ) ) {
                                    $mapping = $laterpay['products_mapping'][$product];
                                }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo $product; ?></strong>
                                </td>
                                <td>
                                    &#10142;
                                </td>
                                <td>
                                    <select name="timepasses[]" class="lp_input">
                                        <option value="0" <?php if ( ! $mapping ) echo 'selected'; ?>><?php echo '- ' . __( 'select a time pass', 'laterpay-migrator' ) . ' -'; ?></option>
                                        <?php if ( $laterpay['timepasses'] ) : ?>
                                            <?php foreach ( $laterpay['timepasses'] as $timepass ) : ?>
                                                <option value="<?php echo $timepass->pass_id; ?>" <?php if ( $mapping && $timepass->pass_id == $mapping['timepass'] ) echo 'selected'; ?>><?php echo $timepass->title; ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="assign_roles[]" class="lp_input">
                                        <option value="0" <?php if ( ! $mapping ) echo 'selected'; ?>><?php echo '- ' . __( 'none', 'laterpay-migrator' ) . ' -'; ?></option>
                                        <?php foreach ( $laterpay['roles'] as $role => $role_data ) : ?>
                                            <option value="<?php echo $role; ?>" <?php if ( $mapping && $role == $mapping['assign'] ) echo 'selected'; ?>><?php echo $role_data['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="remove_roles[]" class="lp_input">
                                        <option value="0" <?php if ( ! $mapping ) echo 'selected'; ?>><?php echo '- ' . __( 'none', 'laterpay-migrator' ) . ' -'; ?></option>
                                        <?php foreach ( $laterpay['roles'] as $role => $role_data ) : ?>
                                            <option value="<?php echo $role; ?>" <?php if ( $mapping && $role == $mapping['remove'] ) echo 'selected'; ?>><?php echo $role_data['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <hr class="lp_form-group-separator">


                <div>
                    <h2><?php _e( 'Subscriber Communication', 'laterpay-migrator' ); ?></h2>
                </div>

                <div class="lp_mb+">
                    <h3><?php _e( 'Sitenotice', 'laterpay-migrator' ); ?></h3>
                    <dfn>
                        <?php _e( 'During migration, the plugin renders a sitenotice bar for subscribers asking them to switch to a free time pass for the rest of their subscription period.', 'laterpay-migrator' ); ?>
                    </dfn>
                    <div class="lp_layout">
                        <div class="lp_layout__item">
                            <div class="lp_browser">
                                <div class="lp_browser__omnibar lp_clearfix">
                                    <div class="lp_browser__omnibar-dot"></div>
                                    <div class="lp_browser__omnibar-dot"></div>
                                    <div class="lp_browser__omnibar-dot"></div>
                                </div>
                                <div id="lp_js_browserSitenotice" class="lp_browser__sitenotice" style="background:<?php echo $laterpay['sitenotice_bg_color']; ?>;">
                                    <div id="lp_js_browserSitenoticeText" class="lp_browser__sitenotice-text" style="color:<?php echo $laterpay['sitenotice_text_color']; ?>;">
                                        <?php echo $laterpay['sitenotice_message']; ?>
                                    </div>
                                    <div id="lp_js_browserSitenoticeButton" class="lp_browser__sitenotice-button">
                                        <?php echo $laterpay['sitenotice_button_text']; ?>
                                    </div>
                                </div>
                            </div>
                        </div><div class="lp_layout__item lp_1/4">
                            <table class="lp_table--layout lp_mt++">
                                <tr>
                                    <td colspan="2">
                                        <textarea id="lp_js_sitenoticeTextInput"
                                            class="lp_js_sitenoticeInput lp_input lp_1"
                                            name="sitenotice_message"
                                            rows="2"><?php echo $laterpay['sitenotice_message']; ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label><?php _e( 'Button Text', 'laterpay-migrator' ); ?></label>
                                    </td>
                                    <td>
                                        <input type="text"
                                            id="lp_js_sitenoticeButtonTextInput"
                                            class="lp_js_sitenoticeInput lp_input"
                                            name="sitenotice_button_text"
                                            value="<?php echo $laterpay['sitenotice_button_text']; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label><?php _e( 'Background Color', 'laterpay-migrator' ); ?></label>
                                    </td>
                                    <td>
                                        <input type="text"
                                            id="lp_js_sitenoticeBgColorInput"
                                            class="lp_js_sitenoticeInput lp_input"
                                            name="sitenotice_bg_color"
                                            value="<?php echo $laterpay['sitenotice_bg_color']; ?>"
                                            placeholder="<?php _e( 'Enter a valid CSS color', 'laterpay-migrator' ); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label><?php _e( 'Text Color', 'laterpay-migrator' ); ?></label>
                                    </td>
                                    <td>
                                        <input type="text"
                                            id="lp_js_sitenoticeTextColorInput"
                                            class="lp_js_sitenoticeInput lp_input"
                                            name="sitenotice_text_color"
                                            value="<?php echo $laterpay['sitenotice_text_color']; ?>"
                                            placeholder="<?php _e( 'Enter a valid CSS color', 'laterpay-migrator' ); ?>">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="lp_mb+">
                    <h3><?php _e( 'Email Notifications', 'laterpay-migrator' ); ?></h3>
                    <dfn>
                        <?php _e( 'The plugin will send every subscriber who has not yet switched to LaterPay up to two emails asking them to switch to a free time pass for the rest of their subscription period.', 'laterpay-migrator' ); ?><br>
                        <?php if ( $laterpay['mailchimp_api_key'] == '' ): ?>
                            <?php _e( 'To ensure smooth delivery and rendering of the emails, we send these emails with <a href="http://mailchimp.com/" class="lp_inline" target="_blank">MailChimp</a>.', 'laterpay-migrator' ); ?><br>
                            <?php _e( 'If you don’t have a MailChimp account, you can subscribe to the free <a href="http://mailchimp.com/pricing/" class="lp_inline" target="_blank">MailChimp “Entrepreneur” plan</a>, which allows up to 2,000 recipients.', 'laterpay-migrator' ); ?><br>
                        <?php endif; ?>
                        <?php _e( 'For every email type you have to create a <strong>campaign</strong> and a <strong>list</strong> on MailChimp.', 'laterpay-migrator' ); ?>
                        <?php _e( 'The campaign defines the email layout to be used as well as the subscriber list, the campaign is sent to.', 'laterpay-migrator' ); ?><br>
                    </dfn>
                    <div class="lp_layout lp_mt-">
                        <div class="lp_layout__item lp_1/6">
                            <label><?php _e( 'MailChimp API Key', 'laterpay-migrator' ); ?></label>
                        </div><div class="lp_layout__item lp_1/4">
                            <span class="lp_iconized-input" data-icon="j"></span>
                            <input type="text"
                                    class="lp_input lp_api-credentials__input lp_1"
                                    name="mailchimp_api_key"
                                    value="<?php echo $laterpay['mailchimp_api_key']; ?>"
                                    placeholder="<?php _e( 'Account Settings &#10142; Extras &#10142; API keys', 'laterpay-migrator' ); ?>">
                        </div><div class="lp_layout__item lp_ml-">
                            <label><?php _e( 'My site uses SSL', 'laterpay-migrator' ); ?></label>
                        </div><div class="lp_layout__item">
                            <div class="lp_toggle">
                                <label class="lp_toggle__label">
                                    <input type="checkbox"
                                            class="lp_toggle__input"
                                            name="mailchimp_ssl_connection"
                                            value="1"
                                            <?php if ( $laterpay['mailchimp_ssl_connection'] ) { echo 'checked'; } ?>>
                                    <span class="lp_toggle__text" data-on="ON" data-off="OFF"></span>
                                    <span class="lp_toggle__handle"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lp_mb+">
                    <h3><?php _e( 'Email Notification 1, sent 14 days before the subscription expires', 'laterpay-migrator' ); ?></h3>
                    <div class="lp_layout">
                        <div class="lp_layout__item lp_1/6">
                            <label><?php _e( 'MailChimp Campaign Name', 'laterpay-migrator' ); ?></label>
                        </div><div class="lp_layout__item lp_1/4">
                            <input type="text"
                                    class="lp_input lp_1"
                                    name="mailchimp_campaign_before_expired"
                                    value="<?php echo $laterpay['mailchimp_campaign_before_expired']; ?>"
                                    placeholder="<?php _e( 'Enter MailChimp campaign name', 'laterpay-migrator' ); ?>">
                        </div>
                    </div>
                </div>

                <div class="lp_mb+">
                    <h3><?php _e( 'Email Notification 2, sent on the day the subscription expires', 'laterpay-migrator' ); ?></h3>
                    <div class="lp_layout">
                        <div class="lp_layout__item lp_1/6">
                            <label><?php _e( 'MailChimp Campaign Name', 'laterpay-migrator' ); ?></label>
                        </div><div class="lp_layout__item lp_1/4">
                            <input type="text"
                                    class="lp_input lp_1"
                                    name="mailchimp_campaign_after_expired"
                                    value="<?php echo $laterpay['mailchimp_campaign_after_expired']; ?>"
                                    placeholder="<?php _e( 'Enter MailChimp campaign name', 'laterpay-migrator' ); ?>">
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>

</div>
