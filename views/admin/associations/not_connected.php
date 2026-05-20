<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12 no-padding">
    <div class="panel_s">
        <div class="panel-body text-center tw-py-16 tw-text-neutral-400">
            <i class="fa fa-lock fa-4x"></i>
            <p class="tw-mt-5 tw-text-base tw-font-semibold tw-text-neutral-500"><?= _l('access_denied'); ?></p>
            <p class="tw-mt-1 tw-text-sm"><?= _l('association_not_connected'); ?></p>
            <a href="<?= admin_url('connections'); ?>" class="btn btn-default btn-sm tw-mt-5">
                <i class="fa fa-link tw-mr-1"></i> <?= _l('manage_connections'); ?>
            </a>
        </div>
    </div>
</div>
