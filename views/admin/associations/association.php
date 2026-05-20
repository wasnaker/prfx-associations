<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php
            echo form_open_multipart(admin_url('associations/add_edit_association'), ['id' => 'association-form', 'class' => '_transaction_form association-form']);
            if (isset($association)) {
                echo form_hidden('isedit');
            }
            ?>
            <div class="col-md-12">
                <h4
                    class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700 tw-flex tw-items-center tw-space-x-2">
                    <span>
                        <?php echo e(isset($association) ? $association->company : _l('create_new_association')); ?>
                    </span>
                    <?php if (isset($association)) {
                        echo format_association_status($association->active == 1 ? 'active' : 'inactive');
                    } ?>
                </h4>
                <?php $this->load->view('admin/associations/association_template'); ?>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    validate_association_form();
    init_ajax_search('association_surveyor', '#surveyor_id.ajax-search');
    init_ajax_search('association_equipment', '#equipment_select.ajax-search');
    init_association_equipment_select();
    if (typeof association_sync_preset_options === 'function') { association_sync_preset_options(); }

    // Province / City cascading selects
    var _provinces_cache = null;

    function get_provinces(callback) {
        if (_provinces_cache) { callback(_provinces_cache); return; }
        $.get(admin_url + 'regions/get_provinces', function(data) {
            _provinces_cache = data; callback(data);
        }, 'json');
    }

    function load_cities(city_sel_id, province_id, selected_city) {
        var $csel = $(city_sel_id);
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

    function init_region_pair(prov_sel_id, city_sel_id) {
        var selected_prov = $(prov_sel_id).data('selected') || '';
        var selected_city = $(city_sel_id).data('selected') || '';
        get_provinces(function(provinces) {
            var $psel = $(prov_sel_id);
            $psel.html('<option value=""></option>');
            $.each(provinces, function(i, p) {
                var opt = $('<option>').val(p.name).text(p.name);
                if (p.name === selected_prov) opt.prop('selected', true);
                $psel.append(opt);
            });
            $psel.selectpicker('refresh');
            if (selected_prov) {
                var prov = $.grep(provinces, function(p) { return p.name === selected_prov; })[0];
                if (prov) load_cities(city_sel_id, prov.id, selected_city);
            }
        });
    }

    function on_province_change(prov_sel_id, city_sel_id) {
        $(document).on('change', prov_sel_id, function() {
            var name = $(this).val();
            get_provinces(function(provinces) {
                var prov = $.grep(provinces, function(p) { return p.name === name; })[0];
                load_cities(city_sel_id, prov ? prov.id : null, '');
            });
        });
    }

    init_region_pair('#province-select', '#city-select');
    on_province_change('#province-select', '#city-select');

    // Billing & Shipping region selects
    init_region_pair('#billing-province-select', '#billing-city-select');
    on_province_change('#billing-province-select', '#billing-city-select');
    init_region_pair('#shipping-province-select', '#shipping-city-select');
    on_province_change('#shipping-province-select', '#shipping-city-select');

    // "Same as profile" → copy to billing
    $('.billing-same-as-association').on('click', function(e) {
        e.preventDefault();
        $('textarea[name="billing_street"]').val($('textarea[name="address"]').val());
        $('input[name="billing_zip"]').val($('input[name="zip"]').val());
        var state = $('select[name="state"]').val();
        var city  = $('select[name="city"]').val();
        if (state) { $('#billing-province-select').selectpicker('val', state).trigger('change'); }
        setTimeout(function() { if (city) { $('#billing-city-select').selectpicker('val', city); } }, 600);
    });

    // "Copy billing to shipping"
    $('.association-copy-billing-address').on('click', function(e) {
        e.preventDefault();
        $('textarea[name="shipping_street"]').val($('textarea[name="billing_street"]').val());
        $('input[name="shipping_zip"]').val($('input[name="billing_zip"]').val());
        var state = $('#billing-province-select').val();
        var city  = $('#billing-city-select').val();
        if (state) { $('#shipping-province-select').selectpicker('val', state).trigger('change'); }
        setTimeout(function() { if (city) { $('#shipping-city-select').selectpicker('val', city); } }, 600);
    });
});
</script>
</body>

</html>
