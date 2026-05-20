<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12">

    <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
        <div class="tw-flex tw-items-center tw-gap-2">
            <?php
            $_me           = get_staff(get_staff_user_id());
            $_is_cust_user = $_me && $_me->client_type === 'association';
            ?>
            <?php if (staff_can('create', 'associations') && !$_is_cust_user) { ?>
            <a href="<?= admin_url('associations/association'); ?>" class="btn btn-primary">
                <i class="fa fa-plus tw-mr-1"></i><?= _l('create_new_association'); ?>
            </a>
            <?php } ?>
        </div>
        <div class="tw-flex tw-items-center tw-gap-2">
            <a href="#" class="btn btn-default btn-with-tooltip sm:!tw-px-3 toggle-small-view hidden-xs"
                onclick="toggle_small_view('.table-associations','#association'); return false;"
                data-toggle="tooltip" title="<?= _l('toggle_full_view'); ?>">
                <i class="fa fa-angle-double-left"></i>
            </a>
        </div>
    </div>

    <div class="row tw-mt-2">
        <div class="col-md-12" id="small-table">
            <div class="panel_s">
                <div class="panel-body">
                    <?= form_hidden('associationid', isset($associationid) ? $associationid : ''); ?>
                    <?php $this->load->view('admin/associations/table_html'); ?>
                </div>
            </div>
        </div>
        <div class="col-md-7 small-table-right-col">
            <div id="association" class="hide"></div>
        </div>
    </div>
</div>
