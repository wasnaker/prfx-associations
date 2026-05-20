/* Associations Module JS */

function validate_association_form(selector) {
    selector = typeof selector == 'undefined' ? '#association-form' : selector;

    appValidateForm($(selector), {
        date:   'required',
        number: { required: true },
    });

    $('body').find('input[name="number"]').rules('add', {
        remote: {
            url:  admin_url + 'associations/validate_association_number',
            type: 'post',
            data: {
                number: function() {
                    return $('input[name="number"]').val();
                },
                isedit: function() {
                    return $('input[name="number"]').data('isedit');
                },
                original_number: function() {
                    return $('input[name="number"]').data('original-number');
                },
                date: function() {
                    return $('body').find('.association-form input[name="date"]').val();
                },
            },
        },
        messages: {
            remote: app.lang.estimate_number_exists,
        },
    });
}

function association_mark_status(e, id, status) {
    e.preventDefault();
    $.post(admin_url + 'associations/mark_action_status/' + status + '/' + id, function(resp) {
        if (resp.success) {
            alert_float('success', resp.message);
            $('#association-status-badge').html(resp.status_html);
            $('li[data-mark-status]').show();
            $('li[data-mark-status="' + status + '"]').hide();
            if ($.fn.DataTable.isDataTable('#associations')) {
                $('#associations').DataTable().ajax.reload(null, false);
            }
        } else {
            alert_float('danger', resp.message);
        }
    }, 'json');
}

function association_pipeline() {
    init_kanban(
        'associations/get_pipeline',
        associations_pipeline_update,
        '.pipeline-status',
        290,
        360
    );
}

function associations_pipeline_sort(type) {
    kan_ban_sort(type, association_pipeline);
}

function associations_pipeline_update(ui, object) {
    if (object === ui.item.parent()[0]) {
        var data = {
            associationid: $(ui.item).attr('data-association-id'),
            status: $(ui.item.parent()[0]).attr('data-status-id'),
            order: [],
        };

        $.each($(ui.item).parents('.pipeline-status').find('li'), function(idx, el) {
            var id = $(el).attr('data-association-id');
            if (id) {
                data.order.push([id, idx + 1]);
            }
        });

        check_kanban_empty_col('[data-association-id]');

        setTimeout(function() {
            $.post(admin_url + 'associations/update_pipeline', data).done(function(response) {
                update_kan_ban_total_when_moving(ui, data.status);
                association_pipeline();
            });
        }, 500);
    }
}

function association_pipeline_open(id) {
    if (id === '') {
        return;
    }
    requestGet('associations/pipeline_open/' + id).done(function(response) {
        var visible = $('.association-pipeline:visible').length > 0;
        $('#association').html(response);
        if (!visible) {
            $('.association-pipeline').modal({
                show: true,
                backdrop: 'static',
                keyboard: false,
            });
        } else {
            $('#association')
                .find('.modal.association-pipeline')
                .removeClass('fade')
                .addClass('in')
                .css('display', 'block');
        }
    });
}

function init_association_notes() {
    $("body").off("submit", "#association-notes").on("submit", "#association-notes", function () {
        var form = $(this);
        var description = form.find('textarea[name="description"]').val().trim();
        if (description === "") {
            return false;
        }
        $.post(form.attr("action"), form.serialize()).done(function (rel_id) {
            form.find('textarea[name="description"]').val("");
            get_sales_notes(rel_id, "associations");
        });
        return false;
    });
}

function init_association(id) {
    load_small_table_item(
        id,
        "#association",
        "associationid",
        "associations/get_association_data_ajax",
        ".table-associations"
    );
}

