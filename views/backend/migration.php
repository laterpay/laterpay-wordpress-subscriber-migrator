<?php
    if ( ! defined( 'ABSPATH' ) ) {
        // prevent direct access to this file
        exit;
    }
?>

<div class="lp_page wp-core-ui">

    <div id="lp_js_flashMessage" class="lp_flash-Message" style="display:none;">
        <p></p>
    </div>

    <div class="lp_navigation">
        <?php if ( ! $laterpay['plugin_is_in_live_mode'] ): ?>
            <a href="<?php echo add_query_arg( array( 'page' => $laterpay['admin_menu']['account']['url'] ), admin_url( 'admin.php' ) ); ?>"
               class="lp_plugin-mode-indicator"
               data-icon="h">
                <h2 class="lp_plugin-mode-indicator__title"><?php _e( 'Test mode', 'laterpay_migrator' ); ?></h2>
                <span class="lp_plugin-mode-indicator__text"><?php _e( 'Earn money in <i>live mode</i>', 'laterpay_migrator' ); ?></span>
            </a>
        <?php endif; ?>
        <?php echo $laterpay['top_nav']; ?>
    </div>

    <div class="lp_pagewrap">

        <div class="lp_clearfix">
            <div class="lp_statistics-row lp_right">
                <ul class="lp_statistics-row__list">
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'XXX', 'laterpay_migrator' ); ?>">
                        <big class="lp_statistics-row__value"><?php echo $laterpay['subscriptions_state']['valid']; ?></big>
                        <?php _e( 'Valid', 'laterpay_migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'XXX', 'laterpay_migrator' ); ?>">
                        <big class="lp_statistics-row__value"><?php echo $laterpay['subscriptions_state']['invalid']; ?></big>
                        <?php _e( 'Invalid', 'laterpay_migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'XXX', 'laterpay_migrator' ); ?>">
                        <big class="lp_statistics-row__value"><?php echo $laterpay['subscriptions_state']['offered']; ?></big>
                        <?php _e( 'Offered', 'laterpay_migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'XXX', 'laterpay_migrator' ); ?>">
                        <big class="lp_statistics-row__value"><?php echo $laterpay['subscriptions_state']['ignored']; ?></big>
                        <?php _e( 'Ignored', 'laterpay_migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'XXX', 'laterpay_migrator' ); ?>">
                        <big class="lp_statistics-row__value"><?php echo $laterpay['subscriptions_state']['migrated']; ?></big>
                        <?php _e( 'Migrated', 'laterpay_migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'XXX', 'laterpay_migrator' ); ?>">
                        <big class="lp_statistics-row__value"><?php echo $laterpay['subscriptions_state']['remaining']; ?></big>
                        <?php _e( 'Remaining', 'laterpay_migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'XXX', 'laterpay_migrator' ); ?>">
                        <big class="lp_statistics-row__value"><?php echo $laterpay['subscriptions_state']['expiry'] ? $laterpay['subscriptions_state']['expiry'] : __( 'n/a', 'laterpay_migrator' ); ?></big>
                        <?php _e( 'Last Expiry', 'laterpay_migrator' ); ?>
                    </li>
                </ul>
            </div>

            <div class="lp_mt+">
                <div class="lp_status-indicator">
                    <label class="lp_status-indicator__label lp_is-active">
                        <input type="radio" name="laterpay_migrator_status" value="setup" checked>
                        <?php _e( 'Setup', 'laterpay_migrator' ); ?>
                    </label>
                    <label class="lp_status-indicator__label">
                        <input type="radio" name="laterpay_migrator_status" value="migrating">
                        <?php _e( 'Migrating', 'laterpay_migrator' ); ?>
                    </label>
                    <label class="lp_status-indicator__label">
                        <input type="radio" name="laterpay_migrator_status" value="complete">
                        <?php _e( 'Complete', 'laterpay_migrator' ); ?>
                    </label>
                </div>

                <a href="#" id="lp_js_startMigration" class="button button-primary">
                    <?php _e( 'Start Migration', 'laterpay_migrator' ); ?>
                </a>
            </div>
        </div>

        <h2><?php _e( 'Subscriber Data CSV Import', 'laterpay_migrator' ); ?></h2>
        <form id="lp_js_uploadForm" method="post">
            <input type="hidden" name="action" value="laterpay_migrator_file_upload">
            <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_migrator_form' ); } ?>
            <div>
                <table class="lp_upload">
                    <tbody>
                        <tr>
                            <td>
                                <div class="lp_upload__input-value-wrapper">
                                    <span class="lp_upload__input-value"></span>
                                </div>
                                <span class="lp_upload__input-wrapper">
                                    <span class="lp_upload__button button button-primary"><?php _e( 'Select CSV File to Upload', 'laterpay_migrator' ); ?></span>
                                    <input type="file" id="lp_js_fileInput" class="lp_upload__input" name="file" size="10" accept=".csv">
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>

        <form id="lp_js_migratorMainForm" method="post">
            <input type="hidden" name="action" value="laterpay_migrator_activate">
            <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_migrator_form' ); } ?>
            <div class="lp_layout">
                <div class="lp_layout__item lp_1/4">
                    <h3><?php _e( 'Required Data Format', 'laterpay_migrator' ); ?></h3>
                    <ul class="lp_list--bulleted">
                        <li class="lp_list__item"><?php _e( 'Text qualified by double quotes', 'laterpay_migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'Fields delimited by semicolons', 'laterpay_migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'File encoded in UTF-8', 'laterpay_migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'No first line with field names', 'laterpay_migrator' ); ?></li>
                    </ul>
                </div><div class="lp_layout__item lp_1/4">
                    <h3><?php _e( 'Required Data per Record (in that order)', 'laterpay_migrator' ); ?></h3>
                    <ul class="lp_list--bulleted">
                        <li class="lp_list__item"><?php _e( 'Email address', 'laterpay_migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'First Name', 'laterpay_migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'Family Name', 'laterpay_migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'Expiry Date of Subscription (mm-dd-yyyy)', 'laterpay_migrator' ); ?></li>
                        <li class="lp_list__item"><?php _e( 'Subscribed Product', 'laterpay_migrator' ); ?></li>
                    </ul>
                </div><div class="lp_layout__item lp_1/4">
                    <?php _e( 'You can <a href="">download a template CSV file</a> here that you can fill with your real data.', 'laterpay_migrator' ); ?>
                </div>
            </div>
            <hr class="lp_form-group-separator">

            <?php if ( $laterpay['products'] ): ?>
                <div>
                    <h2><?php _e( 'Subscription Mapping', 'laterpay_migrator' ); ?></h2>
                    <table class="lp_table">
                        <thead class="lp_table__header-row">
                        <th class="lp_table__heading">
                            <?php _e( 'Subscribed Product', 'laterpay_migrator' ); ?>
                        </th>
                        <th class="lp_table__heading">
                            &#10142;
                        </th>
                        <th class="lp_table__heading">
                            <?php _e( 'Time Pass to Offer', 'laterpay_migrator' ); ?>
                        </th>
                        <th class="lp_table__heading">
                            <?php _e( 'Role to Assign', 'laterpay_migrator' ); ?>
                        </th>
                        <th class="lp_table__heading">
                            <?php _e( 'Role to Remove', 'laterpay_migrator' ); ?>
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
                                        <option value="0" <?php if ( ! $mapping ) echo 'selected'; ?>><?php echo '- ' . __( 'select a time pass', 'laterpay_migrator' ) . ' -'; ?></option>
                                        <?php if ( $laterpay['timepasses'] ) : ?>
                                            <?php foreach ( $laterpay['timepasses'] as $timepass ) : ?>
                                                <option value="<?php echo $timepass->pass_id; ?>" <?php if ( $mapping && $timepass->pass_id == $mapping['timepass'] ) echo 'selected'; ?>><?php echo $timepass->title; ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="assign_roles[]" class="lp_input">
                                        <option value="0" <?php if ( ! $mapping ) echo 'selected'; ?>><?php echo '- ' . __( 'none', 'laterpay_migrator' ) . ' -'; ?></option>
                                        <?php foreach ( $laterpay['roles'] as $role => $role_data ) : ?>
                                            <option value="<?php echo $role; ?>" <?php if ( $mapping && $role == $mapping['assign'] ) echo 'selected'; ?>><?php echo $role_data['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="remove_roles[]" class="lp_input">
                                        <option value="0" <?php if ( ! $mapping ) echo 'selected'; ?>><?php echo '- ' . __( 'none', 'laterpay_migrator' ) . ' -'; ?></option>
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
            <?php endif; ?>


            <div>
                <h2><?php _e( 'Subscriber Communication', 'laterpay_migrator' ); ?></h2>
            </div>

            <div class="lp_mb+">
                <h3><?php _e( 'Sitenotice', 'laterpay_migrator' ); ?></h3>
                <dfn>
                    <?php _e( 'During migration, the plugin renders a sitenotice bar for subscribers asking them to switch to a free time pass for the rest of their subscription period.', 'laterpay_migrator' ); ?>
                </dfn>
                <div class="lp_layout">
                    <div class="lp_layout__item">
                        <div class="lp_browser">
                            <div class="lp_browser__omnibar lp_clearfix">
                                <div class="lp_browser__omnibar-dot"></div>
                                <div class="lp_browser__omnibar-dot"></div>
                                <div class="lp_browser__omnibar-dot"></div>
                            </div>
                            <div id="lp_js_browserSitenotice" class="lp_browser__sitenotice">
                                <div id="lp_js_browserSitenoticeText" class="lp_browser__sitenotice-text">
                                    <?php _e( 'Get a free time pass for the rest of your subscription period', 'laterpay_migrator' ); ?>
                                </div>
                                <div id="lp_js_browserSitenoticeButton" class="lp_browser__sitenotice-button">
                                    <?php _e( 'Switch Now', 'laterpay_migrator' ); ?>
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
                                        rows="2"><?php echo $laterpay['sitenotice_message'] !== false ? $laterpay['sitenotice_message'] :  __( 'Get a free time pass for the rest of your subscription period', 'laterpay_migrator' ); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label><?php _e( 'Button Text', 'laterpay_migrator' ); ?></label>
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
                                    <label><?php _e( 'Background Color', 'laterpay_migrator' ); ?></label>
                                </td>
                                <td>
                                    <input type="text"
                                        id="lp_js_sitenoticeBgColorInput"
                                        class="lp_js_sitenoticeInput lp_input"
                                        name="sitenotice_bg_color"
                                        value="<?php echo $laterpay['sitenotice_bg_color']; ?>"
                                        placeholder="<?php _e( 'Enter a valid CSS color', 'laterpay_migrator' ); ?>">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label><?php _e( 'Text Color', 'laterpay_migrator' ); ?></label>
                                </td>
                                <td>
                                    <input type="text"
                                        id="lp_js_sitenoticeTextColorInput"
                                        class="lp_js_sitenoticeInput lp_input"
                                        name="sitenotice_text_color"
                                        value="<?php echo $laterpay['sitenotice_text_color']; ?>"
                                        placeholder="<?php _e( 'Enter a valid CSS color', 'laterpay_migrator' ); ?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="lp_mb+">
                <h3><?php _e( 'Email Notifications', 'laterpay_migrator' ); ?></h3>
                <dfn>
                    <?php _e( 'The plugin will send every subscriber who has not yet switched up to two emails asking them to switch to a free time pass for the rest of their subscription period.', 'laterpay_migrator' ); ?><br>
                    <?php _e( 'To ensure smooth delivery and rendering of the emails, we send these emails with <a href="http://mailchimp.com/" class="lp_inline" target="_blank">MailChimp</a>.', 'laterpay_migrator' ); ?><br>
                    <?php _e( 'If you don’t have a MailChimp account, you can subscribe to the free <a href="http://mailchimp.com/pricing/" class="lp_inline" target="_blank">MailChimp “Entrepreneur” plan</a>, which allows up to 2,000 recipients ', 'laterpay_migrator' ); ?>
                </dfn>
                <div class="lp_layout">
                    <div class="lp_layout__item lp_1/6">
                        <label><?php _e( 'MailChimp API Key', 'laterpay_migrator' ); ?></label>
                    </div><div class="lp_layout__item lp_1/4">
                        <span class="lp_iconized-input" data-icon="j"></span>
                        <input type="text"
                                class="lp_input lp_api-credentials__input lp_1"
                                name="mailchimp_api_key"
                                value="<?php echo $laterpay['mailchimp_api_key']; ?>"
                                placeholder="<?php _e( 'See Account -> API Keys and Authorized Apps', 'laterpay_migrator' ); ?>">
                    </div>
                </div>

                <div>
                    <?php _e( 'This site uses SSL', 'laterpay' ); ?>
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

            <div class="lp_mb+">
                <h3><?php _e( 'Email Notification 1, sent 14 days before the subscription expires', 'laterpay_migrator' ); ?></h3>
                <div class="lp_layout">
                    <div class="lp_layout__item lp_1/6">
                        <label><?php _e( 'MailChimp Campaign Name', 'laterpay_migrator' ); ?></label>
                    </div><div class="lp_layout__item lp_1/4">
                        <input type="text"
                                class="lp_input lp_1"
                                name="mailchimp_campaign_before_expired"
                                value="<?php echo $laterpay['mailchimp_campaign_before_expired']; ?>"
                                placeholder="<?php _e( 'Enter MailChimp campaign name', 'laterpay_migrator' ); ?>">
                    </div><div class="lp_layout__item lp_ml-">
                        <dfn><?php _e( 'You have to set up a “Campaign” at MailChimp, which defines the layout and text for this email', 'laterpay_migrator' ); ?></dfn>
                    </div>
                </div>
            </div>

            <div class="lp_mb+">
                <h3><?php _e( 'Email Notification 2, sent on the day the subscription expires', 'laterpay_migrator' ); ?></h3>
                <div class="lp_layout">
                    <div class="lp_layout__item lp_1/6">
                        <label><?php _e( 'MailChimp Campaign Name', 'laterpay_migrator' ); ?></label>
                    </div><div class="lp_layout__item lp_1/4">
                        <input type="text"
                                class="lp_input lp_1"
                                name="mailchimp_campaign_after_expired"
                                value="<?php echo $laterpay['mailchimp_campaign_after_expired']; ?>"
                                placeholder="<?php _e( 'Enter MailChimp campaign name', 'laterpay_migrator' ); ?>">
                    </div><div class="lp_layout__item lp_ml-">
                        <dfn><?php _e( 'You have to set up a “Campaign” at MailChimp, which defines the layout and text for this email', 'laterpay_migrator' ); ?></dfn>
                    </div>
                </div>
            </div>
        </form>
    </div>

</div>
