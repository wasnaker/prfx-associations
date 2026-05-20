<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?= form_hidden('_attachment_sale_id', $association->userid); ?>
<?= form_hidden('_attachment_sale_type', 'association'); ?>
<div class="col-md-12 no-padding">
    <div class="panel_s">
        <div class="panel-body">
            <div class="horizontal-scrollable-tabs preview-tabs-top panel-full-width-tabs">
                <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
                <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
                <div class="horizontal-tabs">
                    <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#tab_association" aria-controls="tab_association" role="tab" data-toggle="tab">
                                <?= _l('association'); ?>
                            </a>
                        </li>
                        <li role="presentation" data-toggle="tooltip"
                            title="<?= _l('association_view_activity_tooltip'); ?>">
                            <a href="#tab_activity" aria-controls="tab_activity" role="tab" data-toggle="tab">
                                <?php if (! is_mobile()) { ?>
                                <i class="fa fa-history" aria-hidden="true"></i>
                                <?php } else { ?>
                                <?= _l('association_view_activity_tooltip'); ?>
                                <?php } ?>
                            </a>
                        </li>
                        <li role="presentation" data-toggle="tooltip"
                            title="<?= _l('association_reminders'); ?>">
                            <a href="#tab_reminders"
                                onclick="initDataTable('.table-reminders', admin_url + 'misc/get_reminders/' + <?= $association->userid; ?> + '/' + 'association', undefined, undefined, undefined,[1,'asc']); return false;"
                                aria-controls="tab_reminders" role="tab" data-toggle="tab">
                                <?php if (! is_mobile()) { ?>
                                <i class="fa-regular fa-bell" aria-hidden="true"></i>
                                <?php } else { ?>
                                <?= _l('association_reminders'); ?>
                                <?php } ?>
                                <?php
                        $total_reminders = total_rows(
                            db_prefix() . 'reminders',
                            [
                                'isnotified' => 0,
                                'staff'      => get_staff_user_id(),
                                'rel_type'   => 'association',
                                'rel_id'     => $association->userid,
                            ]
                        );
