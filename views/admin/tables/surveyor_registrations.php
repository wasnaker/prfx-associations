<?php defined('BASEPATH') or exit('No direct script access allowed');

return App_table::find('association_surveyor_registrations')
    ->outputUsing(function ($params) {
        $CI = &get_instance();
        $_me           = get_staff(get_staff_user_id());
        $owner_assoc_id = ($_me && $_me->client_type === 'association' && $_me->client_id)
            ? (int) $_me->client_id : null;

        $aColumns = ['c.company', 'c.state', 'c.city'];
        $where    = ["AND sa.status = 'pending'"];

        $filter_id = (int) $CI->input->post('association_id');
        if ($owner_assoc_id) {
            $where[] = 'AND sa.association_id = ' . $owner_assoc_id;
        } elseif ($filter_id) {
            $where[] = 'AND sa.association_id = ' . $filter_id;
        }

        $where = hooks()->apply_filters('association_surveyor_registrations_table_where', $where);

        $result  = data_tables_init(
            $aColumns, 'sa.id',
            db_prefix() . 'surveyors_associations sa',
            ['LEFT JOIN ' . db_prefix() . 'clients c ON c.userid = sa.surveyor_id'],
            $where,
            ['sa.id as sa_id', 'sa.association_id']
        );
        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row   = [];
            $sa_id = (int) $aRow['sa_id'];
            $row[] = '<a href="' . admin_url('associations/list_surveyor_registrations/' . $sa_id) . '"'
                   . ' onclick="init_surveyor_registration(' . $sa_id . '); return false;"'
                   . ' class="tw-font-medium text-muted">' . e($aRow['company'] ?? '—') . '</a>';
            $row[] = e($aRow['state'] ?? '');
            $row[] = e($aRow['city']  ?? '');
            $row['DT_RowClass'] = 'has-row-options';
            $output['aaData'][] = $row;
        }

        return $output;
    });
