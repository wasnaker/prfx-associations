<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('authentication/includes/head.php'); ?>
<?php if (!empty($recaptcha_enabled)) { ?>
<script src="https://www.google.com/recaptcha/api.js"></script>
<?php } ?>

<body class="tw-bg-neutral-100 login_admin">

<div class="tw-max-w-4xl tw-mx-auto tw-pt-10 tw-px-4">

    <div class="company-logo text-center tw-mb-6">
        <?php get_dark_company_logo(); ?>
    </div>

    <div class="text-center tw-mb-5">
        <h1 class="tw-text-neutral-800 tw-text-2xl tw-font-bold tw-mb-1">
            <?= _l('register_as_association'); ?>
        </h1>
        <p class="tw-text-neutral-500">
            <?= _l('already_have_account'); ?>
            <a href="<?= admin_url('authentication'); ?>" class="tw-font-medium">
                <?= _l('back_to_login'); ?>
            </a>
        </p>
    </div>

    <?php if (!empty($success)) : ?>
    <div class="alert alert-success text-center tw-mb-4">
        <?= htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($form_errors)) : ?>
    <div class="alert alert-danger tw-mb-4">
        <?= $form_errors; ?>
    </div>
    <?php endif; ?>

    <?= form_open(site_url('authentication/register/association'), ['id' => 'register-form']); ?>

    <!-- CSRF token -->
    <input type="hidden" name="reg_csrf_token" value="<?= e($csrf_token); ?>">

    <!-- Honeypot: hidden from humans, bots fill it -->
    <div style="display:none;position:absolute;left:-9999px;" aria-hidden="true">
        <input type="text" name="website_url" tabindex="-1" autocomplete="off" value="">
    </div>

    <div class="panel_s">
        <div class="panel-body">
            <div class="row">

                <!-- ── Left: Primary Contact Information ── -->
                <div class="col-md-6">
                    <h4 class="tw-font-bold tw-mb-4 tw-text-neutral-700">
                        <?= _l('primary_contact_information'); ?>
                    </h4>

                    <div class="form-group">
                        <label class="control-label">
                            <span class="text-danger">*</span> <?= _l('firstname'); ?>
                        </label>
                        <input type="text" name="firstname" class="form-control"
                            value="<?= htmlspecialchars($old_input['firstname'] ?? ''); ?>" autofocus>
                    </div>

                    <div class="form-group">
                        <label class="control-label">
                            <span class="text-danger">*</span> <?= _l('lastname'); ?>
                        </label>
                        <input type="text" name="lastname" class="form-control"
                            value="<?= htmlspecialchars($old_input['lastname'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="control-label">
                            <span class="text-danger">*</span> <?= _l('email_address'); ?>
                        </label>
                        <input type="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($old_input['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="control-label">
                            <span class="text-danger">*</span> <?= _l('password'); ?>
                        </label>
                        <input type="password" name="password" class="form-control" autocomplete="new-password">
                        <small class="text-muted">
                            <?= _l('registration_password_min'); ?> <?= _l('registration_password_uppercase'); ?>
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="control-label">
                            <span class="text-danger">*</span> <?= _l('repeat_password'); ?>
                        </label>
                        <input type="password" name="password_confirm" class="form-control" autocomplete="new-password">
                    </div>
                </div>

                <!-- ── Right: Company Information ── -->
                <div class="col-md-6">
                    <h4 class="tw-font-bold tw-mb-4 tw-text-neutral-700">
                        <?= _l('registration_association_name'); ?>
                    </h4>

                    <div class="form-group">
                        <label class="control-label">
                            <span class="text-danger">*</span> <?= _l('registration_association_name'); ?>
                        </label>
                        <input type="text" name="company_name" class="form-control"
                            value="<?= htmlspecialchars($old_input['company_name'] ?? ''); ?>">
                    </div>

                    <?php if (!empty($recaptcha_enabled)) { ?>
                    <div class="form-group tw-mt-4">
                        <div class="g-recaptcha" data-sitekey="<?= e($recaptcha_site_key); ?>"></div>
                    </div>
                    <?php } ?>
                </div>

            </div>
        </div>
        <div class="panel-footer text-right">
            <a href="<?= admin_url('authentication'); ?>" class="btn btn-default tw-mr-2">
                <?= _l('back_to_login'); ?>
            </a>
            <button type="submit" class="btn btn-primary" data-loading-text="<?= _l('wait_text'); ?>">
                <?= _l('clients_register_string'); ?>
            </button>
        </div>
    </div>
    <?= form_close(); ?>

</div>

</body>
</html>
