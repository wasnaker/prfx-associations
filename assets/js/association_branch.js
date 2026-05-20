/* Association Branch JS */

var _branch_provinces_cache = null;

reload_associations_tables = function() {
    ['.table-associations', '.table-cust-branches'].forEach(function(sel) {
        $.fn.DataTable.isDataTable(sel) && $(sel).DataTable().ajax.reload(null, false);
    });
};

function _branch_get_provinces(callback) {
    if (_branch_provinces_cache) { callback(_branch_provinces_cache); return; }
    $.get(admin_url + 'regions/get_provinces', function(data) {
        _branch_provinces_cache = data; callback(data);
    }, 'json');
}

function _branch_load_cities(province_id, selected_city) {
    var $csel = $('#branch_city');
    $csel.html('<option value=""></option>').selectpicker('refresh');
    if (!province_id) return;
    $.get(admin_url + 'regions/get_cities/' + province_id, function(cities) {
        $.each(cities, function(i, c) {
            var opt = $('<option>').val(c.name).text(c.name);
            if (selected_city && c.name === selected_city) opt.prop('selected', true);
            $csel.append(opt);
        });
        $csel.selectpicker('refresh');
    }, 'json');
}

function _branch_init_province_select(selected_prov, selected_city) {
    _branch_get_provinces(function(provinces) {
        var $psel = $('#branch_state');
        $psel.html('<option value=""></option>');
        $.each(provinces, function(i, p) {
            var opt = $('<option>').val(p.name).text(p.name);
            if (p.name === selected_prov) opt.prop('selected', true);
            $psel.append(opt);
        });
        $psel.selectpicker('refresh');
        if (selected_prov) {
            var prov = $.grep(provinces, function(p) { return p.name === selected_prov; })[0];
            if (prov) _branch_load_cities(prov.id, selected_city);
        }
    });
}

function init_association_branch() {
    $(document).on('change', '#branch_use_vat', function() {
        var checked = $(this).prop('checked');
        $('#branch_nitku').val('').prop('disabled', checked);
        $('#branch_nitku')[checked ? 'hide' : 'show']();
    });

    $(document).on('change', '#branch_state', function() {
        var name = $(this).val();
        _branch_get_provinces(function(provinces) {
            var prov = $.grep(provinces, function(p) { return p.name === name; })[0];
            _branch_load_cities(prov ? prov.id : null, '');
        });
    });

    $(document).on('click', '#branch-save-btn', function() {
        var lang      = window.associationBranchLang || {};
        var company   = $.trim($('#branch_company').val());
        var branch_id = $('#branch_id').val();
        if (!company) { alert_float('danger', (lang.branch_name || 'Branch Name') + ' is required.'); return; }
        if (!branch_id) {
            var fname = $.trim($('#branch_admin_firstname').val());
            var lname = $.trim($('#branch_admin_lastname').val());
            var email = $.trim($('#branch_admin_email').val());
            var pass1 = $('#branch_admin_password').val();
            var pass2 = $('#branch_admin_password2').val();
            if (!fname || !lname || !email || !pass1) { alert_float('danger', (lang.primary_contact_information || 'Primary Contact Information') + ' is required.'); return; }
            if (pass1 !== pass2) { alert_float('danger', lang.passwords_not_match || 'Passwords do not match'); return; }
        }
        $.post(admin_url + 'associations/association_branch/save_branch', {
            parent_id: $('#branch_parent_id').val(), branch_id: branch_id,
            company: company, use_vat: $('#branch_use_vat').prop('checked') ? 1 : 0,
            nitku: $('#branch_use_vat').prop('checked') ? '' : $('#branch_nitku').val(),
            phonenumber: $('#branch_phonenumber').val(), state: $('#branch_state').val(),
            city: $('#branch_city').val(), zip: $('#branch_zip').val(),
            address: $('#branch_address').val(), admin_firstname: $('#branch_admin_firstname').val(),
            admin_lastname: $('#branch_admin_lastname').val(), admin_email: $('#branch_admin_email').val(),
            admin_password: $('#branch_admin_password').val(),
        }).done(function(response) {
            response = JSON.parse(response);
            if (response.success) {
                alert_float('success', response.message);
                $('#branch-modal').modal('hide');
                reload_associations_tables();
            } else { alert_float('danger', response.message); }
        }).fail(function() { alert_float('danger', 'Error saving branch.'); });
    });
}

function open_branch_form(parent_id, branch_id) {
    var lang = window.associationBranchLang || {};
    branch_id = branch_id || '';
    $('#branch_id').val(branch_id);
    $('#branch_parent_id').val(parent_id);
    if (branch_id) {
        $('#branch-modal-title').text(lang.edit_branch || 'Edit Branch');
        $('#branch-admin-section').hide();
        $.getJSON(admin_url + 'associations/association_branch/get_branch_data/' + branch_id, function(data) {
            $('#branch_company').val(data.company || '');
            $('#branch_phonenumber').val(data.phonenumber || '');
            $('#branch_zip').val(data.zip || '');
            $('#branch_address').val(data.address || '');
            var useVat = data.use_vat == 1;
            $('#branch_use_vat').prop('checked', useVat).trigger('change');
            if (!useVat) { $('#branch_nitku').val(data.nitku || ''); }
            _branch_init_province_select(data.state || '', data.city || '');
        });
    } else {
        $('#branch-modal-title').text(lang.new_association_branch || 'New Branch');
        $('#branch_company, #branch_nitku, #branch_phonenumber, #branch_zip, #branch_address').val('');
        $('#branch_admin_firstname, #branch_admin_lastname, #branch_admin_email, #branch_admin_password, #branch_admin_password2').val('');
        $('#branch_use_vat').prop('checked', false).trigger('change');
        $('#branch-admin-section').show();
        _branch_init_province_select('', '');
    }
    $('#branch-modal').modal({show: true, backdrop: 'static'});
}

function delete_branch(id) {
    var lang = window.associationBranchLang || {};
    if (!confirm(lang.confirm_action_prompt || 'Are you sure?')) { return; }
    $.post(admin_url + 'associations/association_branch/delete_branch/' + id).done(function(response) {
        response = JSON.parse(response);
        if (response.success) { alert_float('success', response.message); reload_associations_tables(); }
        else { alert_float('danger', response.message); }
    }).fail(function() { alert_float('danger', 'Error deleting branch.'); });
}
