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
                        <big class="lp_statistics-row__value">XXX</big>
                        <?php _e( 'Valid', 'laterpay_migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'XXX', 'laterpay_migrator' ); ?>">
                        <big class="lp_statistics-row__value">XXX</big>
                        <?php _e( 'Invalid', 'laterpay_migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'XXX', 'laterpay_migrator' ); ?>">
                        <big class="lp_statistics-row__value">XXX</big>
                        <?php _e( 'Offered', 'laterpay_migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'XXX', 'laterpay_migrator' ); ?>">
                        <big class="lp_statistics-row__value">XXX</big>
                        <?php _e( 'Ignored', 'laterpay_migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'XXX', 'laterpay_migrator' ); ?>">
                        <big class="lp_statistics-row__value">XXX</big>
                        <?php _e( 'Migrated', 'laterpay_migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'XXX', 'laterpay_migrator' ); ?>">
                        <big class="lp_statistics-row__value">XXX</big>
                        <?php _e( 'Remaining', 'laterpay_migrator' ); ?>
                    </li>
                    <li class="lp_tooltip lp_statistics-row__item"
                        data-tooltip="<?php _e( 'XXX', 'laterpay_migrator' ); ?>">
                        <big class="lp_statistics-row__value">XXX</big>
                        <?php _e( 'Last Expiry', 'laterpay_migrator' ); ?>
                    </li>
                </ul>
            </div>

            <div class="lp_inline-block lp_mr">
                <label>
                    <input type="radio" name="laterpay_migrator_status" value="setup" checked>
                    <?php _e( 'Setup', 'laterpay_migrator' ); ?>
                </label>
                <label>
                    <input type="radio" name="laterpay_migrator_status" value="migrating">
                    <?php _e( 'Migrating', 'laterpay_migrator' ); ?>
                </label>
                <label>
                    <input type="radio" name="laterpay_migrator_status" value="complete">
                    <?php _e( 'Complete', 'laterpay_migrator' ); ?>
                </label>
            </div>

            <a href="#" class="button button-primary">
                <?php _e( 'Start Migration', 'laterpay_migrator' ); ?>
            </a>
        </div>


        <h2><?php _e( 'Subscriber Data CSV Import', 'laterpay_migrator' ); ?></h2>
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
                                <input type="file" class="lp_upload__input" name="xxx" size="10" accept=".csv">
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div>
            <div class="lp_inline-block lp_1/4">
                <h3><?php _e( 'Required Data Format', 'laterpay_migrator' ); ?></h3>
                <ul>
                    <li><?php _e( 'Text qualified by double quotes', 'laterpay_migrator' ); ?></li>
                    <li><?php _e( 'Fields delimited by commas', 'laterpay_migrator' ); ?></li>
                    <li><?php _e( 'UTF-8 encoding', 'laterpay_migrator' ); ?></li>
                    <li><?php _e( 'No first line with field names', 'laterpay_migrator' ); ?></li>
                </ul>
            </div><div class="lp_inline-block lp_1/4">
                <h3><?php _e( 'Required Data per Record (in that order)', 'laterpay_migrator' ); ?></h3>
                <ul>
                    <li><?php _e( 'Email address', 'laterpay_migrator' ); ?></li>
                    <li><?php _e( 'First Name', 'laterpay_migrator' ); ?></li>
                    <li><?php _e( 'Family Name', 'laterpay_migrator' ); ?></li>
                    <li><?php _e( 'Expiry Date of Subscription (mm-dd-yyyy)', 'laterpay_migrator' ); ?></li>
                    <li><?php _e( 'Subscribed Product', 'laterpay_migrator' ); ?></li>
                </ul>
            </div><div class="lp_inline-block lp_1/4">
                <?php _e( 'You can download a template CSV file here that you can fill with your real data.', 'laterpay_migrator' ); ?>
            </div>
        </div>
        <hr class="lp_form-group-separator">


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
                    <!-- for each unique 'Subscribed Product' from the CSV import -->
                    <tr>
                        <td>
                            <strong>NAME HERE</strong>
                        </td>
                        <td>
                            &#10142;
                        </td>
                        <td>
                            <select class="lp_input">
                                <option>TIME PASS HERE</option>
                                <option>TIME PASS HERE</option>
                                <option>TIME PASS HERE</option>
                            </select>
                        </td>
                        <td>
                            <select class="lp_input">
                                <option>ROLE HERE</option>
                                <option>ROLE HERE</option>
                                <option>ROLE HERE</option>
                            </select>
                        </td>
                        <td>
                            <select class="lp_input">
                                <option>ROLE HERE</option>
                                <option>ROLE HERE</option>
                                <option>ROLE HERE</option>
                            </select>
                        </td>
                    </tr>
                    <!-- end loop -->
                </tbody>
            </table>
        </div>
        <hr class="lp_form-group-separator">


        <div>
            <h2><?php _e( 'Subscriber Communication', 'laterpay_migrator' ); ?></h2>
        </div>

        <div class="lp_mb+">
            <h3><?php _e( 'Sitenotice', 'laterpay_migrator' ); ?></h3>
            <dfn>
                <?php _e( 'During migration, the plugin renders a sitenotice bar for subscribers asking them to switch to a free time pass for the rest of their subscription period.', 'laterpay_migrator' ); ?>
            </dfn>
            <div>
                <div class="lp_inline-block lp_1/2">
                    <div class="lp_browser">
                        <div class="lp_browser__omnibar lp_clearfix">
                            <div class="lp_browser__omnibar-dot"></div>
                            <div class="lp_browser__omnibar-dot"></div>
                            <div class="lp_browser__omnibar-dot"></div>
                        </div>
                        <div id="lp_browser__sitenotice" class="lp_browser__sitenotice">
                            <div id="lp_browser__sitenotice-text" class="lp_browser__sitenotice-text">
                                <?php _e( 'Get a free time pass for the rest of your subscription period', 'laterpay_migrator' ); ?>
                            </div>
                            <div id="lp_browser__sitenotice-button" class="lp_browser__sitenotice-button">
                                <?php _e( 'Switch Now', 'laterpay_migrator' ); ?>
                            </div>
                        </div>
                    </div>
                </div><div class="lp_inline-block lp_1/4">
                    <div>
                        <textarea class="lp_input lp_1" rows="2"><?php _e( 'Get a free time pass for the rest of your subscription period', 'laterpay_migrator' ); ?></textarea>
                    </div>
                    <div>
                        <label><?php _e( 'Button Text', 'laterpay_migrator' ); ?></label>
                        <input type="text" class="lp_input" name="xxx" value="">
                    </div>
                    <div>
                        <label><?php _e( 'Background Color', 'laterpay_migrator' ); ?></label>
                        <input type="text" class="lp_input" name="xxx" value="">
                    </div>
                    <div>
                        <label><?php _e( 'Text Color', 'laterpay_migrator' ); ?></label>
                        <input type="text" class="lp_input" name="xxx" value="">
                    </div>
                </div>
            </div>
        </div>

        <div class="lp_mb+">
            <h3><?php _e( 'Email Notifications', 'laterpay_migrator' ); ?></h3>
            <dfn>
                <?php _e( 'The plugin will send every subscriber who has not yet switched up to two emails asking them to switch to a free time pass for the rest of their subscription period.', 'laterpay_migrator' ); ?>
                <?php _e( 'To ensure smooth delivery and rendering of the emails, we send these emails with <a href="http://mailchimp.com/" class="lp_inline" target="_blank">MailChimp</a>.', 'laterpay_migrator' ); ?>
            </dfn>
            <div>
                <div class="lp_inline-block lp_1/4">
                    <label><?php _e( 'MailChimp API Key', 'laterpay_migrator' ); ?></label>
                </div><div class="lp_inline-block lp_1/4">
                    <span class="lp_iconized-input" data-icon="j"></span>
                    <input type="text" class="lp_input lp_api-credentials__input" name="xxx" value="" placeholder="<?php _e( 'See Account -> API Keys and Authorized Apps', 'laterpay_migrator' ); ?>">
                </div><div class="lp_inline-block lp_1/4">
                    <dfn>
                        <?php _e( 'If you don’t have a MailChimp account, you can subscribe to the free <a href="http://mailchimp.com/pricing/" class="lp_inline" target="_blank">MailChimp “Entrepreneur” plan</a>, which allows up to 2,000 recipients ', 'laterpay_migrator' ); ?>
                    </dfn>
                </div>
            </div>
        </div>

        <div class="lp_mb+">
            <h3><?php _e( 'Email Notification 1, sent 14 days before the subscription expires', 'laterpay_migrator' ); ?></h3>
            <div>
                <div class="lp_inline-block lp_1/4">
                    <label><?php _e( 'MailChimp Campaign Name', 'laterpay_migrator' ); ?></label>
                </div><div class="lp_inline-block lp_1/4">
                    <input type="text" class="lp_input" name="xxx" value="" placeholder="<?php _e( 'Enter MailChimp campaign name', 'laterpay_migrator' ); ?>">
                </div><div class="lp_inline-block lp_1/4">
                    <dfn><?php _e( 'You have to set up a “Campaign” at MailChimp, which defines the layout and text for this email', 'laterpay_migrator' ); ?></dfn>
                </div>
            </div>
        </div>

        <div class="lp_mb+">
            <h3><?php _e( 'Email Notification 2, sent on the day the subscription expires', 'laterpay_migrator' ); ?></h3>
            <div>
                <div class="lp_inline-block lp_1/4">
                    <label><?php _e( 'MailChimp Campaign Name', 'laterpay_migrator' ); ?></label>
                </div><div class="lp_inline-block lp_1/4">
                    <input type="text" class="lp_input" name="xxx" value="" placeholder="<?php _e( 'Enter MailChimp campaign name', 'laterpay_migrator' ); ?>">
                </div><div class="lp_inline-block lp_1/4">
                    <dfn><?php _e( 'You have to set up a “Campaign” at MailChimp, which defines the layout and text for this email', 'laterpay_migrator' ); ?></dfn>
                </div>
            </div>
        </div>

    </div>

</div>
