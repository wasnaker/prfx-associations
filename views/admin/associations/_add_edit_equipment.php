<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel-body" id="association-equipment-section">
    <div class="row mbot10">
        <div class="col-md-4">
            <div class="form-group select-placeholder">
                <label class="control-label"><?php echo _l('association_add_equipment'); ?></label>
                <select id="equipment_select" data-live-search="true" data-width="100%"
                    class="ajax-search"
                    data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                    <option value=""></option>
                </select>
            </div>
        </div>
    </div>

    <div class="table-responsive s_table">
        <table class="table no-mtop" id="association-equipment-table">
            <thead>
                <tr>
                    <th width="25%"><?php echo _l('association_equipment_item_name'); ?></th>
                    <th width="15%"><?php echo _l('association_equipment_unit_code'); ?></th>
                    <th width="15%"><?php echo _l('association_equipment_serial_number'); ?></th>
                    <th width="20%"><?php echo _l('association_equipment_location'); ?></th>
                    <th width="15%"><?php echo _l('association_equipment_cert_expired'); ?></th>
                    <th width="10%" align="center"><i class="fa fa-cog"></i></th>
                </tr>
            </thead>
            <tbody id="association-equipment-tbody">
                <?php
                // Rows from saved ASSOCIATION (edit mode)
                $eq_rows = [];
                if (isset($association) && !empty($association->equipment)) {
                    $eq_rows = $association->equipment;
                } elseif (!empty($preset_equipment)) {
                    // Rows from bulk-select (copy-estimate pattern)
                    foreach ($preset_equipment as $pe) {
                        $eq_rows[] = [
                            'association_equipment_id' => $pe['id'],
                            'item_name'             => $pe['item_name'],
                            'unit_code'             => $pe['unit_code'],
                            'serial_number'         => $pe['serial_number'] ?? '',
                            'location'              => $pe['location'],
                            'cert_expired_date'     => $pe['cert_expired_date'] ?? '',
                        ];
                    }
                }
                foreach ($eq_rows as $i => $eq):
                ?>
                <tr class="association-equipment-row" data-id="<?php echo $eq['association_equipment_id']; ?>">
                    <td><?php echo e($eq['item_name']); ?>
                        <input type="hidden" name="equipment[<?php echo $i; ?>][association_equipment_id]" value="<?php echo $eq['association_equipment_id']; ?>">
                    </td>
                    <td><?php echo e($eq['unit_code']); ?></td>
                    <td><?php echo e($eq['serial_number']); ?></td>
                    <td><?php echo e($eq['location']); ?></td>
                    <td><?php echo e($eq['cert_expired_date']); ?></td>
                    <td>
                        <a href="#" class="btn btn-danger btn-sm association-equipment-remove" data-id="<?php echo $eq['association_equipment_id']; ?>">
                            <i class="fa fa-times"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if (!empty($eq_rows)): ?>
<script>
function association_sync_preset_options() {
    var $sel = $('#equipment_select');
    if (!$sel.length) { return; }
    <?php foreach ($eq_rows as $eq): ?>
    if (!$sel.find('option[value="<?= (int)$eq['association_equipment_id']; ?>"]').length) {
        $sel.append(new Option(
            <?= json_encode(($eq['item_name'] ?? '') . ' (' . ($eq['unit_code'] ?? '') . ')'); ?>,
            <?= (int)$eq['association_equipment_id']; ?>,
            false, false
        ));
    }
    <?php endforeach; ?>
    $sel.selectpicker('refresh');
}
</script>
<?php endif; ?>
