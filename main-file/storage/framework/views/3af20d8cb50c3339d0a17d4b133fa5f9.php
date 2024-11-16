<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Manage Plans')); ?>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('action-button'); ?>
    <div>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create plan')): ?>
            <?php if(count($payment_setting) > 0): ?>
                <div class="float-end">
                    <a href="#" class="btn btn-sm btn-primary btn-icon" data-url="<?php echo e(route('plans.create')); ?>" data-size="lg"
                        data-ajax-popup="true" data-title="<?php echo e(__('Create Plan')); ?>" title="<?php echo e(__('Create')); ?>"
                        data-bs-toggle="tooltip" data-bs-placement="top">
                        <i class="ti ti-plus"></i>
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active" aria-current="page"><?php echo e(__('Plan')); ?></li>
<?php $__env->stopSection(); ?>
<?php
    $user = Auth::user();
    $settings = App\Models\Utility::payment_settings();
?>
<?php $__env->startSection('content'); ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create plan')): ?>
        <div class="row g-o p-0">
            <div class="col-12">
                <?php if(count($payment_setting) == 0): ?>
                    <div class="alert alert-warning"><i class="fe fe-info"></i>
                        <?php echo e(__('Please set payment api key & secret key for add new plan')); ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-0 p-0">
        <div class="col-12">
            <div class="row">
                <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-xxl-3 col-lg-6 col-md-6 col-sm-6 plan_card mb-0 border-bottom border-end">
                        <div class="card shadow-none  price-card price-1 rounded-0">
                            <div class="card-body">
                                <span class="price-badge bg-primary"><?php echo e($plan->name); ?></span>

                                <div class="d-flex flex-row-reverse m-0 p-0 ">
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('edit plan')): ?>
                                        <?php if($plan->id != 1): ?>
                                            <?php echo Form::open([
                                                'method' => 'DELETE',
                                                'route' => ['plans.destroy', $plan->id],
                                                'id' => 'delete-form-' . $plan->id,
                                            ]); ?>

                                            <div class="action-btn bg-light-secondary ms-2">
                                                <a href="#"
                                                    class="mx-3 btn btn-sm d-inline-flex align-items-center bs-pass-para"
                                                    data-id="<?php echo e($plan['id']); ?>" data-confirm="<?php echo e(__('Are You Sure?')); ?>"
                                                    data-text="<?php echo e(__('This action can not be undone. Do you want to continue?')); ?>"
                                                    data-confirm-yes="delete-form-<?php echo e($plan->id); ?>"
                                                    title="<?php echo e(__('Delete')); ?>" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-title="<?php echo e(__('Delete Plan')); ?>">
                                                    <i class="ti ti-trash"></i>
                                                </a>
                                            </div>
                                            <?php echo Form::close(); ?>

                                        <?php endif; ?>


                                        <div class="action-btn bg-light-secondary ms-2">
                                            <a href="#" class="mx-3 btn btn-sm d-inline-flex align-items-center"
                                                title="<?php echo e(__('Edit')); ?>" data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-ajax-popup="true" data-size="lg" data-title="<?php echo e(__('Edit Plan')); ?>"
                                                data-url="<?php echo e(route('plans.edit', $plan->id)); ?>" data-bs-toggle="tooltip"
                                                data-bs-placement="top"><i class="ti ti-edit"></i></a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if(\Auth::user()->type == 'company' && \Auth::user()->plan == $plan->id): ?>
                                        <span class="d-flex align-items-center ms-2">
                                            <i class="f-10 lh-1 fas fa-circle text-success"></i>
                                            <span class="ms-2"><?php echo e(__('Active')); ?></span>
                                        </span>
                                    <?php endif; ?>
                                </div>


                                <span class="mb-4 f-w-500 p-price">
                                    <?php echo e(isset($settings['currency_symbol']) ? $settings['currency_symbol'] : '$'); ?>

                                    <?php echo e(number_format($plan->price)); ?> <small class="text-sm">  /  <?php echo e($plan->duration); ?></small>
                                </span>
                                <p class="mb-0">
                                </p>
                                <p class="mb-0">
                                    <?php echo e($plan->description); ?>

                                </p>

                                <?php if($plan->trial == 1): ?>
                                    <p class="mb-0">
                                        <?php echo e(__('Free Trial Days: '. $plan->trial_days)); ?>

                                    </p>
                                <?php endif; ?>

                                <ul class="list-unstyled my-4">
                                    <li>
                                        <span class="theme-avtar">
                                            <i class="text-primary ti ti-circle-plus"></i></span>
                                        <?php echo e($plan->max_users < 0 ? __('Unlimited') : $plan->max_users); ?>

                                        <?php echo e(__('Users')); ?>

                                    </li>
                                    <li>
                                        <span class="theme-avtar">
                                            <i class="text-primary ti ti-circle-plus"></i></span>
                                        <?php echo e($plan->max_advocates < 0 ? __('Unlimited') : $plan->max_advocates); ?>

                                        <?php echo e(__('Advocates')); ?>

                                    </li>
                                    <li>
                                        <span class="theme-avtar">
                                            <i class="text-primary ti ti-circle-plus"></i></span>
                                        <?php echo e($plan->enable_chatgpt == 'on' ? __('Enable Chat GPT') : __('Disable Chat GPT')); ?>

                                    </li>
                                </ul>


                                <div class="row d-flex justify-content-between">
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('buy plan')): ?>
                                        <?php if($plan->id != \Auth::user()->plan && $plan->price != 0): ?>
                                            <?php if($plan->trial == 1 && empty(\Auth::user()->trial_expire_date)): ?>
                                                <div class="col-5">
                                                    <div class="d-grid text-center">
                                                        <a href="<?php echo e(route('plan.trial', \Illuminate\Support\Facades\Crypt::encrypt($plan->id))); ?>"
                                                            class="btn btn-primary btn-sm d-flex justify-content-center align-items-center"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="<?php echo e(__('Free Trial')); ?>"><?php echo e(__('Free Trial')); ?>

                                                            <i class="fas fa-arrow-right m-1"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <div
                                                class="<?php echo e($plan->trial == 1 && !empty(\Auth::user()->trial_expire_date) ? 'col-8' : 'col-5'); ?>">
                                                <div class="d-grid text-center">
                                                    <a href="<?php echo e(route('payment', \Illuminate\Support\Facades\Crypt::encrypt($plan->id))); ?>"
                                                        class="btn btn-primary btn-sm d-flex justify-content-center align-items-center"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="<?php echo e(__('Subscribe')); ?>"><?php echo e(__('Subscribe')); ?>

                                                        <i class="fas fa-arrow-right m-1"></i></a>
                                                </div>
                                            </div>
                                        <?php elseif($plan->price <= 0): ?>
                                        <?php endif; ?>
                                        <?php endif; ?> <?php if($plan->id != 1 && \Auth::user()->plan != $plan->id && \Auth::user()->type == 'company'): ?>
                                            <div class="col-2">
                                                <?php if(\Auth::user()->requested_plan != $plan->id): ?>
                                                    <a href="<?php echo e(route('send.request', [\Illuminate\Support\Facades\Crypt::encrypt($plan->id)])); ?>"
                                                        class="btn btn-primary btn-icon btn-sm"
                                                        data-title="<?php echo e(__('Send Request')); ?>" data-bs-toggle="tooltip"
                                                        data-bs-placement="top" title="<?php echo e(__('Send Request')); ?>">
                                                        <span class="btn-inner--icon"><i class="fas fa-share"></i></span>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?php echo e(route('request.cancel', \Auth::user()->id)); ?>"
                                                        class="btn btn-danger btn-icon btn-sm"
                                                        data-title="<?php echo e(__('Cancle Request')); ?>" data-bs-toggle="tooltip"
                                                        data-bs-placement="top" title="<?php echo e(__('Cancle Request')); ?>">
                                                        <span class="btn-inner--icon"><i class="fas fa-times"></i></span>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if(\Auth::user()->type == 'company' && \Auth::user()->plan == $plan->id): ?>
                                            <?php if(empty(\Auth::user()->plan_expire_date) && empty(Auth::user()->trial_expire_date)): ?>
                                                <p class="mb-0"><?php echo e(__('Lifetime')); ?></p>
                                            <?php elseif(\Auth::user()->plan_expire_date > \Auth::user()->trial_expire_date): ?>
                                                <p class="mb-0">
                                                    <?php echo e(__('Plan Expires on ')); ?>

                                                    <?php echo e(date('d M Y', strtotime(\Auth::user()->plan_expire_date))); ?>

                                                </p>

                                            <?php else: ?>
                                                <p class="mb-0">
                                                    <?php echo e(__('Trial Expires on ')); ?>

                                                    <?php echo e(!empty(\Auth::user()->trial_expire_date) ? date('d M Y', strtotime(\Auth::user()->trial_expire_date)) : date('Y-m-d')); ?>

                                                </p>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if(Auth::user()->type == 'super admin' && $plan->id != 1): ?>
                                            <div class="form-switch custom-switch-v1 float-end">
                                                <input type="checkbox" data-id="<?php echo e($plan->id); ?>"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="<?php echo e(__('Enable/Disable')); ?>" class="form-check-input input-primary"
                                                    <?php echo e($plan->status == 1 ? 'checked' : ''); ?>>
                                            </div>
                                        <?php endif; ?>

                                    </div>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    <?php $__env->stopSection(); ?>

    <?php $__env->startPush('custom-script'); ?>
        <script>
            $(document).on('change', '#trial', function() {
                if ($(this).is(':checked')) {
                    $('.plan_div').removeClass('d-none');
                    $('#trial').attr("required", true);

                } else {
                    $('.plan_div').addClass('d-none');
                    $('#trial').removeAttr("required");
                }
            });

            $('.input-primary').on('change', function() {
                var planId = $(this).data('id');
                var isChecked = $(this).prop('checked');

                $.ajax({
                    type: 'POST',
                    url: '<?php echo e(route('update.plan.status')); ?>',
                    data: {
                        '_token': '<?php echo e(csrf_token()); ?>',
                        'plan_id': planId
                    },
                    success: function(response) {
                        if(response.success){

                            show_toastr('Success', response.message, 'success')
                        }else{
                            show_toastr('Error', response.message, 'error')

                        }
                    },
                    error: function(error) {

                        if (error.status === 404) {
                            $(this).prop('checked', !isChecked);
                        }
                    }
                });
            });
        </script>
    <?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/yuri/www/adv-flow/main-file/resources/views/plan/index.blade.php ENDPATH**/ ?>