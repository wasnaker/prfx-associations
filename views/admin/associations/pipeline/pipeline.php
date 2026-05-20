<?php defined('BASEPATH') or exit('No direct script access allowed');
$i                   = 0;
$has_permission_edit = staff_can('edit', 'associations');

foreach ($association_statuses as $status) {
    $kanBan = new app\services\associations\AssociationsPipeline($status);
    $kanBan->search($this->input->get('search'))
        ->sortBy($this->input->get('sort_by'), $this->input->get('sort'));
    if ($this->input->get('refresh')) {
        $kanBan->refresh($this->input->get('refresh')[$status] ?? null);
    }
    $associations       = $kanBan->get();
    $total_associations = count($associations);
    $total_pages     = $kanBan->totalPages(); ?>
<ul class="kan-ban-col" data-col-status-id="<?= e($status); ?>"
    data-total-pages="<?= e($total_pages); ?>"
    data-total="<?= e($total_associations); ?>">
    <li class="kan-ban-col-wrapper">
        <div
            class="panel_s panel-<?= association_status_color_class($status); ?> no-mbot">
            <div class="panel-heading tw-font-medium">
                <?= association_status_by_id($status); ?> -
                <span class="tw-text-sm">
                    <?= $kanBan->countAll() . ' ' . _l('associations') ?>
                </span>
            </div>
            <div class="kan-ban-content-wrapper">
                <div class="kan-ban-content">
                    <ul class="sortable<?php if ($has_permission_edit) {
                        echo ' status pipeline-status';
                    } ?>"
                        data-status-id="<?= e($status); ?>">
                        <?php
                            foreach ($associations as $association) {
                                $this->load->view('admin/associations/pipeline/_kanban_card', ['association' => $association, 'status' => $status]);
                            } ?>
                        <?php if ($total_associations > 0) { ?>
                        <li class="text-center not-sortable kanban-load-more"
                            data-load-status="<?= e($status); ?>">
                            <a href="#" class="btn btn-default btn-block<?php if ($total_pages <= 1 || $kanBan->getPage() === $total_pages) {
                                echo ' disabled';
                            } ?>"
                                data-page="<?= $kanBan->getPage(); ?>"
                                onclick="kanban_load_more(<?= e($status); ?>,this,'associations/pipeline_load_more',310,360); return false;"
                                ;><?= _l('load_more'); ?></a>
                        </li>
                        <?php } ?>
                        <li class="text-center not-sortable mtop30 kanban-empty<?php if ($total_associations > 0) {
                            echo ' hide';
                        } ?>">
                            <h4>
                                <i class="fa-solid fa-circle-notch" aria-hidden="true"></i><br /><br />
                                <?= _l('no_associations_found'); ?>
                            </h4>
                        </li>
                    </ul>
                </div>
            </div>
    </li>
</ul>
<?php $i++;
} ?>