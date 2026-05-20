<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_buttons tw-mb-3 sm:tw-mb-5">
                    <?php if (staff_can('create', 'associations')) {
                        $this->load->view('admin/associations/associations_top_stats');
                    } ?>
                    <div class="row">
                        <div class="col-md-8">
                            <?php if (staff_can('create', 'associations')) { ?>
                            <a href="<?= admin_url('associations/association'); ?>"
                                class="btn btn-primary pull-left new">
                                <i class="fa-regular fa-plus tw-mr-1"></i>
                                <?= _l('create_new_association'); ?>
                            </a>
                            <div class="display-block pull-left mleft10">
                                <a href="#" class="btn btn-default associations-total"
                                    onclick="slideToggle('#stats-top'); init_associations_total(true); return false;"
                                    data-toggle="tooltip"
                                    title="<?= _l('view_stats_tooltip'); ?>"><i
                                        class="fa fa-bar-chart"></i></a>
                            </div>
                            <?php } ?>
                            <a href="<?= admin_url('associations/pipeline/' . $switch_pipeline); ?>"
                                class="btn btn-default mleft5 pull-left" data-toggle="tooltip" data-placement="top"
                                data-title="<?= _l('switch_to_list_view'); ?>">
                                <i class="fa-solid fa-table-list"></i>
                            </a>
                        </div>
                        <div class="col-md-4" data-toggle="tooltip" data-placement="top"
                            data-title="<?= _l('search_by_tags'); ?>">
                            <?= render_input('search', '', '', 'search', ['data-name' => 'search', 'onkeyup' => 'association_pipeline();', 'placeholder' => _l('search_associations')], [], 'no-margin') ?>
                            <?= form_hidden('sort_type'); ?>
                            <?= form_hidden('sort', (get_option('default_associations_pipeline_sort') != '' ? get_option('default_associations_pipeline_sort_type') : '')); ?>
                        </div>
                    </div>
                </div>
                <div class="animated mtop5 fadeIn">
                    <?= form_hidden('associationid', $associationid); ?>
                    <div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="kanban-leads-sort">
                                    <span
                                        class="bold"><?= _l('associations_pipeline_sort'); ?>:
                                    </span>
                                    <a href="#" onclick="associations_pipeline_sort('datecreated'); return false"
                                        class="datecreated">
                                        <?php if (get_option('default_associations_pipeline_sort') == 'datecreated') {
                                            echo '<i class="kanban-sort-icon fa fa-sort-amount-' . strtolower(get_option('default_associations_pipeline_sort_type')) . '"></i> ';
                                        } ?>
                                        <?= _l('associations_sort_datecreated'); ?>
                                    </a>
                                    |
                                    <a href="#" onclick="associations_pipeline_sort('date'); return false" class="date">
                                        <?php if (get_option('default_associations_pipeline_sort') == 'date') {
                                            echo '<i class="kanban-sort-icon fa fa-sort-amount-' . strtolower(get_option('default_associations_pipeline_sort_type')) . '"></i> ';
                                        } ?>
                                        <?= _l('associations_sort_association_date'); ?>
                                    </a>
                                    |
                                    <a href="#" onclick="associations_pipeline_sort('pipeline_order');return false;"
                                        class="pipeline_order">
                                        <?php if (get_option('default_associations_pipeline_sort') == 'pipeline_order') {
                                            echo '<i class="kanban-sort-icon fa fa-sort-amount-' . strtolower(get_option('default_associations_pipeline_sort_type')) . '"></i> ';
                                        } ?>
                                        <?= _l('associations_sort_pipeline'); ?>
                                    </a>
                                    |
                                    <a href="#" onclick="associations_pipeline_sort('expirydate');return false;"
                                        class="expirydate">
                                        <?php if (get_option('default_associations_pipeline_sort') == 'expirydate') {
                                            echo '<i class="kanban-sort-icon fa fa-sort-amount-' . strtolower(get_option('default_associations_pipeline_sort_type')) . '"></i> ';
                                        } ?>
                                        <?= _l('associations_sort_expiry_date'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div id="association-pipeline">
                                <div class="container-fluid">
                                    <div id="kan-ban"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="association">
</div>

<?php init_tail(); ?>
<script>
    $(function() {
        association_pipeline();
    });
</script>
</body>

</html>