if ($total_reminders > 0) {
    echo '<span class="badge">' . $total_reminders . '</span>';
}
?>
                            </a>
                        </li>
                        <li role="presentation" data-toggle="tooltip"
                            title="<?= _l('association_notes'); ?>"
                            class="tab-separator">
                            <a href="#tab_notes"
                                onclick="get_sales_notes(<?= e($association->userid); ?>,'associations'); return false"
                                aria-controls="tab_notes" role="tab" data-toggle="tab">
                                <?php if (! is_mobile()) { ?>
                                <i class="fa-regular fa-sticky-note" aria-hidden="true"></i>
                                <?php } else { ?>
                                <?= _l('association_notes'); ?>
                                <?php } ?>
                                <span class="notes-total">
                                    <?php if ($totalNotes > 0) { ?>
                                    <span class="badge"><?= e($totalNotes); ?></span>
                                    <?php } ?>
                                </span>
                            </a>
                        </li>
                        <li role="presentation" data-toggle="tooltip"
                            title="<?= _l('emails_tracking'); ?>"
                            class="tab-separator">
                            <a href="#tab_emails_tracking" aria-controls="tab_emails_tracking" role="tab"
                                data-toggle="tab">
                                <?php if (! is_mobile()) { ?>
                                <i class="fa-regular fa-envelope-open" aria-hidden="true"></i>
                                <?php } else { ?>
                                <?= _l('emails_tracking'); ?>
                                <?php } ?>
                            </a>
                        </li>
                        <li role="presentation" data-toggle="tooltip"
                            data-title="<?= _l('view_tracking'); ?>"
                            class="tab-separator">
                            <a href="#tab_views" aria-controls="tab_views" role="tab" data-toggle="tab">
                                <?php if (! is_mobile()) { ?>
                                <i class="fa fa-eye"></i>
                                <?php } else { ?>
                                <?= _l('view_tracking'); ?>
                                <?php } ?>
                            </a>
                        </li>
                        <li role="presentation" data-toggle="tooltip"
                            data-title="<?= _l('toggle_full_view'); ?>"
                            class="tab-separator toggle_view">
                            <a href="#" onclick="small_table_full_view(); return false;">
                                <i class="fa fa-expand"></i>
                            </a>
                        </li>
                        <?php hooks()->do_action('after_admin_association_preview_template_tab_menu_last_item', $association); ?>
                    </ul>
                </div>
            </div>
            <?php
            $_me_staff    = get_staff(get_staff_user_id());
            $_client_type = $_me_staff ? ($_me_staff->client_type ?? '') : '';
            $_can_edit    = can_do_on_entity('edit', 'associations', (int) $association->userid, 'association');
            ?>

            <div class="tw-mt-4 tw-mb-3">
                <h2 class="tw-text-2xl tw-font-bold tw-text-neutral-800 tw-m-0 tw-leading-tight">
                    <?= e($association->company); ?>
                </h2>
            </div>

            <div class="row mtop20">
                <div class="col-md-3">
                    <span id="association-status-badge"><?= format_association_status($association->active == 1 ? 'active' : 'inactive', 'mtop5 inline-block'); ?></span>
                </div>
                <div class="col-md-9">
                    <div class="visible-xs">
                        <div class="mtop10"></div>
                    </div>
                    <div class="pull-right _buttons">
                        <?php if ($_can_edit) { ?>
                        <a href="<?= admin_url('associations/association/' . $association->userid); ?>"
                            class="btn btn-default btn-with-tooltip sm:!tw-px-3" data-toggle="tooltip"
                            title="<?= _l('edit_association_tooltip'); ?>"
                            data-placement="bottom"><i class="fa-regular fa-pen-to-square"></i></a>
                        <?php } ?>
                        <?php if (!empty($can_self_register)) { ?>
                        <?php if (empty($my_registration) || $my_registration->status === 'rejected') { ?>
                        <a href="#" class="btn btn-primary btn-with-tooltip sm:!tw-px-3"
                            data-toggle="tooltip" title="<?= _l('register_here'); ?>"
                            data-placement="bottom"
                            onclick="register_to_association(<?= (int) $association->userid; ?>); return false;">
                            <i class="fa fa-user-plus"></i> <?= _l('register_here'); ?>
                        </a>
                        <?php } else { ?>
                        <span class="btn btn-default btn-with-tooltip sm:!tw-px-3 disabled"
                            data-toggle="tooltip" data-placement="bottom"
                            title="<?= _l('assoc_registration_status_' . $my_registration->status); ?>">
                            <i class="fa fa-check-circle"></i> <?= _l('assoc_registration_status_' . $my_registration->status); ?>
                        </span>
                        <?php } ?>
                        <?php } ?>
                        <div class="btn-group">
                            <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false"><i
                                    class="fa-regular fa-file-pdf"></i><?= is_mobile() ? ' PDF' : ''; ?>
                                <span class="caret"></span></a>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li class="hidden-xs">
                                    <a
                                        href="<?= admin_url('associations/pdf/' . $association->userid . '?output_type=I'); ?>">
                                        <?= _l('view_pdf'); ?>
                                    </a>
                                </li>
                                <li class="hidden-xs">
                                    <a href="<?= admin_url('associations/pdf/' . $association->userid . '?output_type=I'); ?>"
                                        target="_blank">
                                        <?= _l('view_pdf_in_new_window'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= admin_url('associations/pdf/' . $association->userid); ?>">
                                        <?= _l('download'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= admin_url('associations/pdf/' . $association->userid . '?print=true'); ?>"
                                        target="_blank">
                                        <?= _l('print'); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default pull-left dropdown-toggle"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?= _l('more'); ?>
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <?php hooks()->do_action('after_association_view_as_client_link', $association); ?>
                                <?php if (staff_can('create', 'associations')) { ?>
                                <li>
                                    <a
                                        href="<?= admin_url('associations/copy/' . $association->userid); ?>">
                                        <?= _l('copy_association'); ?>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if (staff_can('delete', 'associations')) { ?>
                                <?php
                                               if ((get_option('delete_only_on_last_association') == 1 && is_last_association($association->userid))
                                                   || (get_option('delete_only_on_last_association') == 0)) { ?>
                                <li>
                                    <a href="<?= admin_url('associations/delete/' . $association->userid); ?>"
                                        class="text-danger delete-text _delete">
                                        <?= _l('delete_association_tooltip'); ?>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <hr class="hr-panel-separator" />
            <div class="tab-content">
                <div role="tabpanel" class="tab-pane ptop10 active" id="tab_association">
                    <?php if (isset($association->scheduled_email) && $association->scheduled_email) { ?>
                    <div class="alert alert-warning">
                        <?= e(_l('invoice_will_be_sent_at', _dt($association->scheduled_email->scheduled_at))); ?>
                        <?php if ($_can_edit) { ?>
                        <a href="#"
                            onclick="edit_association_scheduled_email(<?= $association->scheduled_email->id; ?>); return false;">
                            <?= _l('edit'); ?>
                        </a>
                        <?php } ?>
                    </div>
                    <?php } ?>
                    <?php
                    // Profile completeness — shown when company is inactive (for both admin and association user)
                    $_me_staff = get_staff(get_staff_user_id());
                    $_is_own_association = $_me_staff && $_me_staff->client_type === 'association'
                        && (int)$_me_staff->client_id === (int)$association->userid;
                    $_show_completeness = ($association->active == 0) && (is_admin() || $_is_own_association);

                    if ($_show_completeness) :
                        $_checks = [
                            _l('association_vat')        => !empty($association->vat),
                            _l('client_phonenumber')  => !empty($association->phonenumber),
                            _l('client_address')      => !empty($association->address),
                            _l('client_state')        => !empty($association->state),
                            _l('client_city')         => !empty($association->city),
                            _l('billing_address')     => !empty($association->billing_street) && !empty($association->billing_city) && !empty($association->billing_state),
                            _l('association_logo_light') => !empty($association->logo_light) || !empty($association->logo_dark),
                        ];
                        $_total    = count($_checks);
                        $_filled   = count(array_filter($_checks));
                        $_percent  = (int) round(($_filled / $_total) * 100);
                        $_missing  = array_keys(array_filter($_checks, fn($v) => !$v));
                        $_bar_class = $_percent === 100 ? 'success' : ($_percent >= 60 ? 'warning' : 'danger');
                    ?>
                    <div class="tw-mb-4">
                        <div class="tw-flex tw-items-center tw-justify-between tw-mb-1">
                            <span class="tw-text-sm tw-font-medium tw-text-neutral-600">
                                <?= _l('profile_completeness'); ?>
                            </span>
                            <span class="tw-text-sm tw-font-semibold text-<?= $_bar_class; ?>">
                                <?= $_filled; ?>/<?= $_total; ?> &mdash; <?= $_percent; ?>%
                            </span>
                        </div>
                        <div class="progress tw-mb-2" style="height:8px;margin-bottom:6px;">
                            <div class="progress-bar progress-bar-<?= $_bar_class; ?>"
                                style="width:<?= $_percent; ?>%;"></div>
                        </div>
                        <?php if (!empty($_missing)) { ?>
                        <div class="tw-text-xs tw-text-neutral-500">
                            <span class="tw-font-medium"><?= _l('profile_missing'); ?>:</span>
                            <?php foreach ($_missing as $_field) { ?>
                            <span class="label label-danger tw-mr-1 tw-mb-1"><?= e($_field); ?></span>
                            <?php } ?>
                        </div>
                        <?php } else { ?>
                        <div class="tw-text-xs tw-text-success">
                            <i class="fa fa-check-circle tw-mr-1"></i><?= _l('profile_complete_ready'); ?>
                        </div>
                        <?php } ?>
                    </div>
                    <?php endif; ?>

                    <div id="association-preview">
                        <div class="row">
                            <div class="col-md-6 col-sm-6">
                                <?php $tags = get_tags_in($association->userid, 'association'); ?>
                                <?php if (count($tags) > 0) { ?>
                                <p class="tw-mb-1">
                                    <i class="fa fa-tag text-muted tw-mr-1" data-toggle="tooltip"
                                       data-title="<?= e(implode(', ', $tags)); ?>"></i>
                                    <?php foreach ($tags as $tag) { ?>
                                    <span class="label label-default tw-mr-1"><?= e($tag); ?></span>
                                    <?php } ?>
                                </p>
                                <?php } ?>
                                <address class="tw-text-neutral-500">
                                    <?php if (!empty($association->phonenumber)) { ?>
                                    <p class="no-mbot"><?= e($association->phonenumber); ?></p>
                                    <?php } ?>
                                    <?php if (!empty($association->website)) { ?>
                                    <p class="no-mbot"><a href="<?= e($association->website); ?>" target="_blank"><?= e($association->website); ?></a></p>
                                    <?php } ?>
                                    <?php if (!empty($association->address)) { ?>
                                    <p class="no-mbot"><?= nl2br(e($association->address)); ?></p>
                                    <?php } ?>
                                    <?php if (!empty($association->city) || !empty($association->state)) { ?>
                                    <p class="no-mbot"><?= e(implode(', ', array_filter([$association->city ?? '', $association->state ?? '']))); ?></p>
                                    <?php } ?>
                                </address>
                            </div>
                        </div>
                        <div class="row mtop15">
                            <div class="col-md-12">
                                <table class="table table-condensed no-margin">
                                    <tbody>
                                        <?php if (!empty($association->vat)) { ?>
                                        <tr>
                                            <td class="tw-w-1/3 text-muted"><?= _l('client_vat_number'); ?></td>
                                            <td><?= e($association->vat); ?></td>
                                        </tr>
                                        <?php } ?>
                                        <?php if (!empty($association->state)) { ?>
                                        <tr>
                                            <td class="text-muted"><?= _l('client_state'); ?></td>
                                            <td><?= e($association->state); ?></td>
                                        </tr>
                                        <?php } ?>
                                        <?php if (!empty($association->city)) { ?>
                                        <tr>
                                            <td class="text-muted"><?= _l('client_city'); ?></td>
                                            <td><?= e($association->city); ?></td>
                                        </tr>
                                        <?php } ?>
                                        <?php if (!empty($association->zip)) { ?>
                                        <tr>
                                            <td class="text-muted"><?= _l('client_postal_code'); ?></td>
                                            <td><?= e($association->zip); ?></td>
                                        </tr>
                                        <?php } ?>
                                        <?php if (!empty($country_name)) { ?>
                                        <tr>
                                            <td class="text-muted"><?= _l('client_country'); ?></td>
                                            <td><?= e($country_name); ?></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if (!empty($coordinates)): ?>
                        <div class="row mtop15">
                            <div class="col-md-12">
                                <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                                    <span class="tw-text-sm tw-font-semibold tw-text-neutral-600">
                                        <i class="fa fa-map-marker tw-mr-1"></i><?= _l('location'); ?>
                                    </span>
                                    <a href="https://www.google.com/maps?q=<?= $coordinates->latitude; ?>,<?= $coordinates->longitude; ?>"
                                       target="_blank" rel="noopener"
                                       class="tw-text-xs tw-text-neutral-400 hover:tw-text-neutral-600">
                                        <i class="fa fa-external-link tw-mr-1"></i>Google Maps
                                    </a>
                                </div>
                                <?php if (!empty($coordinates->address)): ?>
                                <p class="tw-text-sm tw-text-neutral-500 tw-mb-2"><?= e($coordinates->address); ?></p>
                                <?php endif; ?>
                                <div id="map-preview-<?= $association->userid; ?>"
                                     style="height:180px; border:1px solid #ddd; border-radius:4px; background:#f5f5f5;"></div>
                            </div>
                        </div>
                        <script>
                        (function(){
                            var _lat=<?= (float)$coordinates->latitude; ?>,_lng=<?= (float)$coordinates->longitude; ?>;
                            var _el=document.getElementById('map-preview-<?= $association->userid; ?>');
                            function _im(){
                                if(!_el||!window.L)return;
                                delete L.Icon.Default.prototype._getIconUrl;
                                L.Icon.Default.mergeOptions({iconUrl:'<?= module_dir_url('apps','assets/js/leaflet/images/marker-icon.png'); ?>',iconRetinaUrl:'<?= module_dir_url('apps','assets/js/leaflet/images/marker-icon-2x.png'); ?>',shadowUrl:'<?= module_dir_url('apps','assets/js/leaflet/images/marker-shadow.png'); ?>'});
                                var m=L.map(_el,{zoomControl:true,dragging:false,scrollWheelZoom:false}).setView([_lat,_lng],15);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap',maxZoom:19}).addTo(m);
                                L.marker([_lat,_lng]).addTo(m);
                                setTimeout(function(){m.invalidateSize();},300);
                            }
                            if(window.L){_im();}else{var _c=document.createElement('link');_c.rel='stylesheet';_c.href='<?= module_dir_url('apps','assets/js/leaflet/leaflet.css'); ?>';document.head.appendChild(_c);var _s=document.createElement('script');_s.src='<?= module_dir_url('apps','assets/js/leaflet/leaflet.js'); ?>';_s.onload=_im;document.head.appendChild(_s);}
                        })();
                        </script>
                        <?php endif; ?>

                    </div>
                </div>
                <div role="tabpanel" class="tab-pane" id="tab_reminders">
                    <a href="#" data-toggle="modal" class="btn btn-primary"
                        data-target=".reminder-modal-association-<?= e($association->userid); ?>"><i
                            class="fa-regular fa-bell"></i>
                        <?= _l('association_set_reminder_title'); ?></a>
                    <hr />
                    <?php render_datatable([_l('reminder_description'), _l('reminder_date'), _l('reminder_staff'), _l('reminder_is_notified')], 'reminders'); ?>
                    <?php $this->load->view('admin/includes/modals/reminder', ['id' => $association->userid, 'name' => 'association', 'members' => $members, 'reminder_title' => _l('association_set_reminder_title')]); ?>
                </div>
                <div role="tabpanel" class="tab-pane ptop10" id="tab_emails_tracking">
                    <?php $this->load->view('admin/includes/emails_tracking', [
                        'tracked_emails' => get_tracked_emails($association->userid, 'association'),
                    ]); ?>
                </div>
                <div role="tabpanel" class="tab-pane" id="tab_notes">
                    <?= form_open(admin_url('associations/add_note/' . $association->userid), ['id' => 'association-notes', 'class' => 'association-notes-form']); ?>
                    <?= render_textarea('description'); ?>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary mtop15 mbot15">
                            <?= _l('association_add_note'); ?>
                        </button>
                    </div>
                    <?= form_close(); ?>
                    <hr />
                    <div class="mtop20" id="sales_notes_area">
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="tab_activity">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="activity-feed">
                                <?php foreach ($activity as $activity) {
                                    $_custom_data = false; ?>
                                <div class="feed-item"
                                    data-sale-activity-id="<?= e($activity['id']); ?>">
                                    <div class="date">
                                        <span class="text-has-action" data-toggle="tooltip"
                                            data-title="<?= e(_dt($activity['date'])); ?>">
                                            <?= e(time_ago($activity['date'])); ?>
                                        </span>
                                    </div>
                                    <div class="text">
                                        <?php if (is_numeric($activity['staffid']) && $activity['staffid'] != 0) { ?>
                                        <a
                                            href="<?= admin_url('profile/' . $activity['staffid']); ?>">
                                            <?= staff_profile_image($activity['staffid'], ['staff-profile-xs-image pull-left mright5']);
                                            ?>
                                        </a>
                                        <?php } ?>
                                        <?php
                                            $additional_data = '';
                                    if (! empty($activity['additional_data'])) {
                                        $additional_data = app_unserialize($activity['additional_data']);
                                        $i               = 0;

                                        foreach ((is_array($additional_data) ? $additional_data : []) as $data) {
                                            if (strpos($data, '<original_status>') !== false) {
                                                $original_status     = get_string_between($data, '<original_status>', '</original_status>');
                                                $additional_data[$i] = format_association_status($original_status, '', false);
                                            } elseif (strpos($data, '<new_status>') !== false) {
                                                $new_status          = get_string_between($data, '<new_status>', '</new_status>');
                                                $additional_data[$i] = format_association_status($new_status, '', false);
                                            } elseif (strpos($data, '<status>') !== false) {
                                                $status              = get_string_between($data, '<status>', '</status>');
                                                $additional_data[$i] = format_association_status($status, '', false);
                                            } elseif (strpos($data, '<custom_data>') !== false) {
                                                $_custom_data = get_string_between($data, '<custom_data>', '</custom_data>');
                                                unset($additional_data[$i]);
                                            }
                                            $i++;
                                        }
                                    }

                                    $_formatted_activity = _l($activity['description'], $additional_data);

                                    if ($_custom_data !== false) {
                                        $_formatted_activity .= ' - ' . $_custom_data;
                                    }

                                    if (! empty($activity['full_name'])) {
                                        $_formatted_activity = e($activity['full_name']) . ' - ' . $_formatted_activity;
                                    }

                                    echo $_formatted_activity;

                                    // Show plain-text diff when additional_data is a raw string (not serialized)
                                    if (!empty($activity['additional_data']) && $additional_data === false) {
                                        echo '<pre class="tw-text-xs tw-text-neutral-500 tw-mt-1 tw-mb-0 tw-whitespace-pre-wrap tw-bg-neutral-50 tw-rounded tw-p-2">' . e($activity['additional_data']) . '</pre>';
                                    }

                                    if (is_admin()) {
                                        echo '<a href="#" class="pull-right text-muted" onclick="delete_sale_activity(' . $activity['id'] . '); return false;"><i class="fa-regular fa-trash-can"></i></a>';
                                    } ?>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane ptop10" id="tab_views">
                    <?php
                  $views_activity = get_views_tracking('association', $association->userid);
if (count($views_activity) === 0) {
    echo '<h4 class="tw-m-0 tw-text-base tw-font-medium tw-text-neutral-500">' . _l('not_viewed_yet', _l('association_lowercase')) . '</h4>';
}

foreach ($views_activity as $activity) { ?>
                    <p class="text-success no-margin">
                        <?= _l('view_date') . ': ' . _dt($activity['date']); ?>
                    </p>
                    <p class="text-muted">
                        <?= _l('view_ip') . ': ' . $activity['view_ip']; ?>
                    </p>
                    <hr />
                    <?php } ?>
                </div>
                <?php hooks()->do_action('after_admin_association_preview_template_tab_content_last_item', $association); ?>
            </div>
        </div>
    </div>
</div>
<script>
    init_items_sortable(true);
    init_btn_with_tooltips();
    init_datepicker();
    init_selectpicker();
    init_form_reminder();
    init_tabs_scrollable();
    init_association_notes();
</script>
