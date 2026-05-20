<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12 no-padding">
    <div class="panel_s">
        <div class="panel-body">

            <div class="row mtop20">
                <div class="col-md-8">
                    <h4 class="tw-mt-0 tw-mb-1 tw-font-bold tw-text-neutral-800">
                        <a href="<?= admin_url('surveyors/list_surveyors/' . (int)$surveyor->userid); ?>">
                            <?= e($surveyor->company); ?>
                        </a>
                    </h4>
                    <?php if ($surveyor->active == 1) { ?>
                    <span class="label label-success inline-block"><?= _l('active'); ?></span>
                    <?php } else { ?>
                    <span class="label label-default inline-block"><?= _l('inactive'); ?></span>
                    <?php } ?>
                </div>
                <div class="col-md-4">
                    <div class="pull-right">
                        <a href="<?= admin_url('surveyors/list_surveyors/' . (int)$surveyor->userid); ?>"
                            class="btn btn-default btn-xs btn-with-tooltip"
                            data-toggle="tooltip" data-placement="bottom"
                            title="<?= _l('view_full_profile'); ?>">
                            <i class="fa fa-external-link fa-fw"></i>
                        </a>
                    </div>
                </div>
            </div>

            <hr class="tw-my-4" />

            <table class="table table-condensed no-margin">
                <tbody>
                    <?php if ($association) { ?>
                    <tr>
                        <td class="tw-w-1/3 text-muted"><?= _l('association_name'); ?></td>
                        <td>
                            <a href="<?= admin_url('associations/list_associations/' . (int)$association->userid); ?>">
                                <?= e($association->company); ?>
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <td class="text-muted"><?= _l('assoc_sp_group'); ?></td>
                        <td><?= $group ? e($group->name) : '—'; ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?= _l('assoc_sp_number'); ?></td>
                        <td><?= e($permit->number ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?= _l('assoc_sp_publish_date'); ?></td>
                        <td><?= !empty($permit->publish_date) ? _d($permit->publish_date) : '—'; ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?= _l('assoc_sp_expired_date'); ?></td>
                        <td><?= !empty($permit->expired_date) ? _d($permit->expired_date) : '—'; ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?= _l('assoc_sp_status'); ?></td>
                        <td><span class="label label-warning"><?= _l('permit_status_pending'); ?></span></td>
                    </tr>
                    <?php if (!empty($permit->file)) { ?>
                    <tr>
                        <td class="text-muted"><?= _l('assoc_sp_file'); ?></td>
                        <td>
                            <a href="<?= base_url('uploads/surveyor_permits/' . $permit->file); ?>" target="_blank"
                                class="btn btn-xs btn-default">
                                <i class="fa fa-download fa-fw"></i> <?= _l('download'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>

            <?php if (staff_can('approve_surveyor_permit', 'associations')) { ?>
            <hr class="tw-my-4" />
            <div class="tw-flex tw-gap-2">
                <button type="button" class="btn btn-success"
                    onclick="sp_mark_permit(<?= (int)$permit->id; ?>); return false;">
                    <i class="fa fa-check fa-fw"></i> <?= _l('assoc_sp_approve'); ?>
                </button>
            </div>
            <?php } ?>

        </div>
    </div>
</div>

<script>
function sp_mark_permit(permit_id) {
    if (!confirm('<?= _l('assoc_sp_approve'); ?>?')) { return; }

    $.post(admin_url + 'associations/association_surveyor_permit/mark_surveyor_permit/' + permit_id)
     .done(function(response) {
        response = JSON.parse(response);
        if (response.success) {
            alert_float('success', response.message);
            $('#association-surveyor-permit-right-panel').addClass('hide').html('');
            $('input[name="association_surveyor_permit_id"]').val('');
            $.fn.DataTable.isDataTable('.table-association-surveyor-permit-list')
                && $('.table-association-surveyor-permit-list').DataTable().ajax.reload(null, false);
        } else {
            alert_float('danger', response.message);
        }
    }).fail(function() {
        alert_float('danger', '<?= _l('something_went_wrong'); ?>');
    });
}
</script>
