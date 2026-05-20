<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="panel-table-full">
                <div id="vueApp">
                    <div class="col-md-12 tw-mb-3">
                        <h4 class="tw-my-0 tw-font-bold tw-text-xl"><?= _l('associations'); ?></h4>
                        <a href="#" 
							class="associations-total tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700"
							onclick="slideToggle('#stats-top'); init_associations_total(true); return false;">
								<?= _l('view_financial_stats'); ?>
						</a>
                    </div>                  
                    <div class="col-md-12">
                        <?php $this->load->view('admin/associations/quick_stats'); ?>
                    </div>
                    <?php $this->load->view('admin/associations/list_template'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
var hidden_columns = [];

$(function() {
    initDataTable(
        '.table-associations',
        admin_url + 'associations/table',
        false,
        false,
        {},
        [0, 'asc']
    );
    init_association();
});
</script>
</body>

</html>