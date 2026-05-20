<div class="quick-top-stats tw-mb-6">
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-3">
        <?php foreach ($association_statuses as $status) {
            $percent_data = get_associations_percent_by_status($status); ?>
        <div class="tw-bg-white tw-border tw-border-solid tw-border-neutral-300/80 tw-shadow-sm tw-py-3 tw-px-4 tw-rounded-lg">
            <div class="tw-flex tw-items-center">
                <span class="tw-font-normal tw-text-base tw-inline-flex tw-items-center text-<?= association_status_color_class($status); ?>">
                    <?= format_association_status($status, '', false); ?>
                </span>
                <span class="tw-ml-2 tw-text-xs tw-text-neutral-500 tw-mt-px">
                    (<?= e($percent_data['percent']); ?>%)
                </span>
            </div>
            <div class="tw-mt-1 tw-text-neutral-600">
                <span class="tw-font-semibold">
                    <?= e($percent_data['total_by_status']); ?> / <?= e($percent_data['total']); ?>
                </span>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
