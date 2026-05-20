<?php defined('BASEPATH') or exit('No direct script access allowed');
if ($association['status'] == $status) { ?>
<li data-association-id="<?= e($association['id']); ?>"
    class="<?= $association['quotationid'] != null ? 'not-sortable' : ''; ?>">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-base pipeline-heading tw-mb-0.5">
                    <a href="<?= admin_url('associations/list_associations/' . $association['id']); ?>"
                        class="tw-text-neutral-700 hover:tw-text-neutral-900 active:tw-text-neutral-900"
                        onclick="association_pipeline_open(<?= e($association['id']); ?>); return false;">
                        <?= e(format_association_number($association['id'])); ?>
                    </a>
                    <?php if (staff_can('edit', 'associations')) { ?>
                    <a href="<?= admin_url('associations/association/' . $association['id']); ?>"
                        target="_blank" class="pull-right tw-font-medium">
                        <small>
                            <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                        </small>
                    </a>
                    <?php } ?>
                </h4>
                <span class="tw-inline-block tw-w-full tw-mb-2">
                    <a href="<?= admin_url('clients/client/' . $association['clientid']); ?>"
                        target="_blank">
                        <?= e($association['company']); ?>
                    </a>
                </span>
            </div>
            <div class="col-md-12">
                <div class="tw-flex">
                    <div class="tw-grow">
                        <p class="tw-mb-0 tw-text-sm tw-text-neutral-700">
                            <span class="tw-text-neutral-500">
                                <?= _l('association_total'); ?>:
                            </span>
                            <?= e(app_format_money($association['total'], $association['currency_name'])); ?>
                        </p>
                        <p class="tw-mb-0 tw-text-sm tw-text-neutral-700">
                            <span class="tw-text-neutral-500">
                                <?= _l('association_data_date'); ?>:
                            </span>
                            <?= e(_d($association['date'])); ?>
                        </p>
                        <?php if (is_date($association['expirydate']) || ! empty($association['expirydate'])) { ?>
                        <p class="tw-mb-0 tw-text-sm tw-text-neutral-700">
                            <span class="tw-text-neutral-500">
                                <?= _l('association_data_expiry_date'); ?>:
                            </span>
                            <?= e(_d($association['expirydate'])); ?>
                        </p>
                        <?php } ?>
                    </div>
                    <div class="tw-shrink-0 text-right">
                        <small>
                            <i class="fa fa-paperclip"></i>
                            <?= _l('association_notes'); ?>:
                            <?= total_rows(db_prefix() . 'notes', [
                                'rel_id'   => $association['id'],
                                'rel_type' => 'association',
                            ]); ?>
                        </small>
                    </div>
                    <?php $tags = get_tags_in($association['id'], 'association'); ?>
                    <?php if (count($tags) > 0) { ?>
                    <div class="kanban-tags tw-text-sm tw-inline-flex">
                        <?= render_tags($tags); ?>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</li>
<?php } ?>