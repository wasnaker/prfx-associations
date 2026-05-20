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
                    <tr>
                        <td class="tw-w-1/3 text-muted"><?= _l('association_name'); ?></td>
                        <td>
                            <a href="<?= admin_url('associations/list_associations/' . (int)$association->userid); ?>">
                                <?= e($association->company); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?= _l('client_state'); ?></td>
                        <td><?= e($surveyor->state ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?= _l('client_city'); ?></td>
                        <td><?= e($surveyor->city ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?= _l('assoc_date_registered'); ?></td>
                        <td><?= !empty($sa->date_registered) ? _dt($sa->date_registered) : '—'; ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?= _l('assoc_sp_status'); ?></td>
                        <td><span class="label label-warning"><?= _l('surv_assoc_status_pending'); ?></span></td>
                    </tr>
                </tbody>
            </table>

            <hr class="tw-my-4" />

            <?php
            $_reg_doc_types = [
                'nib'               => ['label' => _l('doc_type_nib'),            'indent' => false],
                'npwp'              => ['label' => _l('doc_type_npwp'),           'indent' => false],
                'akte_pendirian'    => ['label' => _l('doc_type_akte_pendirian'), 'indent' => false, 'has_notary' => true],
                'akte_pendirian_sk' => ['label' => _l('doc_sk_kemenkumham'),      'indent' => true],
                'akte_perubahan'    => ['label' => _l('doc_type_akte_perubahan'), 'indent' => false, 'has_notary' => true],
                'akte_perubahan_sk' => ['label' => _l('doc_sk_kemenkumham'),      'indent' => true],
                'bpjs_tk'           => ['label' => _l('doc_type_bpjs_tk'),        'indent' => false],
                'bpjs_kes'          => ['label' => _l('doc_type_bpjs_kes'),       'indent' => false],
            ];
            $legal_docs   = $legal_docs ?? [];
            $_docs_ok     = true;
            foreach ($_reg_doc_types as $_dt => $_cfg) {
                $_d = $legal_docs[$_dt] ?? null;
                if (empty($_d->doc_number) || empty($_d->file)) { $_docs_ok = false; }
            }
            ?>

            <p class="tw-font-semibold tw-text-sm tw-text-neutral-700 tw-mb-2">
                <?= _l('legal_documents'); ?>
                <?php if ($_docs_ok): ?>
                <span class="label label-success tw-ml-1"><?= _l('complete'); ?></span>
                <?php else: ?>
                <span class="label label-danger tw-ml-1"><?= _l('incomplete'); ?></span>
                <?php endif; ?>
            </p>

            <table class="table table-condensed no-margin tw-mb-3">
                <tbody>
                    <?php foreach ($_reg_doc_types as $_dt => $_cfg):
                        $_doc    = $legal_docs[$_dt] ?? null;
                        $_has_no = !empty($_doc->doc_number);
                        $_has_fi = !empty($_doc->file);
                        $_meta   = (!empty($_doc->meta) && !empty($_cfg['has_notary']))
                            ? json_decode($_doc->meta, true) : [];
                    ?>
                    <tr<?= !empty($_cfg['indent']) ? ' class="tw-bg-neutral-50"' : ''; ?>>
                        <td class="tw-w-2/5 text-muted tw-text-xs tw-align-top<?= !empty($_cfg['indent']) ? ' tw-pl-6' : ''; ?>">
                            <?= !empty($_cfg['indent']) ? '<i class="fa fa-level-up fa-rotate-90 tw-mr-1 tw-text-neutral-400"></i>' : ''; ?>
                            <?= $_cfg['label']; ?>
                        </td>
                        <td class="tw-text-xs tw-align-top">
                            <?= $_has_no ? e($_doc->doc_number) : '<span class="text-muted">—</span>'; ?>
                            <?php if (!empty($_meta['notary_name'])): ?>
                                <div class="text-muted tw-mt-0.5"><?= _l('doc_notary_name'); ?>: <?= e($_meta['notary_name']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="tw-text-center tw-align-top" style="width:60px">
                            <?php if ($_has_fi): ?>
                                <a href="<?= admin_url('surveyors/download_legal_doc/' . (int)$surveyor->userid . '/' . $_dt); ?>"
                                    class="btn btn-xs btn-default" target="_blank">
                                    <i class="fa fa-download"></i>
                                </a>
                            <?php else: ?>
                                <i class="fa fa-times-circle text-danger" data-toggle="tooltip" title="<?= _l('missing'); ?>"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (staff_can('approve_surveyor_registration', 'associations') || staff_can('mark_as', 'associations')) { ?>
            <hr class="tw-my-4" />
            <div class="tw-flex tw-gap-2">
                <button type="button" class="btn btn-success"
                    onclick="assoc_approve_member(<?= (int)$sa->surveyor_id; ?>, <?= (int)$sa->association_id; ?>); return false;">
                    <i class="fa fa-check fa-fw"></i> <?= _l('assoc_sp_approve'); ?>
                </button>
                <button type="button" class="btn btn-danger"
                    onclick="assoc_reject_member(<?= (int)$sa->surveyor_id; ?>, <?= (int)$sa->association_id; ?>, '<?= addslashes(e($surveyor->company ?? '')); ?>'); return false;">
                    <i class="fa fa-times fa-fw"></i> <?= _l('assoc_sr_reject'); ?>
                </button>
            </div>
            <?php } ?>

        </div>
    </div>
</div>