function init_association_equipment_select() {
    var $sel = $('#equipment_select');
    if (!$sel.length) { return; }

    $sel.on('change', function() {
        var id = $(this).val();
        if (!id) { return; }

        $.getJSON(admin_url + 'associations/get_equipment_data/' + id, function(eq) {
            if (!eq || !eq.id) { return; }

            // Prevent duplicate
            if ($('#association-equipment-tbody tr[data-id="' + eq.id + '"]').length) {
                alert_float('warning', 'Equipment already added.');
                $sel.selectpicker('val', '');
                return;
            }

            var idx = $('#association-equipment-tbody tr').length;
            var row = '<tr class="association-equipment-row" data-id="' + eq.id + '">'
                + '<td>' + $('<span>').text(eq.item_name).html()
                + '<input type="hidden" name="equipment[' + idx + '][association_equipment_id]" value="' + eq.id + '"></td>'
                + '<td>' + $('<span>').text(eq.unit_code).html() + '</td>'
                + '<td>' + $('<span>').text(eq.serial_number || '').html() + '</td>'
                + '<td>' + $('<span>').text(eq.location || '').html() + '</td>'
                + '<td>' + $('<span>').text(eq.cert_expired_date || '').html() + '</td>'
                + '<td><a href="#" class="btn btn-danger btn-sm association-equipment-remove" data-id="' + eq.id + '">'
                + '<i class="fa fa-times"></i></a></td>'
                + '</tr>';

            $('#association-equipment-tbody').append(row);
            $sel.selectpicker('val', '');
        });
    });

    $(document).off('click', '.association-equipment-remove').on('click', '.association-equipment-remove', function(e) {
        e.preventDefault();
        $(this).closest('tr').remove();
        // Re-index hidden inputs
        $('#association-equipment-tbody tr').each(function(i) {
            $(this).find('input[type="hidden"]').attr('name', 'equipment[' + i + '][association_equipment_id]');
        });
    });
}

function init_associations_total(manual) {
    if ($("#associations_total").length === 0) {
        return;
    }
    var _quo_total_href_manual = $(".associations-total");
    if (
        $("body").hasClass("associations-total-manual") &&
        typeof manual == "undefined" &&
        !_quo_total_href_manual.hasClass("initialized")
    ) {
        return;
    }
    _quo_total_href_manual.addClass("initialized");
    var currency = $("body").find('select[name="total_currency"]').val();
    var _years = $("body")
        .find('select[name="associations_total_years"]')
        .selectpicker("val");
    var years = [];
    $.each(_years, function (i, _y) {
        if (_y !== "") {
            years.push(_y);
        }
    });

    var association_id = "";
    var project_id = "";

    var _association_id = $('.association_profile input[name="userid"]').val();
    var _project_id = $('input[name="project_id"]').val();
    if (typeof _association_id != "undefined") {
        association_id = _association_id;
    } else if (typeof _project_id != "undefined") {
        project_id = _project_id;
    }

    $.post(admin_url + "associations/get_associations_total", {
        currency: currency,
        init_total: true,
        years: years,
        association_id: association_id,
        project_id: project_id,
    }).done(function (response) {
        $("#associations_total").html(response);
    });
}

function _assoc_reload_after_action() {
    reload_surveyors_tables();
    // Reload surveyor registrations table jika sedang terbuka
    if ($.fn.DataTable.isDataTable('.table-association-surveyor-registration-list')) {
        $('.table-association-surveyor-registration-list').DataTable().ajax.reload(null, false);
    }
    // Tutup right panel registrations jika terbuka
    $('#association-surveyor-registration-right-panel').addClass('hide').html('');
    $('input[name="surveyor_registration_id"]').val('');
}

function assoc_approve_member(surveyor_id, association_id) {
    $.post(admin_url + 'associations/mark_member_status/approve/' + surveyor_id, {association_id: association_id}, function (resp) {
        if (resp.success) {
            alert_float('success', resp.message || '');
            window.location.href = admin_url + 'associations/list_surveyor_registrations';
        } else {
            alert_float('danger', resp.message);
        }
    }, 'json');
}

