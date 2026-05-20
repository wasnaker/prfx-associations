<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="col-md-12">

            <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                <h4 class="tw-my-0 tw-font-bold tw-text-xl">
                    <?= _l('pending_registrations'); ?>
                    <?php $total = count($stage1) + count($stage2); ?>
                    <?php if ($total > 0) { ?>
                    <span class="badge badge-danger tw-ml-2"><?= $total; ?></span>
                    <?php } ?>
                </h4>
                <a href="<?= admin_url('associations'); ?>" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left tw-mr-1"></i> <?= _l('associations'); ?>
                </a>
            </div>

            <!-- Stage 1: Awaiting User Activation -->
            <div class="panel_s">
                <div class="panel-heading">
                    <h4 class="tw-font-semibold tw-mb-0">
                        <span class="label label-warning tw-mr-2">1</span>
                        <?= _l('stage_pending_user'); ?>
                        <?php if (count($stage1) > 0) { ?>
                        <span class="badge badge-warning tw-ml-1"><?= count($stage1); ?></span>
                        <?php } ?>
                    </h4>
                </div>
                <div class="panel-body">
                    <?php if (empty($stage1)) { ?>
                    <p class="text-muted tw-mb-0"><i class="fa fa-check tw-mr-1"></i><?= _l('no_pending_registrations'); ?></p>
                    <?php } else { ?>
                    <table class="table table-striped dt-table" data-order-col="3" data-order-type="desc">
                        <thead>
                            <tr>
                                <th><?= _l('registration_association_name'); ?></th>
                                <th><?= _l('client_firstname'); ?></th>
                                <th><?= _l('client_email'); ?></th>
                                <th><?= _l('association_vat'); ?></th>
                                <th><?= _l('date_registered'); ?></th>
                                <th class="text-right no-sort"><?= _l('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stage1 as $row) { ?>
                            <tr>
                                <td class="tw-font-medium"><?= e($row['company'] ?? '—'); ?></td>
                                <td><?= e($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                <td><?= e($row['email']); ?></td>
                                <td><?= e($row['vat'] ?? '—'); ?></td>
                                <td data-order="<?= e($row['datecreated']); ?>"><?= _dt($row['datecreated']); ?></td>
                                <td class="text-right">
                                    <a href="#"
                                       onclick="approveConfirm('<?= admin_url('associations/activate_user/' . $row['staffid']); ?>','<?= e($row['firstname'] . ' ' . $row['lastname']); ?>'); return false;"
                                       class="btn btn-warning btn-sm tw-mr-1">
                                        <i class="fa fa-user-check tw-mr-1"></i><?= _l('activate_user'); ?>
                                    </a>
                                    <a href="#"
                                       onclick="rejectConfirm('<?= admin_url('associations/reject_registration/' . $row['staffid']); ?>','<?= e($row['firstname'] . ' ' . $row['lastname']); ?>'); return false;"
                                       class="btn btn-danger btn-sm">
                                        <i class="fa fa-times tw-mr-1"></i><?= _l('reject'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <?php } ?>
                </div>
            </div>

            <!-- Stage 2: Awaiting Association Approval -->
            <div class="panel_s">
                <div class="panel-heading">
                    <h4 class="tw-font-semibold tw-mb-0">
                        <span class="label label-info tw-mr-2">2</span>
                        <?= _l('stage_pending_company'); ?>
                        <?php if (count($stage2) > 0) { ?>
                        <span class="badge badge-info tw-ml-1"><?= count($stage2); ?></span>
                        <?php } ?>
                    </h4>
                </div>
                <div class="panel-body">
                    <?php if (empty($stage2)) { ?>
                    <p class="text-muted tw-mb-0"><i class="fa fa-check tw-mr-1"></i><?= _l('no_pending_registrations'); ?></p>
                    <?php } else { ?>
                    <table class="table table-striped dt-table" data-order-col="3" data-order-type="desc">
                        <thead>
                            <tr>
                                <th><?= _l('registration_association_name'); ?></th>
                                <th><?= _l('client_firstname'); ?></th>
                                <th><?= _l('client_email'); ?></th>
                                <th><?= _l('date_registered'); ?></th>
                                <th class="no-sort"><?= _l('completeness'); ?></th>
                                <th class="text-right no-sort"><?= _l('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stage2 as $row) { ?>
                            <tr>
                                <td class="tw-font-medium">
                                    <a href="<?= admin_url('associations#' . $row['client_id']); ?>">
                                        <?= e($row['company'] ?? '—'); ?>
                                    </a>
                                </td>
                                <td><?= e($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                <td><?= e($row['email']); ?></td>
                                <td data-order="<?= e($row['datecreated']); ?>"><?= _dt($row['datecreated']); ?></td>
                                <td>
                                    <?php foreach ($row['_checks'] as $check) { ?>
                                    <span class="label <?= $check['ok'] ? 'label-success' : 'label-danger'; ?> tw-mr-1 tw-mb-1 tw-inline-block">
                                        <i class="fa <?= $check['ok'] ? 'fa-check' : 'fa-times'; ?> tw-mr-1"></i><?= e($check['label']); ?>
                                    </span>
                                    <?php } ?>
                                </td>
                                <td class="text-right">
                                    <?php if ($row['_ready']) { ?>
                                    <a href="#"
                                       onclick="approveConfirm('<?= admin_url('associations/approve_registration/' . $row['staffid']); ?>','<?= e($row['firstname'] . ' ' . $row['lastname']); ?>'); return false;"
                                       class="btn btn-success btn-sm tw-mr-1">
                                        <i class="fa fa-check tw-mr-1"></i><?= _l('approve'); ?>
                                    </a>
                                    <?php } else { ?>
                                    <button class="btn btn-default btn-sm tw-mr-1" disabled
                                        data-toggle="tooltip" title="<?= _l('registration_incomplete'); ?>">
                                        <i class="fa fa-check tw-mr-1"></i><?= _l('approve'); ?>
                                    </button>
                                    <?php } ?>
                                    <a href="#"
                                       onclick="rejectConfirm('<?= admin_url('associations/reject_registration/' . $row['staffid']); ?>','<?= e($row['firstname'] . ' ' . $row['lastname']); ?>'); return false;"
                                       class="btn btn-danger btn-sm">
                                        <i class="fa fa-times tw-mr-1"></i><?= _l('reject'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <?php } ?>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- Approve Confirmation Modal -->
<div class="modal fade" id="approve-confirm-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content" style="border:none;border-radius:12px;overflow:hidden;">
            <div style="background:linear-gradient(135deg,#22c55e 0%,#15803d 100%);padding:24px 24px 18px;">
                <div class="tw-flex tw-items-center tw-gap-3">
                    <div style="background:rgba(255,255,255,0.2);border-radius:50%;width:42px;height:42px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa fa-check" style="font-size:18px;color:#fff;"></i>
                    </div>
                    <div>
                        <h4 style="color:#fff;margin:0;font-weight:700;font-size:16px;"><?= _l('approve_registration'); ?></h4>
                        <p id="approve-confirm-name" style="color:rgba(255,255,255,0.85);margin:0;font-size:12px;"></p>
                    </div>
                </div>
            </div>
            <div class="modal-body" style="padding:20px 24px;">
                <p class="tw-text-sm tw-text-neutral-600 tw-mb-0"><?= _l('confirm_approve_registration'); ?></p>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:14px 24px;background:#fafafa;">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times tw-mr-1"></i><?= _l('cancel'); ?>
                </button>
                <a id="approve-confirm-btn" href="#" class="btn btn-success" style="background:linear-gradient(135deg,#22c55e,#15803d);border:none;color:#fff;">
                    <i class="fa fa-check tw-mr-1"></i><?= _l('approve'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Reject Confirmation Modal -->
<div class="modal fade" id="reject-confirm-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content" style="border:none;border-radius:12px;overflow:hidden;">
            <div style="background:linear-gradient(135deg,#ef4444 0%,#b91c1c 100%);padding:24px 24px 18px;">
                <div class="tw-flex tw-items-center tw-gap-3">
                    <div style="background:rgba(255,255,255,0.2);border-radius:50%;width:42px;height:42px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa fa-times" style="font-size:18px;color:#fff;"></i>
                    </div>
                    <div>
                        <h4 style="color:#fff;margin:0;font-weight:700;font-size:16px;"><?= _l('reject_registration'); ?></h4>
                        <p id="reject-confirm-name" style="color:rgba(255,255,255,0.85);margin:0;font-size:12px;"></p>
                    </div>
                </div>
            </div>
            <div class="modal-body" style="padding:20px 24px;">
                <p class="tw-text-sm tw-text-neutral-600 tw-mb-0"><?= _l('confirm_reject_registration'); ?></p>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:14px 24px;background:#fafafa;">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times tw-mr-1"></i><?= _l('cancel'); ?>
                </button>
                <a id="reject-confirm-btn" href="#" class="btn btn-danger" style="background:linear-gradient(135deg,#ef4444,#b91c1c);border:none;color:#fff;">
                    <i class="fa fa-times tw-mr-1"></i><?= _l('reject'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
function approveConfirm(url, name) {
    $('#approve-confirm-name').text(name);
    $('#approve-confirm-btn').attr('href', url);
    $('#approve-confirm-modal').modal({ show: true, backdrop: 'static', keyboard: false });
}
function rejectConfirm(url, name) {
    $('#reject-confirm-name').text(name);
    $('#reject-confirm-btn').attr('href', url);
    $('#reject-confirm-modal').modal({ show: true, backdrop: 'static', keyboard: false });
}
</script>
