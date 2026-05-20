<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="no-margin"><?= _l('my_associations'); ?></h4>
                                <hr />
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-associations">
                                <thead>
                                    <tr>
                                        <th><?= _l('association'); ?></th>
                                        <th><?= _l('client_phonenumber'); ?></th>
                                        <th><?= _l('city'); ?></th>
                                        <th><?= _l('status'); ?></th>
                                        <th><?= _l('date_registered'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($my_memberships as $row) { ?>
                                    <tr>
                                        <td>
                                            <a href="<?= admin_url('associations/list_associations/' . (int) $row->association_id); ?>">
                                                <?= e($row->association_name ?: '—'); ?>
                                            </a>
                                        </td>
                                        <td><?= e($row->phonenumber); ?></td>
                                        <td><?= e($row->city); ?></td>
                                        <td>
                                            <?php
                                            $status_map = [
                                                'pending'  => ['warning', 'pending'],
                                                'active'   => ['success', 'active'],
                                                'rejected' => ['danger',  'rejected'],
                                            ];
                                            $s = $status_map[$row->status] ?? ['default', $row->status];
                                            echo '<span class="label label-' . $s[0] . '">' . _l('customer_status_' . $s[1]) . '</span>';
                                            ?>
                                        </td>
                                        <td><?= !empty($row->date_registered) ? _dt($row->date_registered) : '—'; ?></td>
                                    </tr>
                                <?php } ?>
                                <?php if (empty($my_memberships)) { ?>
                                    <tr>
                                        <td colspan="5" class="text-center">—</td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