function assoc_reject_member(surveyor_id, association_id, surveyor_name) {
    var title = surveyor_name ? surveyor_name : '';
    var modal = $('<div class="modal fade" tabindex="-1" role="dialog">'
        + '<div class="modal-dialog" role="document"><div class="modal-content">'
        + '<div class="modal-header">'
        + '<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>'
        + '<h4 class="modal-title">' + title + '</h4></div>'
        + '<div class="modal-body">'
        + '<div class="form-group"><label class="control-label">Rejection Reason <span class="text-danger">*</span></label>'
        + '<textarea id="reject_reason_input" class="form-control" rows="4" placeholder="Explain why this registration is rejected..."></textarea>'
        + '</div></div>'
        + '<div class="modal-footer">'
        + '<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>'
        + '<button type="button" class="btn btn-danger" id="confirm_reject_btn">Reject</button>'
        + '</div></div></div></div>');

    $('body').append(modal);
    modal.modal('show');

    modal.find('#confirm_reject_btn').on('click', function () {
        var reason = modal.find('#reject_reason_input').val().trim();
        if (!reason) { modal.find('#reject_reason_input').focus(); return; }
        $.post(
            admin_url + 'associations/mark_member_status/reject/' + surveyor_id,
            { reason: reason, association_id: association_id },
            function (resp) {
                modal.modal('hide');
                if (resp.success) {
                    alert_float('success', resp.message || '');
                    window.location.href = admin_url + 'associations/list_surveyor_registrations';
                } else {
                    alert_float('danger', resp.message);
                }
            }, 'json'
        );
    });

    modal.on('hidden.bs.modal', function () { modal.remove(); });
}

function assoc_mark_pending(surveyor_id, association_id) {
    $('#assoc-mark-pending-modal').modal('hide');
    $.post(
        admin_url + 'associations/mark_member_status/pending/' + surveyor_id,
        { association_id: association_id },
        function (resp) {
            if (resp.success) {
                alert_float('success', resp.message || '');
                window.location.href = admin_url + 'surveyors';
            } else {
                alert_float('danger', resp.message);
            }
        }, 'json'
    );
}

function assoc_mark_pending_confirm(surveyor_id, association_id) {
    var modal = $('<div class="modal fade" tabindex="-1" role="dialog">'
        + '<div class="modal-dialog" role="document"><div class="modal-content">'
        + '<div class="modal-header">'
        + '<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>'
        + '<h4 class="modal-title">' + _l('mark_as_pending') + '</h4></div>'
        + '<div class="modal-body"><p>' + _l('confirm_mark_as_pending_desc') + '</p></div>'
        + '<div class="modal-footer">'
        + '<button type="button" class="btn btn-default" data-dismiss="modal">' + _l('cancel') + '</button>'
        + '<button type="button" class="btn btn-warning" id="confirm_pending_btn">' + _l('mark_as_pending') + '</button>'
        + '</div></div></div></div>');

    $('body').append(modal);
    modal.modal('show');

    modal.find('#confirm_pending_btn').on('click', function () {
        $.post(
            admin_url + 'associations/mark_member_status/pending/' + surveyor_id,
            { association_id: association_id },
            function (resp) {
                modal.modal('hide');
                if (resp.success) {
                    alert_float('success', resp.message || '');
                    if ($.fn.DataTable.isDataTable('.table-surveyors')) {
                        $('.table-surveyors').DataTable().draw();
                    }
                } else {
                    alert_float('danger', resp.message);
                }
            }, 'json'
        );
    });

    modal.on('hidden.bs.modal', function () { modal.remove(); });
}

function register_to_association(association_id) {
    $.post(admin_url + 'associations/register_to_association/' + association_id, {})
        .done(function (response) {
            var data = JSON.parse(response);
            if (data.success) {
                alert_float('success', data.message);
                init_association(association_id);
            } else {
                alert_float('danger', data.message);
            }
        });
}

function init_surveyor_registration(id) {
    load_small_table_item(
        id,
        '#association-surveyor-registration-right-panel',
        'surveyor_registration_id',
        'associations/get_surveyor_registration_data_ajax',
        '.table-association-surveyor-registration-list'
    );
}

function init_surveyor_permit(id) {
    load_small_table_item(
        id,
        '#association-surveyor-permit-right-panel',
        'surveyor_permit_id',
        'associations/get_surveyor_permit_data_ajax',
        '.table-association-surveyor-permit-list'
    );
}
