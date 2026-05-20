<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="col-md-12">
            <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                <h4 class="tw-my-0 tw-font-bold tw-text-xl"><?= _l('assoc_surveyor_permits'); ?></h4>
                <div class="tw-flex tw-items-center tw-gap-2">
                    <a href="<?= admin_url('associations'); ?>" class="btn btn-default btn-xs">
                        <i class="fa fa-arrow-left tw-mr-1"></i><?= _l('associations'); ?>
                    </a>
                    <a href="#" class="btn btn-default btn-with-tooltip sm:!tw-px-3 toggle-small-view hidden-xs"
                        onclick="toggle_small_view('.table-association-surveyor-permit-list','#association-surveyor-permit-right-panel'); return false;"
                        data-toggle="tooltip" title="<?= _l('toggle_full_view'); ?>">
                        <i class="fa fa-angle-double-left"></i>
                    </a>
                </div>
            </div>

            <div class="row tw-mt-2">
                <div class="col-md-12" id="small-table">
                    <div class="panel_s">
                        <div class="panel-body">
                            <?= form_hidden('surveyor_permit_id', ''); ?>
                            <?= form_hidden('association_id', $filter_assoc_id); ?>
                            <?php $this->load->view('associations/admin/tables/surveyor_permits_list'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-7 small-table-right-col">
                    <div id="association-surveyor-permit-right-panel" class="hide"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
var hidden_columns = [];

$(function() {
    var base_url = admin_url + 'associations/list_surveyor_permits';

    initDataTable(
        '.table-association-surveyor-permit-list',
        base_url,
        false,
        false,
        {association_id: '[name="association_id"]'},
        [0, 'asc']
    );

});
</script>
</body>
</html>
