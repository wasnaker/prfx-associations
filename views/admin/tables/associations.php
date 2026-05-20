<?php

defined('BASEPATH') or exit('No direct script access allowed');

return App_table::find('associations')
    ->outputUsing(function ($params) {
        $_me             = get_staff(get_staff_user_id());
        $owner_client_id = ($_me && $_me->client_type === 'association' && $_me->client_id)
            ? (int) $_me->client_id : null;

        $aColumns = [
            get_sql_select_client_company(),
            'phonenumber',
            'state',
            'city',
            'active',
        ];

        $sIndexColumn = 'userid';
        $sTable       = db_prefix() . 'clients';
        $join         = [];
        $where        = ['AND ' . db_prefix() . 'clients.client_type = "association"'];

        if ($_me && $_me->client_type === 'association' && $owner_client_id) {
            $cap     = staff_can('edit', 'associations') ? 'edit' : 'view';
            $where[] = 'AND ' . entity_scope_where($_me, db_prefix() . 'clients.userid', 'association', 'associations', $cap);
        }

        if ($filtersWhere = $this->getWhereFromRules()) {
            $where[] = $filtersWhere;
        }

        $aColumns = hooks()->apply_filters('associations_table_sql_columns', $aColumns);
        $join     = hooks()->apply_filters('associations_table_sql_join',    $join);
        $where    = hooks()->apply_filters('associations_table_sql_where',   $where);

        $result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['userid', 'company_id']);
        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row  = [];
            $name = !empty($aRow['company']) ? $aRow['company'] : '—';
            $uid  = $aRow['userid'];

            $row[] = '<a href="' . admin_url('associations/list_associations/' . $uid) . '"'
                . ' onclick="init_association(' . $uid . '); return false;"'
                . ' class="tw-font-medium">' . e($name) . '</a>'
                . '<div class="row-options">'
                . (staff_can('edit', 'associations') || $owner_client_id
                    ? '<a href="' . admin_url('associations/association/' . $uid) . '">' . _l('edit') . '</a> | ' : '')
                . (staff_can('delete', 'associations')
                    ? '<a href="' . admin_url('associations/delete/' . $uid) . '" class="_delete">' . _l('delete') . '</a>' : '')
                . '</div>';

            $row[] = e($aRow['phonenumber'] ?? '');
            $row[] = e($aRow['state']       ?? '');
            $row[] = e($aRow['city']        ?? '');
            $row[] = $aRow['active'] == 1
                ? '<span class="label label-success">' . _l('active') . '</span>'
                : '<span class="label label-danger">' . _l('inactive') . '</span>';

            $row = hooks()->apply_filters('associations_table_row_data', $row, $aRow);

            $row['DT_RowClass'] = 'has-row-options';
            $row['DT_RowData']  = ['id' => $uid];
            $output['aaData'][] = $row;
        }

        return $output;
    })->setRules([
        App_table_filter::new('active', 'BooleanRule')
            ->label(_l('active'))
            ->raw(fn ($v) => 'active = ' . (int) $v),
    ]);
