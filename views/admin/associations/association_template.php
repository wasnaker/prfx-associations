<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s accounting-template association">

    <div class="panel-body">

        <h4 class="tw-my-0 tw-font-bold tw-text-xl tw-mb-4">
            <?= isset($client) ? _l('edit_association') : _l('new_association'); ?>
        </h4>


        <?php if (isset($client)) { ?>
        <?= form_hidden('userid', $client->userid); ?>
        <?php } ?>

        <!-- Tab navigation -->
        <div class="horizontal-scrollable-tabs panel-full-width-tabs">
            <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
            <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
            <div class="horizontal-tabs">
                <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#tab-profile" aria-controls="tab-profile" role="tab" data-toggle="tab">
                            <?= _l('association_profile_details'); ?>
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tab-general" aria-controls="tab-general" role="tab" data-toggle="tab">
                            <?= _l('association_tab_general'); ?>
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tab-billing" aria-controls="tab-billing" role="tab" data-toggle="tab">
                            <?= _l('billing_shipping'); ?>
                        </a>
                    </li>
                    <?php if (isset($client)) { ?>
                    <li role="presentation">
                        <a href="#tab-location" aria-controls="tab-location" role="tab" data-toggle="tab"
                           onclick="setTimeout(function(){ var c=document.getElementById('map-picker-association-<?= isset($client) ? $client->userid : 0; ?>'); if(c && c._mapPicker) c._mapPicker.invalidate(); }, 100)">
                            <?= _l('location'); ?>
                        </a>
                    </li>
                    <?php } ?>
                </ul>
            </div>
        </div>

        <div class="tab-content mtop15">

            <!-- ===== Profile Tab ===== -->
            <div role="tabpanel" class="tab-pane active" id="tab-profile">
                <div class="row">
                    <div class="col-md-8">

                        <?php $value = (isset($client) ? $client->company : ''); ?>
                        <?= render_input('company', 'client_company', $value, 'text', isset($client) ? [] : ['autofocus' => true]); ?>


                        <?php $value = (isset($client) ? $client->phonenumber : ''); ?>
                        <?= render_input('phonenumber', 'client_phonenumber', $value); ?>

                        <?php $value = (isset($client) ? $client->website : ''); ?>
                        <?= render_input('website', 'client_website', $value); ?>



                        <hr />

                        <?php $value = (isset($client) ? $client->address : ''); ?>
                        <?= render_textarea('address', 'client_address', $value); ?>

                        <?php $value = (isset($client) ? $client->zip : ''); ?>
                        <?= render_input('zip', 'client_postal_code', $value); ?>

                        <div class="form-group select-placeholder">
                            <label class="control-label"><?= _l('client_state'); ?></label>
                            <select name="state" id="province-select" class="form-control selectpicker"
                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                                data-selected="<?= e(isset($client) ? $client->state : ''); ?>">
                                <option value=""></option>
                            </select>
                        </div>
                        <div class="form-group select-placeholder">
                            <label class="control-label"><?= _l('client_city'); ?></label>
                            <select name="city" id="city-select" class="form-control selectpicker"
                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                                data-selected="<?= e(isset($client) ? $client->city : ''); ?>">
                                <option value=""></option>
                            </select>
                        </div>

                    </div><!-- /col-md-8 -->
                </div><!-- /row -->
            </div><!-- /tab-profile -->

            <!-- ===== General Tab ===== -->
            <div role="tabpanel" class="tab-pane" id="tab-general">
                <div class="row">
                    <?php foreach (['light' => _l('association_logo_light'), 'dark' => _l('association_logo_dark')] as $_ltype => $_llabel) {
                        $_lcol    = 'logo_' . $_ltype;
                        $_lfile   = isset($client) ? ($client->$_lcol ?? null) : null;
                        $_lurl    = $_lfile ? base_url('uploads/client_logos/' . $client->userid . '/' . rawurlencode($_lfile)) : null;
                        $_ldelete = isset($client) ? admin_url('associations/delete_logo/' . $client->userid . '/' . $_ltype) : '';
                    ?>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label"><?= $_llabel; ?></label>
                            <?php if ($_lurl) { ?>
                            <div class="tw-mb-2">
                                <img src="<?= e($_lurl); ?>" class="img-responsive tw-max-h-20 tw-mb-2 tw-border tw-rounded tw-p-1" />
                                <div>
                                    <a href="<?= e($_lurl); ?>" download class="btn btn-xs btn-default tw-mr-1">
                                        <i class="fa fa-download tw-mr-1"></i><?= _l('download'); ?>
                                    </a>
                                    <a href="<?= e($_ldelete); ?>" class="btn btn-xs btn-danger _delete">
                                        <i class="fa fa-trash tw-mr-1"></i><?= _l('association_logo_delete'); ?>
                                    </a>
                                </div>
                            </div>
                            <?php } ?>
                            <input type="file" name="<?= $_ltype === 'light' ? 'logo_light' : 'logo_dark'; ?>" class="form-control" accept="image/jpeg,image/png,image/gif">
                            <p class="help-block"><?= _l('association_logo_upload_notice'); ?></p>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <div class="row mtop15">
                    <div class="col-md-12">
                        <div class="tw-bg-neutral-50 tw-py-3 tw-px-4 tw-rounded-lg tw-border tw-border-solid tw-border-neutral-200">
                            <div class="checkbox checkbox-primary -tw-mb-0.5">
                                <input type="checkbox" name="enforce_minimum_price" id="enforce_minimum_price" value="1"
                                    <?= isset($client) && !empty($client->enforce_minimum_price) ? 'checked' : ''; ?>>
                                <label for="enforce_minimum_price">
                                    <?= _l('association_enforce_minimum_price'); ?>
                                </label>
                            </div>
                            <p class="tw-ml-7 tw-mb-0 text-muted"><?= _l('association_enforce_minimum_price_help'); ?></p>
                        </div>
                    </div>
                </div>
            </div><!-- /tab-general -->

            <!-- Billing & Shipping removed -->
            <div role="tabpanel" class="tab-pane" id="tab-billing">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="tw-font-semibold tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                            <?= _l('billing_address'); ?>
                            <a href="#" class="billing-same-as-association tw-text-sm tw-text-neutral-500 hover:tw-text-neutral-700">
                                <?= _l('association_billing_same_as_profile'); ?>
                            </a>
                        </h4>

                        <?php $value = (isset($client) ? $client->billing_street : ''); ?>
                        <?= render_textarea('billing_street', 'billing_street', $value); ?>

                        <?php $value = (isset($client) ? $client->billing_zip : ''); ?>
                        <?= render_input('billing_zip', 'billing_zip', $value); ?>

                        <div class="form-group select-placeholder">
                            <label class="control-label"><?= _l('billing_state'); ?></label>
                            <select name="billing_state" id="billing-province-select" class="form-control selectpicker"
                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                                data-selected="<?= e(isset($client) ? $client->billing_state : ''); ?>">
                                <option value=""></option>
                            </select>
                        </div>
                        <div class="form-group select-placeholder">
                            <label class="control-label"><?= _l('billing_city'); ?></label>
                            <select name="billing_city" id="billing-city-select" class="form-control selectpicker"
                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                                data-selected="<?= e(isset($client) ? $client->billing_city : ''); ?>">
                                <option value=""></option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h4 class="tw-font-semibold tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                            <span>
                                <i class="fa-regular fa-circle-question tw-mr-1" data-toggle="tooltip"
                                    data-title="<?= _l('association_shipping_address_notice'); ?>"></i>
                                <?= _l('shipping_address'); ?>
                            </span>
                            <a href="#" class="association-copy-billing-address tw-text-sm tw-text-neutral-500 hover:tw-text-neutral-700">
                                <?= _l('association_billing_copy'); ?>
                            </a>
                        </h4>

                        <?php $value = (isset($client) ? $client->shipping_street : ''); ?>
                        <?= render_textarea('shipping_street', 'shipping_street', $value); ?>

                        <?php $value = (isset($client) ? $client->shipping_zip : ''); ?>
                        <?= render_input('shipping_zip', 'shipping_zip', $value); ?>

                        <div class="form-group select-placeholder">
                            <label class="control-label"><?= _l('shipping_state'); ?></label>
                            <select name="shipping_state" id="shipping-province-select" class="form-control selectpicker"
                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                                data-selected="<?= e(isset($client) ? $client->shipping_state : ''); ?>">
                                <option value=""></option>
                            </select>
                        </div>
                        <div class="form-group select-placeholder">
                            <label class="control-label"><?= _l('shipping_city'); ?></label>
                            <select name="shipping_city" id="shipping-city-select" class="form-control selectpicker"
                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                                data-selected="<?= e(isset($client) ? $client->shipping_city : ''); ?>">
                                <option value=""></option>
                            </select>
                        </div>
                    </div>
                </div><!-- /row billing+shipping -->

                <?php if (isset($client)
                    && (total_rows(db_prefix() . 'invoices', ['clientid' => $client->userid]) > 0
                        || total_rows(db_prefix() . 'estimates', ['clientid' => $client->userid]) > 0
                        || total_rows(db_prefix() . 'creditnotes', ['clientid' => $client->userid]) > 0)) { ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="tw-bg-neutral-50 tw-py-3 tw-px-4 tw-rounded-lg tw-border tw-border-solid tw-border-neutral-200">
                            <div class="checkbox checkbox-primary -tw-mb-0.5">
                                <input type="checkbox" name="update_all_other_transactions" id="update_all_other_transactions">
                                <label for="update_all_other_transactions">
                                    <?= _l('association_update_address_info_on_invoices'); ?>
                                </label>
                            </div>
                            <p class="tw-ml-7 tw-mb-0"><?= _l('association_update_address_info_on_invoices_help'); ?></p>
                            <div class="checkbox checkbox-primary">
                                <input type="checkbox" name="update_credit_notes" id="update_credit_notes">
                                <label for="update_credit_notes">
                                    <?= _l('association_profile_update_credit_notes'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>

            </div><!-- /tab-billing -->

            <!-- ===== Location Tab ===== -->
            <?php if (isset($client)) { ?>
            <div role="tabpanel" class="tab-pane" id="tab-location">
                <div class="row">
                    <div class="col-md-8">
                        <?php
                        $userid        = $client->userid;
                        $entity_type   = 'association';
                        $lat_field     = 'map_latitude';
                        $lng_field     = 'map_longitude';
                        $address_field = 'map_address';
                        $height        = 400;
                        $label         = '';
                        include module_views_path('apps', 'partials/map_picker.php');
                        ?>
                    </div>
                </div>
            </div><!-- /tab-location -->
            <?php } ?>

        </div><!-- /tab-content -->

    </div><!-- /panel-body -->

</div>

<div class="btn-bottom-pusher"></div>
<div class="btn-bottom-toolbar text-right">
    <button type="submit" form="association-form" class="btn-tr btn btn-primary">
        <?php echo _l('submit'); ?>
    </button>
    <a href="<?= admin_url('associations'); ?>" class="btn btn-default tw-ml-2">
        <?= _l('cancel'); ?>
    </a>
</div>
