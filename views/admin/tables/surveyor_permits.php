<?php defined('BASEPATH') or exit('No direct script access allowed');

return App_table::find('association_surveyor_permits')
    ->outputUsing(function ($params) {
        $_me            = get_staff(get_staff_user_id());
        $owner_assoc_id = ($_me && $_me->client_type === 'association' && $_me->client_id)
            ? (int) $_me->client_id : null;

        $aColumns = ['c.company', 'ig.name', 'sp.number'];
        $join     = [
            'LEFT JOIN ' . db_prefix() . 'clients c       ON c.userid  = sp.surveyor_id',
            'LEFT JOIN ' . db_prefix() . 'items_groups ig ON ig.id     = sp.groupid',
        ];
        $where = ["AND sp.status = 'pending'"];

        $CI = &get_instance();
        $filter_id   = (int) $CI->input->post('association_id');
        $assoc_filter = $owner_assoc_id ?: $filter_id;

        if ($assoc_filter) {
            $join[] = 'INNER JOIN ' . db_prefix() . 'surveyors_associations sa'
                . ' ON sa.association_id = ' . $assoc_filter
                . " AND sa.status = 'active'"
                . ' AND sa.surveyor_id IN ('
                .     'SELECT c2.userid FROM ' . db_prefix() . 'clients c2'
                .     ' WHERE c2.userid = sp.surveyor_id OR c2.company_id = sp.surveyor_id'
                . ')';
        }

        $where = hooks()->apply_filters('association_surveyor_permits_table_where', $where);

        $result  = data_tables_init($aColumns, 'sp.id', db_prefix() . 'surveyor_permits sp',
            $join, $where, ['sp.id as permit_id'], 'GROUP BY sp.id');
        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row = [];
            $pid = (int) $aRow['permit_id'];
            $row[] = '<a href="' . admin_url('associations/list_surveyor_permits/' . $pid) . '"'
                   . ' onclick="init_surveyor_permit(' . $pid . '); return false;"'
                   . ' class="tw-font-medium text-muted">' . e($aRow['company'] ?? '—') . '</a>';
            $row[] = e($aRow['name']   ?? '');
            $row[] = e($aRow['number'] ?? '');
            $row['DT_RowClass'] = 'has-row-options';
            $output['aaData'][] = $row;
        }

        return $output;
    });
