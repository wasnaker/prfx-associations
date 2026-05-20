<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<dl class="tw-grid tw-grid-cols-2 md:tw-grid-cols-2 lg:tw-grid-cols-5 tw-gap-2 tw-mb-0">
    <?php
foreach ($totals as $key => $data) {
    $class = association_status_color_class($data['status']);
    $name  = association_status_by_id($data['status']); ?>
    <div
        class="tw-border tw-border-solid tw-border-neutral-300/80 tw-rounded-md tw-bg-white odd:last:tw-col-span-2 md:odd:last:tw-col-auto">
        <div class="tw-px-4 tw-py-5 sm:tw-px-4 sm:tw-py-2">
            <dt class="tw-font-normal text-<?= e($class); ?>">
                <?= e($name); ?>
            </dt>
            <dd class="tw-mt-1 tw-flex tw-items-baseline tw-justify-between md:tw-block lg:tw-flex">
                <div class="tw-flex tw-items-baseline tw-text-base tw-font-semibold tw-text-neutral-600">
                    <?= e(app_format_money($data['total'], $data['currency_name'])); ?>
                </div>
            </dd>
        </div>
    </div>
    <?php
} ?>
</dl>
<script>
    $(function() {
        init_selectpicker();
    });
</script>