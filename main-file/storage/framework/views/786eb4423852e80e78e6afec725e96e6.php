<?php
    $user = Auth::user();
    $settings = App\Models\Utility::settings();
?>

<?php $__env->startPush('custom-script'); ?>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.3.0/jquery.form.min.js"></script>

    <script>
        var type = window.location.hash.substr(1);
        $('.list-group-item').removeClass('active');
        $('.list-group-item').removeClass('text-primary');
        if (type != '') {
            $('a[href="#' + type + '"]').addClass('active').removeClass('text-primary');
        } else {
            $('.list-group-item:eq(0)').addClass('active').removeClass('text-primary');
        }

        $(document).on('click', '.list-group-item', function() {
            $('.list-group-item').removeClass('active');
            $('.list-group-item').removeClass('text-primary');
            setTimeout(() => {
                $(this).addClass('active').removeClass('text-primary');
            }, 10);
        });

        var scrollSpy = new bootstrap.ScrollSpy(document.body, {
            target: '#useradd-sidenav',
            offset: 300
        })
    </script>

    <script type="text/javascript">
        $(document).ready(function() {

        });

        $(document).on('click', '.apply-coupon', function(e) {
            e.preventDefault();
            var where = $(this).attr('data-from');

            applyCoupon($('#' + where + '_coupon').val(), where);
        })

        function applyCoupon(coupon_code, where) {

            if (coupon_code != '') {
                $.ajax({
                    url: '<?php echo e(route('apply.coupon')); ?>',
                    datType: 'json',
                    data: {
                        plan_id: '<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>',
                        coupon: coupon_code,
                        frequency: $('input[name="' + where + '_payment_frequency"]:checked').val()
                    },
                    success: function(data) {


                        if (data.is_success) {
                            $('.' + where + '-coupon-tr').show().find('.' + where + '-coupon-price').text(data
                                .discount_price);
                            $('.' + where + '-final-price').text(data.final_price);

                            show_toastr('success', data.message, 'success');
                        } else {
                            $('.' + where + '-coupon-tr').hide().find('.' + where + '-coupon-price').text('');
                            $('.' + where + '-final-price').text(data.final_price);
                            show_toastr('error', data.message, 'error');
                        }
                    }
                })
            } else {
                show_toastr('error', '<?php echo e(__('Invalid Coupon Code.')); ?>');
                $('.' + where + '-coupon-tr').hide().find('.' + where + '-coupon-price').text('');
            }
        }
    </script>

    <script type="text/javascript">
        <?php if(isset($admin_payment_setting['is_stripe_enabled']) &&
                $admin_payment_setting['is_stripe_enabled'] == 'on' &&
                !empty($admin_payment_setting['stripe_key']) &&
                !empty($admin_payment_setting['stripe_secret'])): ?>

            var stripe = Stripe('<?php echo e($admin_payment_setting['stripe_key']); ?>');
            var elements = stripe.elements();

            // Custom styling can be passed to options when creating an Element.
            var style = {
                base: {
                    // Add your base input styles here. For example:
                    fontSize: '14px',
                    color: '#32325d',
                },
            };

            // Create an instance of the card Element.
            var card = elements.create('card', {
                style: style
            });

            // Add an instance of the card Element into the `card-element` <div>.
            card.mount('#card-element');
            // Create a token or display an error when the form is submitted.
            var form = document.getElementById('payment-form');

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                stripe.createToken(card).then(function(result) {
                    if (result.error) {
                        $("#card-errors").html(result.error.message);
                        toastrs('Error', result.error.message, 'error');
                    } else {
                        // Send the token to your server.
                        stripeTokenHandler(result.token);
                    }
                });
            });

            function stripeTokenHandler(token) {
                // Insert the token ID into the form so it gets submitted to the server
                var form = document.getElementById('payment-form');
                var hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'stripeToken');
                hiddenInput.setAttribute('value', token.id);
                form.appendChild(hiddenInput);
                // Submit the form
                form.submit();
            }
        <?php endif; ?>
    </script>

    <?php if(
        !empty($admin_payment_setting['is_paystack_enabled']) &&
            isset($admin_payment_setting['is_paystack_enabled']) &&
            $admin_payment_setting['is_paystack_enabled'] == 'on'): ?>
        <script src="https://js.paystack.co/v1/inline.js"></script>
        <script>
            $(document).on("click", "#pay_with_paystack", function() {
                $('#paystack-payment-form').ajaxForm(function(res) {
                    if (res.flag == 1) {
                        var coupon_id = res.coupon;
                        var paystack_callback = "<?php echo e(url('/plan/paystack')); ?>";
                        var order_id = '<?php echo e(time()); ?>';
                        var handler = PaystackPop.setup({
                            key: '<?php echo e($admin_payment_setting['paystack_public_key']); ?>',
                            email: res.email,
                            amount: res.total_price * 100,
                            currency: res.currency,
                            ref: 'pay_ref_id' + Math.floor((Math.random() * 1000000000) +
                                1
                            ), // generates a pseudo-unique reference. Please replace with a reference you generated. Or remove the line entirely so our API will generate one for you
                            metadata: {
                                custom_fields: [{
                                    display_name: "Email",
                                    variable_name: "email",
                                    value: res.email,
                                }]
                            },

                            callback: function(response) {
                                console.log(response.reference, order_id);
                                window.location.href = paystack_callback + '/' + response
                                    .reference + '/' + '<?php echo e(encrypt($plan->id)); ?>' +
                                    '?coupon_id=' + coupon_id
                            },
                            onClose: function() {
                                alert('window closed');
                            }
                        });
                        handler.openIframe();
                    } else if (res.flag == 2) {

                    } else {
                        show_toastr('error', data.message);
                    }

                }).trigger('submit');
            });
        </script>
    <?php endif; ?>

    <?php if(
        !empty($admin_payment_setting['is_flutterwave_enabled']) &&
            isset($admin_payment_setting['is_flutterwave_enabled']) &&
            $admin_payment_setting['is_flutterwave_enabled'] == 'on'): ?>
        <script src="https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>

        <script>
            //    is_flutterwave_enabled Payment
            $(document).on("click", "#pay_with_flaterwave", function() {

                $('#flaterwave-payment-form').ajaxForm(function(res) {
                    if (res.flag == 1) {
                        var coupon_id = res.coupon;
                        var API_publicKey = '';
                        if ("<?php echo e(isset($admin_payment_setting['flutterwave_public_key'])); ?>") {
                            API_publicKey = "<?php echo e($admin_payment_setting['flutterwave_public_key']); ?>";
                        }
                        var nowTim = "<?php echo e(date('d-m-Y-h-i-a')); ?>";
                        var flutter_callback = "<?php echo e(url('/plan/flaterwave')); ?>";
                        var x = getpaidSetup({
                            PBFPubKey: API_publicKey,
                            customer_email: '<?php echo e(Auth::user()->email); ?>',
                            amount: res.total_price,
                            currency: res.currency,
                            txref: nowTim + '__' + Math.floor((Math.random() * 1000000000)) +
                                'fluttpay_online-' +
                                <?php echo e(date('Y-m-d')); ?>,
                            meta: [{
                                metaname: "payment_id",
                                metavalue: "id"
                            }],
                            onclose: function() {},
                            callback: function(response) {
                                var txref = response.tx.txRef;
                                if (
                                    response.tx.chargeResponseCode == "00" ||
                                    response.tx.chargeResponseCode == "0"
                                ) {
                                    window.location.href = flutter_callback + '/' + txref + '/' +
                                        '<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>?coupon_id=' +
                                        coupon_id + '&payment_frequency=' + res.payment_frequency;
                                } else {
                                    // redirect to a failure page.
                                }
                                x.close(); // use this to close the modal immediately after payment.
                            }
                        });
                    } else if (res.flag == 2) {

                    } else {
                        show_toastr('Error', data.message, 'msg');
                    }

                }).trigger('submit');
            });
        </script>
    <?php endif; ?>

    <?php if(
        !empty($admin_payment_setting['is_razorpay_enabled']) &&
            isset($admin_payment_setting['is_razorpay_enabled']) &&
            $admin_payment_setting['is_razorpay_enabled'] == 'on'): ?>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
            // Razorpay Payment

            $(document).on("click", "#pay_with_razorpay", function() {
                $('#razorpay-payment-form').ajaxForm(function(res) {
                    if (res.flag == 1) {

                        var razorPay_callback = '<?php echo e(url('/plan/razorpay')); ?>';
                        var totalAmount = res.total_price * 100;
                        var coupon_id = res.coupon;
                        var API_publicKey = '';
                        if ("<?php echo e(isset($admin_payment_setting['razorpay_public_key'])); ?>") {
                            API_publicKey = "<?php echo e($admin_payment_setting['razorpay_public_key']); ?>";
                        }
                        var options = {
                            "key": API_publicKey, // your Razorpay Key Id
                            "amount": totalAmount,
                            "name": 'Plan',
                            "currency": res.currency,
                            "description": "",
                            "handler": function(response) {
                                window.location.href = razorPay_callback + '/' + response
                                    .razorpay_payment_id + '/' +
                                    '<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>?coupon_id=' +
                                    coupon_id + '&payment_frequency=' + res.payment_frequency;
                            },
                            "theme": {
                                "color": "#528FF0"
                            }
                        };
                        var rzp1 = new Razorpay(options);
                        rzp1.open();
                    } else if (res.flag == 2) {

                    } else {
                        show_toastr('Error', data.message, 'msg');
                    }

                }).trigger('submit');
            });
        </script>
    <?php endif; ?>

    <?php if(
        $admin_payment_setting['is_payfast_enabled'] == 'on' &&
            !empty($admin_payment_setting['payfast_merchant_id']) &&
            !empty($admin_payment_setting['payfast_merchant_key'])): ?>
        <script>
            $(document).ready(function() {
                get_payfast_status(amount = 0, coupon = null);
            })

            function get_payfast_status(amount, coupon) {

                var plan_id = $('#plan_id').val();

                $.ajax({
                    url: '<?php echo e(route('payfast.payment')); ?>',
                    method: 'POST',
                    data: {
                        'plan_id': plan_id,
                        'coupon_amount': amount,
                        'coupon_code': coupon
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        if (data.success == true) {
                            $('#get-payfast-inputs').append(data.inputs);

                        } else {
                            show_toastr('error', data.inputs, 'error')
                        }
                    }
                });
            }
        </script>
    <?php endif; ?>
    <?php if(isset($admin_payment_setting['is_payhere_enabled']) && $admin_payment_setting['is_payhere_enabled'] == 'on'): ?>
        <script>
            $(document).ready(function() {
                //get_payhere_status(amount = 0, coupon = null);
            })

            function get_payhere_status(amount, coupon) {

                var plan_id = $('#plan_id').val();

                $.ajax({
                    url: '<?php echo e(route('plan.payhere.payment')); ?>',
                    method: 'POST',
                    data: {
                        'plan_id': plan_id,
                        'coupon_amount': amount,
                        'coupon_code': coupon
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {

                        if (data.success == true) {
                            var form = $('#get-payhere-inputs');

                            // Append each input field to the form
                            $.each(data.inputs, function(name, value) {
                                var type = ['order_id', 'items', 'currency', 'amount', 'first_name',
                                    'last_name', 'email', 'address', 'city'
                                ].includes(name) ? 'text' : 'hidden';
                                form.append('<input name="' + name + '" type="' + type + '" value=\'' +
                                    value + '\' />');
                            });

                            form.addClass('d-none')
                        } else {
                            show_toastr('error', data.msg, 'error')
                        }
                    }
                });
            }
        </script>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0="
        crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.3.0/jquery.form.js"
        integrity="sha384-qlmct0AOBiA2VPZkMY3+2WqkHtIQ9lSdAsAn5RUJD/3vA5MKDgSGcdmIv4ycVxyn" crossorigin="anonymous">
    </script>
<?php $__env->stopPush(); ?>


<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Plan Payment')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('plans.index')); ?>"><?php echo e(__('Plan')); ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo e(__('Plan Payment')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <div class="col-sm-12">
        <div class="row g-0">
            <div class="col-xl-3 border-end border-bottom">
                <div class="card shadow-none bg-transparent sticky-top">

                    <div class="list-group list-group-flush rounded-0" id="useradd-sidenav">
                        <?php if(isset($admin_payment_setting['is_manually_enabled']) && $admin_payment_setting['is_manually_enabled'] == 'on'): ?>
                            <a class="list-group-item list-group-item-action border-0" data-toggle="tab"
                                href="#manually-payment" role="tab" aria-controls="manually"
                                aria-selected="true"><?php echo e(__('Manually')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_bank_enabled']) && $admin_payment_setting['is_bank_enabled'] == 'on'): ?>
                            <a class="list-group-item list-group-item-action border-0" data-toggle="tab"
                                href="#bank-payment" role="tab" aria-controls="manually"
                                aria-selected="true"><?php echo e(__('Bank Transfer')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_stripe_enabled']) && $admin_payment_setting['is_stripe_enabled'] == 'on'): ?>
                            <a class="list-group-item list-group-item-action border-0" data-toggle="tab"
                                href="#stripe-payment" role="tab" aria-controls="stripe"
                                aria-selected="true"><?php echo e(__('Stripe')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_paypal_enabled']) && $admin_payment_setting['is_paypal_enabled'] == 'on'): ?>
                            <a class="list-group-item list-group-item-action border-0" data-toggle="tab"
                                href="#paypal-payment" role="tab" aria-controls="paypal"
                                aria-selected="false"><?php echo e(__('Paypal')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_paystack_enabled']) && $admin_payment_setting['is_paystack_enabled'] == 'on'): ?>
                            <a class="list-group-item list-group-item-action border-0" data-toggle="tab"
                                href="#paystack-payment" role="tab" aria-controls="paystack"
                                aria-selected="false"><?php echo e(__('Paystack')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_flutterwave_enabled']) && $admin_payment_setting['is_flutterwave_enabled'] == 'on'): ?>
                            <a class="list-group-item list-group-item-action border-0" data-toggle="tab"
                                href="#flutterwave-payment" role="tab" aria-controls="flutterwave"
                                aria-selected="false"><?php echo e(__('Flutterwave')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_razorpay_enabled']) && $admin_payment_setting['is_razorpay_enabled'] == 'on'): ?>
                            <a class="list-group-item list-group-item-action border-0" data-toggle="tab"
                                href="#razorpay-payment" role="tab" aria-controls="razorpay"
                                aria-selected="false"><?php echo e(__('Razorpay')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_paytm_enabled']) && $admin_payment_setting['is_paytm_enabled'] == 'on'): ?>
                            <a class="list-group-item list-group-item-action border-0" data-toggle="tab"
                                href="#paytm-payment" role="tab" aria-controls="paytm"
                                aria-selected="false"><?php echo e(__('Paytm')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_mercado_enabled']) && $admin_payment_setting['is_mercado_enabled'] == 'on'): ?>
                            <a class="list-group-item list-group-item-action border-0" data-toggle="tab"
                                href="#mercadopago-payment" role="tab" aria-controls="mercadopago"
                                aria-selected="false"><?php echo e(__('Mercado Pago')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_mollie_enabled']) && $admin_payment_setting['is_mollie_enabled'] == 'on'): ?>
                            <a class="list-group-item list-group-item-action border-0" data-toggle="tab"
                                href="#mollie-payment" role="tab" aria-controls="mollie"
                                aria-selected="false"><?php echo e(__('Mollie')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_skrill_enabled']) && $admin_payment_setting['is_skrill_enabled'] == 'on'): ?>
                            <a class="list-group-item list-group-item-action border-0" data-toggle="tab"
                                href="#skrill-payment" role="tab" aria-controls="skrill"
                                aria-selected="false"><?php echo e(__('Skrill')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_coingate_enabled']) && $admin_payment_setting['is_coingate_enabled'] == 'on'): ?>
                            <a class="list-group-item list-group-item-action border-0" data-toggle="tab"
                                href="#coingate-payment" role="tab" aria-controls="coingate"
                                aria-selected="false"><?php echo e(__('Coingate')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_paymentwall_enabled']) && $admin_payment_setting['is_paymentwall_enabled'] == 'on'): ?>
                            <a class="list-group-item list-group-item-action border-0" data-toggle="tab"
                                href="#paymentwall-payment" role="tab" aria-controls="paymentwall"
                                aria-selected="true"><?php echo e(__('Paymentwall')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_toyyibpay_enabled']) && $admin_payment_setting['is_toyyibpay_enabled'] == 'on'): ?>
                            <a href="#toyyibpay_payment" class="list-group-item list-group-item-action border-0"
                                data-toggle="tab" role="tab" aria-controls="toyyibpay"
                                aria-selected="true"><?php echo e(__('Toyyibpay')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_payfast_enabled']) && $admin_payment_setting['is_payfast_enabled'] == 'on'): ?>
                            <a href="#payfast_payment" class="list-group-item list-group-item-action border-0"
                                data-toggle="tab" role="tab" aria-controls="payfast" aria-selected="true">
                                <?php echo e(__('Payfast')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_iyzipay_enabled']) && $admin_payment_setting['is_iyzipay_enabled'] == 'on'): ?>
                            <a href="#useradd-16" class="list-group-item list-group-item-action border-0">
                                <?php echo e(__('IyziPay')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_sspay_enabled']) && $admin_payment_setting['is_sspay_enabled'] == 'on'): ?>
                            <a href="#useradd-17" class="list-group-item list-group-item-action border-0">
                                <?php echo e(__('SSPay')); ?>

                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_paytab_enabled']) && $admin_payment_setting['is_paytab_enabled'] == 'on'): ?>
                            <a href="#useradd-18"
                                class="list-group-item list-group-item-action border-0"><?php echo e(__('PayTab')); ?> <div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_benefit_enabled']) && $admin_payment_setting['is_benefit_enabled'] == 'on'): ?>
                            <a href="#useradd-19"
                                class="list-group-item list-group-item-action border-0"><?php echo e(__('Benefit')); ?> <div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_cashfree_enabled']) && $admin_payment_setting['is_cashfree_enabled'] == 'on'): ?>
                            <a href="#useradd-20"
                                class="list-group-item list-group-item-action border-0"><?php echo e(__('Cashfree')); ?> <div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_aamarpay_enabled']) && $admin_payment_setting['is_aamarpay_enabled'] == 'on'): ?>
                            <a href="#useradd-21"
                                class="list-group-item list-group-item-action border-0"><?php echo e(__('Aamarpay')); ?> <div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_paytr_enabled']) && $admin_payment_setting['is_paytr_enabled'] == 'on'): ?>
                            <a href="#useradd-22"
                                class="list-group-item list-group-item-action border-0"><?php echo e(__('Pay TR')); ?> <div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_yookassa_enabled']) && $admin_payment_setting['is_yookassa_enabled'] == 'on'): ?>
                            <a href="#useradd-23"
                                class="list-group-item list-group-item-action border-0"><?php echo e(__('Yookassa')); ?> <div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_midtrans_enabled']) && $admin_payment_setting['is_midtrans_enabled'] == 'on'): ?>
                            <a href="#useradd-24"
                                class="list-group-item list-group-item-action border-0"><?php echo e(__('Midtrans')); ?> <div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_xendit_enabled']) && $admin_payment_setting['is_xendit_enabled'] == 'on'): ?>
                            <a href="#useradd-25"
                                class="list-group-item list-group-item-action border-0"><?php echo e(__('Xendit')); ?> <div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_payhere_enabled']) && $admin_payment_setting['is_payhere_enabled'] == 'on'): ?>
                                    <a href="#useradd-26"
                                        class="list-group-item list-group-item-action border-0"><?php echo e(__('PayHere')); ?> <div
                                            class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                                <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_paiementpro_enabled']) && $admin_payment_setting['is_paiementpro_enabled'] == 'on'): ?>
                            <a href="#paiementpro_payment"
                                class="list-group-item list-group-item-action border-0"><?php echo e(__('Paiementpro')); ?><div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_nepalste_enabled']) && $admin_payment_setting['is_nepalste_enabled'] == 'on'): ?>
                            <a href="#paiementpro_payment"
                                class="list-group-item list-group-item-action border-0"><?php echo e(__('Nepalste')); ?><div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_cinetpay_enabled']) && $admin_payment_setting['is_cinetpay_enabled'] == 'on'): ?>
                            <a href="#cinetpay_payment"
                                class="list-group-item list-group-item-action border-0"><?php echo e(__('Cinetpay')); ?><div
                                    class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                        <?php endif; ?>
                        <?php if(isset($admin_payment_setting['is_fedapay_enabled']) && $admin_payment_setting['is_fedapay_enabled'] == 'on'): ?>
                        <a href="#fedapay_payment"
                           class="list-group-item list-group-item-action border-0"><?php echo e(__('Fedapay')); ?><div
                                class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                    <?php endif; ?>

                    </div>

                    <div class="card shadow-none rounded-0 border price-card price-1 wow animate__fadeInUp"
                        data-wow-delay="0.2s"
                        style="visibility: visible; animation-delay: 0.2s; animation-name: fadeInUp;">
                        <div class="card-body">
                            <span class="price-badge bg-primary"><?php echo e($plan->name); ?></span>

                            <span class="mb-4 f-w-600 p-price"><?php echo e(number_format($plan->price)); ?><small class="text-sm">/
                                    <?php echo e($plan->duration); ?></small></span>

                            <p class="mb-0">
                                <?php echo e($plan->description); ?>

                            </p>

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
                                    <?php echo e($plan->max_employee < 0 ? __('Unlimited') : $plan->max_employee); ?>

                                    <?php echo e(__('Employee')); ?>

                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-9">
                <?php if($admin_payment_setting['is_manually_enabled'] == 'on'): ?>
                    <div id="useradd-14" class="card shadow-none rounded-0 border-bottom">
                        <div class="card-header">
                            <h5 class=" h6 mb-0"><?php echo e(__('Manually')); ?></h5>
                        </div>
                        <div class="card-body">
                            <label><?php echo e(__('Requesting manual payment for the planned amount for the subscriptions plan.')); ?></label>
                        </div>
                        <div class="card-footer text-end">


                            <?php if(empty($planReqs)): ?>
                                <a href="<?php echo e(route('send.request', [\Illuminate\Support\Facades\Crypt::encrypt($plan->id)])); ?>"
                                    class="btn btn-primary btn-icon m-1" data-title="<?php echo e(__('Send Request')); ?>"
                                    data-bs-toggle="tooltip">
                                    <span class="btn-inner--icon"><?php echo e(__('Send Request')); ?></span>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo e(route('response.request', [$planReqs->id, 0])); ?>"
                                    class="btn btn-danger btn-icon m-1" data-title="<?php echo e(__('Cancel Request')); ?>"
                                    data-bs-toggle="tooltip">
                                    <span class="btn-inner--icon"><?php echo e(__('Cancel Request')); ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if($admin_payment_setting['is_bank_enabled'] == 'on'): ?>
                    <div id="bank-payment" class="card shadow-none rounded-0 border-bottom">
                        <form class="w3-container w3-display-middle w3-card-4" method="POST"
                            action="<?php echo e(route('plan.pay.with.bank')); ?>" enctype='multipart/form-data'>
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5 class=" h6 mb-0"><?php echo e(__('Bank Transfer')); ?></h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label"><b><?php echo e(__('Bank Details:')); ?></b></label>
                                            <div class="form-group">

                                                <?php if(isset($admin_payment_setting['bank_details']) && !empty($admin_payment_setting['bank_details'])): ?>
                                                    <?php echo $admin_payment_setting['bank_details']; ?>

                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label"> <?php echo e(__('Payment Receipt')); ?></label>
                                            <div class="form-group">
                                                <input type="file" name="payment_receipt" class="form-control mb-3"
                                                    required>
                                            </div>
                                        </div>
                                    </div>
                                    <form>
                                        <div class="row mt-3">
                                            <div class="col-md-10">
                                                <div class="form-group">
                                                    <label for="bank_coupon"
                                                        class="form-label"><?php echo e(__('Coupon')); ?></label>
                                                    <input type="text" id="bank_coupon" name="coupon"
                                                        class="form-control coupon"
                                                        placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-2 coupon-apply-btn mt-4">
                                                <div class="form-group apply-bank-btn-coupon">
                                                    <a href="#"
                                                        class="btn btn-primary align-items-center apply-coupon"
                                                        data-from="bank"><?php echo e(__('Apply')); ?></a>
                                                </div>
                                            </div>
                                            <div class="col-6 text-right">
                                                <b><?php echo e(__('Plan Price')); ?></b> : $<?php echo e($plan->price); ?><b
                                                    class="bank-coupon-price"></b>
                                            </div>
                                            <div class="col-6 text-right ">
                                                <b><?php echo e(__('Net Amount')); ?></b> : $<span class="bank-final-price">
                                                    <?php echo e($plan->price); ?>

                                                </span></b>
                                                <small>(After coupon apply)</small>
                                            </div>

                                            <div class="row mt-2">
                                                <div class="col-sm-12">
                                                    <div class="float-end">
                                                        <input type="hidden" name="plan_id"
                                                            value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                        <button class="btn btn-primary d-flex align-items-center"
                                                            type="submit">
                                                            <i class="mdi mdi-cash-multiple mr-1"></i> <?php echo e(__('Pay Now')); ?>

                                                            (<span
                                                                class="bank-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="error" style="display: none;">
                                                    <div class='alert-danger alert'>
                                                        <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                <?php if(isset($admin_payment_setting['is_stripe_enabled']) && $admin_payment_setting['is_stripe_enabled'] == 'on'): ?>
                    <div id="stripe-payment" class="card  shadow-none rounded-0 border-bottom">
                        <div class="card-header">
                            <h5><?php echo e(__('Pay Using Stripe')); ?></h5>
                            <small class="text-muted"><?php echo e(__('Details about your plan Stripe payment')); ?></small>
                        </div>
                        <div class="card-body">
                            <form role="form" action="<?php echo e(route('stripe.post')); ?>" method="post"
                                class="require-validation" id="payment-form">
                                <?php echo csrf_field(); ?>
                                <div class=" rounded stripe-payment-div">
                                    <div class="row">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label class="form-label" for="card-name-on"
                                                    class="form-label"><?php echo e(__('Name on card')); ?></label>
                                                <input type="text" name="name" id="card-name-on"
                                                    class="form-control required"
                                                    placeholder="<?php echo e(\Auth::user()->name); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <div id="card-element"></div>
                                            <div id="card-errors" role="alert"></div>
                                        </div>
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="stripe_coupon"
                                                    class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="stripe_coupon" name="coupon"
                                                    class="form-control coupon" data-from="stripe"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn mt-4">
                                            <div class="form-group apply-stripe-btn-coupon">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="stripe"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="stripe-coupon-price"></b>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">

                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i> <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="stripe-final-price"><?php echo e($admin_payment_setting['currency_symbol']); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_paypal_enabled']) && $admin_payment_setting['is_paypal_enabled'] == 'on'): ?>
                    <div class="card  shadow-none rounded-0 border-bottom" id="paypal-payment">
                        <form role="form" action="<?php echo e(route('plan.pay.with.paypal')); ?>" method="post"
                            id="paypal-payment-form" class="w3-container w3-display-middle w3-card-4">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('Pay Using Paypal')); ?></h5>
                                <small class="text-muted"><?php echo e(__('Details about your plan Paypal payment')); ?></small>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="paypal_coupon"
                                                    class="form-label"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="paypal_coupon" name="coupon"
                                                    class="form-control coupon"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group apply-paypal-btn-coupon mt-4">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="paypal"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right paypal-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="paypal-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i> <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="paypal-final-price"><?php echo e($admin_payment_setting['currency_symbol']); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_paystack_enabled']) && $admin_payment_setting['is_paystack_enabled'] == 'on'): ?>
                    <div id="paystack-payment" class="card  shadow-none rounded-0 border-bottom ">
                        <form role="form" action="<?php echo e(route('plan.pay.with.paystack')); ?>" method="post"
                            id="paystack-payment-form" class="w3-container w3-display-middle w3-card-4">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('Paystack')); ?></h5>
                                <small class="text-muted"><?php echo e(__('Details about your plan Paystack payment')); ?></small>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="paystack_coupon"
                                                    class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="paystack_coupon" name="coupon"
                                                    class="form-control coupon" data-from="paystack"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group pt-3 mt-3">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="paystack"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right paystack-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="paystack-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="button" id="pay_with_paystack">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i> <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="paystack-final-price"><?php echo e($admin_payment_setting['currency_symbol']); ?><?php echo e($plan->price); ?></span>)
                                                    </button>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_flutterwave_enabled']) && $admin_payment_setting['is_flutterwave_enabled'] == 'on'): ?>
                    <div id="flutterwave-payment" class="card  shadow-none rounded-0 border-bottom ">
                        <form role="form" action="<?php echo e(route('plan.pay.with.flaterwave')); ?>" method="post"
                            class="w3-container w3-display-middle w3-card-4" id="flaterwave-payment-form">
                            <?php echo csrf_field(); ?> <div class="card-header">
                                <h5><?php echo e(__('Flutterwave')); ?></h5>
                                <small class="text-muted"><?php echo e(__('Details about your plan Flutterwave payment')); ?></small>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="flaterwave_coupon"
                                                    class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="flaterwave_coupon" name="coupon"
                                                    class="form-control coupon" data-from="flaterwave"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group pt-3 mt-3">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="flaterwave"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right flaterwave-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="flaterwave-coupon-price"></b>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="button" id="pay_with_flaterwave">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i> <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="flaterwave-final-price"><?php echo e($admin_payment_setting['currency_symbol']); ?><?php echo e($plan->price); ?></span>)
                                                    </button>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_razorpay_enabled']) && $admin_payment_setting['is_razorpay_enabled'] == 'on'): ?>
                    <div id="razorpay-payment" class="card  shadow-none rounded-0 border-bottom ">
                        <form role="form" action="<?php echo e(route('plan.pay.with.razorpay')); ?>" method="post"
                            class="w3-container w3-display-middle w3-card-4" id="razorpay-payment-form">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('Razorpay')); ?></h5>
                                <small class="text-muted"><?php echo e(__('Details about your plan Razorpay payment')); ?></small>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-10">
                                            <div class="form-group">
                                                <label for="razorpay_coupon"
                                                    class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="razorpay_coupon" name="coupon"
                                                    class="form-control coupon" data-from="razorpay"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group pt-3 mt-3">
                                                <a href="#" class="btn btn-primary  align-items-center apply-coupon"
                                                    data-from="razorpay"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right razorpay-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="razorpay-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="button" id="pay_with_razorpay">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i> <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="razorpay-final-price"><?php echo e($admin_payment_setting['currency_symbol']); ?><?php echo e($plan->price); ?></span>)
                                                    </button>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_paytm_enabled']) && $admin_payment_setting['is_paytm_enabled'] == 'on'): ?>
                    <div id="paytm-payment" class="card  shadow-none rounded-0 border-bottom ">
                        <form role="form" action="<?php echo e(route('plan.pay.with.paytm')); ?>" method="post"
                            class="w3-container w3-display-middle w3-card-4" id="paytm-payment-form">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('Paytm')); ?></h5>
                                <small class="text-muted"><?php echo e(__('Details about your plan Paytm payment')); ?></small>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-10">
                                            <div class="form-group">
                                                <label for="paytm_coupon"
                                                    class="form-label text-dark"><?php echo e(__('Mobile Number')); ?></label>
                                                <input type="text" id="mobile" name="mobile"
                                                    class="form-control mobile" data-from="mobile"
                                                    placeholder="<?php echo e(__('Enter Mobile Number')); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-10">
                                            <div class="form-group">
                                                <label for="paytm_coupon"
                                                    class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="paytm_coupon" name="coupon"
                                                    class="form-control coupon" data-from="paytm"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group pt-3 mt-3">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="paytm"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right paytm-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="paytm-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit" id="pay_with_paytm">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i> <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="paytm-final-price"><?php echo e($admin_payment_setting['currency_symbol']); ?><?php echo e($plan->price); ?></span>)
                                                    </button>


                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_mercado_enabled']) && $admin_payment_setting['is_mercado_enabled'] == 'on'): ?>
                    <div id="mercadopago-payment" class="card  shadow-none rounded-0 border-bottom ">
                        <form role="form" action="<?php echo e(route('plan.pay.with.mercado')); ?>" method="post"
                            class="w3-container w3-display-middle w3-card-4" id="mercado-payment-form">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('Mercado Pago')); ?></h5>
                                <small
                                    class="text-muted"><?php echo e(__('Details about your plan Mercado Pago payment')); ?></small>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-10">
                                            <div class="form-group">
                                                <label for="mercado_coupon"
                                                    class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="mercado_coupon" name="coupon"
                                                    class="form-control coupon" data-from="mercado"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group pt-3 mt-3">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="mercado"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right mercado-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="mercado-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit" id="pay_with_paytm">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i> <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="mercado-final-price"><?php echo e($admin_payment_setting['currency_symbol']); ?><?php echo e($plan->price); ?></span>)
                                                    </button>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_mollie_enabled']) && $admin_payment_setting['is_mollie_enabled'] == 'on'): ?>
                    <div id="mollie-payment" class="card  shadow-none rounded-0 border-bottom ">
                        <form role="form" action="<?php echo e(route('plan.pay.with.mollie')); ?>" method="post"
                            class="w3-container w3-display-middle w3-card-4" id="mollie-payment-form">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('Mollie')); ?></h5>
                                <small class="text-muted"><?php echo e(__('Details about your plan Mollie payment')); ?></small>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-10">
                                            <div class="form-group">
                                                <label for="mollie_coupon"
                                                    class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="mollie_coupon" name="coupon"
                                                    class="form-control coupon" data-from="mollie"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group pt-3 mt-3">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="mollie"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right mollie-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="mollie-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit" id="pay_with_mollie">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i> <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="mollie-final-price"><?php echo e($admin_payment_setting['currency_symbol']); ?><?php echo e($plan->price); ?></span>)
                                                    </button>


                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_skrill_enabled']) && $admin_payment_setting['is_skrill_enabled'] == 'on'): ?>
                    <div id="skrill-payment" class="card  shadow-none rounded-0 border-bottom ">
                        <form role="form" action="<?php echo e(route('plan.pay.with.skrill')); ?>" method="post"
                            class="w3-container w3-display-middle w3-card-4" id="skrill-payment-form">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('Skrill')); ?></h5>
                                <small class="text-muted"><?php echo e(__('Details about your plan Skrill payment')); ?></small>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-10">
                                            <div class="form-group">
                                                <label for="skrill_coupon"
                                                    class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="skrill_coupon" name="coupon"
                                                    class="form-control coupon" data-from="skrill"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group pt-3 mt-3">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="skrill"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right skrill-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="skrill-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit" id="pay_with_skrill">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i> <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="skrill-final-price"><?php echo e($admin_payment_setting['currency_symbol']); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                        $skrill_data = [
                                            'transaction_id' => md5(
                                                date('Y-m-d') . strtotime('Y-m-d H:i:s') . 'user_id',
                                            ),
                                            'user_id' => 'user_id',
                                            'amount' => 'amount',
                                            'currency' => 'currency',
                                        ];
                                        session()->put('skrill_data', $skrill_data);
                                    ?>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_coingate_enabled']) && $admin_payment_setting['is_coingate_enabled'] == 'on'): ?>
                    <div id="coingate-payment" class="card  shadow-none rounded-0 border-bottom ">
                        <form role="form" action="<?php echo e(route('plan.pay.with.coingate')); ?>" method="post"
                            class="w3-container w3-display-middle w3-card-4" id="coingate-payment-form">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('Coingate')); ?></h5>
                                <small class="text-muted"><?php echo e(__('Details about your plan Coingate payment')); ?></small>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-10">
                                            <div class="form-group">
                                                <label for="coingate_coupon"
                                                    class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="coingate_coupon" name="coupon"
                                                    class="form-control coupon" data-from="coingate"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group pt-3 mt-3">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="coingate"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right coingate-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="coingate-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit" id="pay_with_coingate">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i> <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="coingate-final-price"><?php echo e($admin_payment_setting['currency_symbol']); ?><?php echo e($plan->price); ?></span>)
                                                    </button>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_paymentwall_enabled']) && $admin_payment_setting['is_paymentwall_enabled'] == 'on'): ?>
                    <div id="paymentwall-payment" class="card shadow-none rounded-0 border-bottom">
                        <form role="form" action="<?php echo e(route('paymentwall')); ?>" method="post"
                            id="paymentwall-payment-form" class="w3-container w3-display-middle w3-card-4">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('PaymentWall')); ?></h5>
                                <small class="text-muted"><?php echo e(__('Details about your plan PaymentWall payment')); ?></small>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="paymentwall_coupon"
                                                    class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="paymentwall_coupon" name="coupon"
                                                    class="form-control coupon" data-from="paymentwall"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group pt-3 mt-3">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="paymentwall"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right paymentwall-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="paymentwall-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit" id="pay_with_paymentwall">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i> <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="paymentwall-final-price"><?php echo e($admin_payment_setting['currency_symbol']); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_toyyibpay_enabled']) && $admin_payment_setting['is_toyyibpay_enabled'] == 'on'): ?>
                    <div id="toyyibpay_payment" class="card shadow-none rounded-0 border-bottom">
                        <form role="form" action="<?php echo e(route('plan.pay.with.toyyibpay')); ?>" method="post"
                            id="toyyibpay-payment-form" class="w3-container w3-display-middle w3-card-4">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('Toyyibpay')); ?></h5>
                                <small class="text-muted"><?php echo e(__('Details about your plan Toyyibpay payment')); ?></small>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="toyyibpay_coupon"
                                                    class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="toyyibpay_coupon" name="coupon"
                                                    class="form-control coupon" data-from="toyyibpay"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group pt-3 mt-3 apply-toyyibpay-btn-coupon">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="toyyibpay"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right toyyibpay-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="toyyibpay-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit" id="pay_with_toyyibpay">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i>
                                                        <?php echo e(__('Pay Now')); ?> (<span
                                                            class="toyyibpay-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)</button>


                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_payfast_enabled']) && $admin_payment_setting['is_payfast_enabled'] == 'on'): ?>
                    <div id="payfast_payment" class="card shadow-none rounded-0 border-bottom">
                        <div class="card-header">
                            <h5><?php echo e(__('Payfast')); ?></h5>
                        </div>

                        <?php if(
                            $admin_payment_setting['is_payfast_enabled'] == 'on' &&
                                !empty($admin_payment_setting['payfast_merchant_id']) &&
                                !empty($admin_payment_setting['payfast_merchant_key']) &&
                                !empty($admin_payment_setting['payfast_signature']) &&
                                !empty($admin_payment_setting['payfast_mode'])): ?>
                            <div
                                <?php echo e(($admin_payment_setting['is_payfast_enabled'] == 'on' &&
                                    !empty($admin_payment_setting['payfast_merchant_id']) &&
                                    !empty($admin_payment_setting['payfast_merchant_key'])) == 'on'
                                    ? 'active'
                                    : ''); ?>>
                                <?php
                                    $pfHost =
                                        $admin_payment_setting['payfast_mode'] == 'sandbox'
                                            ? 'sandbox.payfast.co.za'
                                            : 'www.payfast.co.za';
                                ?>
                                <form role="form" action=<?php echo e('https://' . $pfHost . '/eng/process'); ?> method="post"
                                    class="require-validation" id="payfast-form">
                                    <div class="card-body  ">

                                        <div class="row mt-3">
                                            <div class="col-md-10">
                                                <div class="form-group">
                                                    <label for="payfast_coupon"
                                                        class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                                    <input type="text" id="payfast_coupon" name="coupon"
                                                        class="form-control coupon" data-from="payfast"
                                                        placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-2 coupon-apply-btn">
                                                <div class="form-group pt-3 mt-3 apply-payfast-btn-coupon">
                                                    <a href="#"
                                                        class="btn btn-primary align-items-center apply-coupon"
                                                        data-from="payfast"><?php echo e(__('Apply')); ?></a>
                                                </div>
                                            </div>
                                            <div class="col-12 text-right payfast-coupon-tr" style="display: none">
                                                <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="payfast-coupon-price"></b>
                                            </div>

                                            <div id="get-payfast-inputs"></div>
                                            <div class="row mt-2">
                                                <div class="col-sm-12">
                                                    <div class="float-end">
                                                        <input type="hidden" name="plan_id" id="plan_id"
                                                            value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                        <button class="btn btn-primary d-flex align-items-center"
                                                            type="submit" id="pay_with_payfast">
                                                            <i class="mdi mdi-cash-multiple mr-1"></i>
                                                            <?php echo e(__('Pay Now')); ?> (<span
                                                                class="payfast-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_iyzipay_enabled']) && $admin_payment_setting['is_iyzipay_enabled'] == 'on'): ?>
                    <div id="useradd-16" class="card shadow-none rounded-0 border-bottom">
                        <form class="w3-container w3-display-middle w3-card-4" method="POST" id="iyzipay-payment-form"
                            action="<?php echo e(route('iyzipay.payment.init')); ?>">
                            <?php echo csrf_field(); ?> <div class="card-header">
                                <h5><?php echo e(__('IyziPay')); ?></h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="iyzipay_coupon"
                                                    class="form-label"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="iyzipay_coupon" name="coupon"
                                                    class="form-control coupon"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group pt-3 mt-3  apply-iyzipay-btn-coupon">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="iyzipay"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right iyzipay-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="iyzipay-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i>
                                                        <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="iyzipay-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_sspay_enabled']) && $admin_payment_setting['is_sspay_enabled'] == 'on'): ?>
                    <div id="useradd-17" class="card shadow-none rounded-0 border-bottom">
                        <form class="w3-container w3-display-middle w3-card-4" method="POST" id="sspay-payment-form"
                            action="<?php echo e(route('plan.sspaypayment')); ?>">
                            <?php echo csrf_field(); ?> <div class="card-header">
                                <h5><?php echo e(__('SSPay')); ?></h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="sspay_coupon" class="form-label"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="sspay_coupon" name="coupon"
                                                    class="form-control coupon"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group pt-3 mt-3 apply-sspay-btn-coupon">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="sspay"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right sspay-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="sspay-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i>
                                                        <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="sspay-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_paytab_enabled']) && $admin_payment_setting['is_paytab_enabled'] == 'on'): ?>
                    <div id="useradd-18" class="card shadow-none rounded-0 border-bottom">
                        <form class="w3-container w3-display-middle w3-card-4" method="POST" id="paytab-payment-form"
                            action="<?php echo e(route('plan.pay.with.paytab')); ?>">
                            <?php echo csrf_field(); ?> <div class="card-header">
                                <h5><?php echo e(__('PayTab')); ?></h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="paytab_coupon" class="form-label"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="paytab_coupon" name="coupon"
                                                    class="form-control coupon"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group pt-3 mt-3 apply-paytab-btn-coupon">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="paytab"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right paytab-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="paytab-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i>
                                                        <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="paytab-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_benefit_enabled']) && $admin_payment_setting['is_benefit_enabled'] == 'on'): ?>
                    <div id="useradd-19" class="card shadow-none rounded-0 border-bottom">
                        <form class="w3-container w3-display-middle w3-card-4" method="POST" id="benefit-payment-form"
                            action="<?php echo e(route('benefit.initiate')); ?>">
                            <?php echo csrf_field(); ?> <div class="card-header">
                                <h5><?php echo e(__('Benefit')); ?></h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="benefit_coupon"
                                                    class="form-label"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="benefit_coupon" name="coupon"
                                                    class="form-control coupon"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group pt-3 mt-3 apply-benefit-btn-coupon">
                                                <a href="#"
                                                    class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="benefit"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right benefit-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="benefit-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i>
                                                        <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="benefit-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_cashfree_enabled']) && $admin_payment_setting['is_cashfree_enabled'] == 'on'): ?>
                    <div id="useradd-20" class="card shadow-none rounded-0 border-bottom">
                        <form class="w3-container w3-display-middle w3-card-4" method="POST"
                            id="cashfree-payment-form" action="<?php echo e(route('plan.pay.with.cashfree')); ?>">
                            <?php echo csrf_field(); ?> <div class="card-header">
                                <h5><?php echo e(__('Cashfree')); ?></h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="cashfree_coupon"
                                                    class="form-label"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="cashfree_coupon" name="coupon"
                                                    class="form-control coupon"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group pt-3 mt-3 apply-cashfree-btn-coupon">
                                                <a href="#"
                                                    class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="cashfree"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right cashfree-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="cashfree-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i>
                                                        <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="cashfree-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_aamarpay_enabled']) && $admin_payment_setting['is_aamarpay_enabled'] == 'on'): ?>
                    <div id="useradd-21" class="card shadow-none rounded-0 border-bottom">
                        <form class="w3-container w3-display-middle w3-card-4" method="POST"
                            id="aamarpay-payment-form" action="<?php echo e(route('plan.pay.with.aamarpay')); ?>">
                            <?php echo csrf_field(); ?> <div class="card-header">
                                <h5><?php echo e(__('Aamarpay')); ?></h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="aamarpay_coupon"
                                                    class="form-label"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="aamarpay_coupon" name="coupon"
                                                    class="form-control coupon"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group pt-3 mt-3 apply-aamarpay-btn-coupon">
                                                <a href="#"
                                                    class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="aamarpay"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right aamarpay-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="aamarpay-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i>
                                                        <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="aamarpay-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if(isset($admin_payment_setting['is_paytr_enabled']) && $admin_payment_setting['is_paytr_enabled'] == 'on'): ?>
                    <div id="useradd-22" class="card  shadow-none rounded-0 border-bottom ">
                        <form class="w3-container w3-display-middle w3-card-4" method="POST" id="paytr-payment-form"
                            action="<?php echo e(route('plan.pay.with.paytr')); ?>">
                            <?php echo csrf_field(); ?> <div class="card-header">
                                <h5><?php echo e(__('Pay TR')); ?></h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="paytr_coupon"
                                                    class="form-label"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="paytr_coupon" name="coupon"
                                                    class="form-control coupon"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group pt-3 mt-3 apply-paytr-btn-coupon">
                                                <a href="#"
                                                    class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="paytr"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right paytr-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="paytr-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i>
                                                        <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="paytr-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                <?php if(isset($admin_payment_setting['is_yookassa_enabled']) && $admin_payment_setting['is_yookassa_enabled'] == 'on'): ?>
                    <div id="useradd-23" class="card  shadow-none rounded-0 border-bottom ">
                        <form class="w3-container w3-display-middle w3-card-4" method="get" id="yookassa-payment-form"
                            action="<?php echo e(route('plan.pay.with.yookassa')); ?>">
                            <?php echo csrf_field(); ?> <div class="card-header">
                                <h5><?php echo e(__('Yookassa')); ?></h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="yookassa_coupon"
                                                    class="form-label"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="yookassa_coupon" name="coupon"
                                                    class="form-control coupon"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group pt-3 mt-3 apply-yookassa-btn-coupon">
                                                <a href="#"
                                                    class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="yookassa"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right yookassa-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="yookassa-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i>
                                                        <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="yookassa-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                <?php if(isset($admin_payment_setting['is_midtrans_enabled']) && $admin_payment_setting['is_midtrans_enabled'] == 'on'): ?>
                    <div id="useradd-24" class="card  shadow-none rounded-0 border-bottom ">
                        <form class="w3-container w3-display-middle w3-card-4" method="get"
                            id="midtrans-payment-form" action="<?php echo e(route('plan.get.midtrans')); ?>">
                            <?php echo csrf_field(); ?> <div class="card-header">
                                <h5><?php echo e(__('Midtrans')); ?></h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="midtrans_coupon"
                                                    class="form-label"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="midtrans_coupon" name="coupon"
                                                    class="form-control coupon"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group pt-3 mt-3 apply-midtrans-btn-coupon">
                                                <a href="#"
                                                    class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="midtrans"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right midtrans-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="midtrans-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i>
                                                        <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="midtrans-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                <?php if(isset($admin_payment_setting['is_xendit_enabled']) && $admin_payment_setting['is_xendit_enabled'] == 'on'): ?>
                    <div id="useradd-25" class="card  shadow-none rounded-0 border-bottom ">
                        <form class="w3-container w3-display-middle w3-card-4" method="get"
                            id="midtrans-payment-form" action="<?php echo e(route('plan.xendit.payment')); ?>">
                            <?php echo csrf_field(); ?> <div class="card-header">
                                <h5><?php echo e(__('Xendit')); ?></h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="xendit_coupon"
                                                    class="form-label"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="xendit_coupon" name="coupon"
                                                    class="form-control coupon"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn">
                                            <div class="form-group pt-3 mt-3 apply-xendit-btn-coupon">
                                                <a href="#"
                                                    class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="xendit"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right xendit-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="xendit-coupon-price"></b>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-sm-12">
                                                <div class="float-end">
                                                    <input type="hidden" name="plan_id"
                                                        value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">
                                                    <button class="btn btn-primary d-flex align-items-center"
                                                        type="submit">
                                                        <i class="mdi mdi-cash-multiple mr-1"></i>
                                                        <?php echo e(__('Pay Now')); ?>

                                                        (<span
                                                            class="xendit-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="error" style="display: none;">
                                                <div class='alert-danger alert'>
                                                    <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                
                <?php if(isset($admin_payment_setting['is_payhere_enabled']) && $admin_payment_setting['is_payhere_enabled'] == 'on'): ?>
                    <?php
                        $phHost =
                            $admin_payment_setting['payhere_mode'] == 'local'
                                ? 'https://sandbox.payhere.lk/pay/checkout'
                                : 'https://www.payhere.lk/pay/checkout';
                    ?>

                    <div id="useradd-26" class="card  shadow-none rounded-0 border-bottom ">
                        <form class="w3-container w3-display-middle w3-card-4" method="post"
                            action="<?php echo e(route('plan.payhere.payment')); ?>">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('PayHere')); ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="row mt-3">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label for="payhere_coupon"
                                                class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                            <input type="text" id="payhere_coupon" name="coupon"
                                                class="form-control coupon" data-from="payhere"
                                                placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2 coupon-apply-btn mt-4">
                                        <div class="form-group apply-payhere-btn-coupon">
                                            <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                data-from="payhere"><?php echo e(__('Apply')); ?></a>
                                        </div>
                                    </div>
                                    <div class="col-12 text-right payhere-coupon-tr" style="display: none">
                                        <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="payhere-coupon-price"></b>
                                    </div>
                                    <div id="get-payhere-inputs"></div>

                                    <div class="row mt-2">
                                        <div class="col-sm-12">
                                            <div class="float-end">
                                                <input type="hidden" name="plan_id"
                                                    value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>"
                                                    id="plan_id">
                                                <button class="btn btn-primary d-flex align-items-center"
                                                    type="submit">
                                                    <i class="mdi mdi-cash-multiple mr-1"></i>
                                                    <?php echo e(__('Pay Now')); ?>

                                                    (<span
                                                        class="payhere-final-price"><?php echo e($settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$'); ?><?php echo e($plan->price); ?></span>)
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="error" style="display: none;">
                                            <div class='alert-danger alert'>
                                                <?php echo e(__('Please correct the errors and try again.')); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                
                <?php if(isset($admin_payment_setting['is_paiementpro_enabled']) && $admin_payment_setting['is_paiementpro_enabled'] == 'on'): ?>
                    <div id="paiementpro_payment" class="card  shadow-none rounded-0 border-bottom ">
                        <form role="form" method="post" action="<?php echo e(route('plan.pay.with.paiementpro')); ?>"
                            id="paiementpro-payment-form" class="w3-container w3-display-middle w3-card-4">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('Paiementpro')); ?></h5>
                                <p class="text-sm text-muted"><?php echo e(__('Details about your plan paiementpro payment')); ?>

                                </p>
                            </div>
                            <div class="card-body">
                                <div class="row mt-3">
                                    <div class="col-md-12 mt-4 row">
                                        <div class="form-group col-md-6">
                                            <?php echo e(Form::label('mobile_number', __('Mobile Number'), ['class' => 'form-label'])); ?>

                                            <input type="text" name="mobile_number"
                                                class="form-control font-style mobile_number">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <?php echo e(Form::label('channel', __('Channel'), ['class' => 'form-label'])); ?>

                                            <input type="text" name="channel"
                                                class="form-control font-style channel">
                                            <small class="text-danger">Example : OMCIV2,MOMO,CARD,FLOOZ ,PAYPAL</small>
                                        </div>
                                    </div>
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label for="paiementpro_coupon"
                                                class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                            <input type="text" id="paiementpro_coupon" name="coupon"
                                                class="form-control coupon" data-from="paiementpro"
                                                placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2 coupon-apply-btn mt-4">
                                        <div class="form-group apply-paiementpro-btn-coupon">
                                            <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                data-from="paiementpro"><?php echo e(__('Apply')); ?></a>
                                        </div>
                                    </div>
                                    <div class="col-12 text-right paiementpro-coupon-tr" style="display: none">
                                        <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="paiementpro-coupon-price"></b>
                                    </div>
                                </div>
                                <div class="col-sm-12 my-2 px-2">
                                    <div class="text-end">
                                        <input type="hidden" name="plan_id" id="plan_id"
                                            value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">

                                        <button type="submit" data-from="paiementpro" value="<?php echo e(__('Pay Now')); ?>"
                                            id="pay_with_paiementpro"
                                            class="btn btn-xs btn-primary"><?php echo e(__('Pay Now')); ?>(<span
                                                class="paiementpro-final-price"><?php echo e($plan->price); ?></span>)</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                

                
                <?php if(isset($admin_payment_setting['is_nepalste_enabled']) && $admin_payment_setting['is_nepalste_enabled'] == 'on'): ?>
                    <div id="nepalste_payment" class="card  shadow-none rounded-0 border-bottom ">
                        <form role="form" action="<?php echo e(route('plan.pay.with.nepalste')); ?>" method="post"
                            id="nepalste-payment-form" class="w3-container w3-display-middle w3-card-4">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('Nepalste')); ?></h5>
                                <p class="text-sm text-muted"><?php echo e(__('Details about your plan nepalste payment')); ?></p>
                            </div>
                            <div class="card-body">
                                <div class="row mt-3">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label for="nepalste_coupon"
                                                class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                            <input type="text" id="nepalste_coupon" name="coupon"
                                                class="form-control coupon" data-from="nepalste"
                                                placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2 coupon-apply-btn mt-4">
                                        <div class="form-group apply-nepalste-btn-coupon">
                                            <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                data-from="nepalste"><?php echo e(__('Apply')); ?></a>
                                        </div>
                                    </div>
                                    <div class="col-12 text-right nepalste-coupon-tr" style="display: none">
                                        <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="nepalste-coupon-price"></b>
                                    </div>
                                </div>
                                <div class="col-sm-12 my-2 px-2">
                                    <div class="text-end">
                                        <input type="hidden" name="plan_id" id="plan_id"
                                            value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">

                                        <button type="submit" data-from="nepalste" value="<?php echo e(__('Pay Now')); ?>"
                                            id="pay_with_nepalste"
                                            class="btn btn-xs btn-primary"><?php echo e(__('Pay Now')); ?>(<span
                                                class="nepalste-final-price"><?php echo e($plan->price); ?></span>)</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                

                
                <?php if(isset($admin_payment_setting['is_cinetpay_enabled']) && $admin_payment_setting['is_cinetpay_enabled'] == 'on'): ?>
                    <div id="cinetpay_payment" class="card shadow-none rounded-0 border-bottom">
                        <form role="form" action="<?php echo e(route('plan.pay.with.cinetpay')); ?>" method="post"
                            id="cinetpay-payment-form" class="w3-container w3-display-middle w3-card-4">
                            <?php echo csrf_field(); ?>
                            <div class="card-header">
                                <h5><?php echo e(__('Cinetpay')); ?></h5>
                                <p class="text-sm text-muted"><?php echo e(__('Details about your plan cinetpay payment')); ?></p>
                            </div>
                            <div class="card-body">
                                <div class="row mt-3">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label for="cinetpay_coupon"
                                                class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                            <input type="text" id="cinetpay_coupon" name="coupon"
                                                class="form-control coupon" data-from="cinetpay"
                                                placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2 coupon-apply-btn mt-4">
                                        <div class="form-group apply-cinetpay-btn-coupon">
                                            <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                data-from="cinetpay"><?php echo e(__('Apply')); ?></a>
                                        </div>
                                    </div>
                                    <div class="col-12 text-right cinetpay-coupon-tr" style="display: none">
                                        <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="cinetpay-coupon-price"></b>
                                    </div>
                                </div>
                                <div class="col-sm-12 my-2 px-2">
                                    <div class="text-end">
                                        <input type="hidden" name="plan_id" id="plan_id"
                                            value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">

                                        <button type="submit" data-from="cinetpay" value="<?php echo e(__('Pay Now')); ?>"
                                            id="pay_with_cinetpay"
                                            class="btn btn-xs btn-primary"><?php echo e(__('Pay Now')); ?>(<span
                                                class="cinetpay-final-price"><?php echo e($plan->price); ?></span>)</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                

                
                <?php if(isset($admin_payment_setting['is_fedapay_enabled']) && $admin_payment_setting['is_fedapay_enabled'] == 'on'): ?>
                <div id="fedapay_payment" class="card shadow-none rounded-0 border-bottom">
                    <form role="form" action="<?php echo e(route('plan.pay.with.fedapay')); ?>" method="post" id="fedapay-payment-form" class="w3-container w3-display-middle w3-card-4">
                        <?php echo csrf_field(); ?>
                    <div class="card-header">
                        <h5><?php echo e(__('Fedapay')); ?></h5>
                        <p class="text-sm text-muted"><?php echo e(__('Details about your plan fedapay payment')); ?></p>
                    </div>
                    <div class="card-body">
                                <div class="row mt-3">
                                    <div class="row mt-3">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="fedapay_coupon"
                                                    class="form-label text-dark"><?php echo e(__('Coupon')); ?></label>
                                                <input type="text" id="fedapay_coupon" name="coupon"
                                                    class="form-control coupon" data-from="fedapay"
                                                    placeholder="<?php echo e(__('Enter Coupon Code')); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 coupon-apply-btn mt-4">
                                            <div class="form-group apply-fedapay-btn-coupon">
                                                <a href="#" class="btn btn-primary align-items-center apply-coupon"
                                                    data-from="fedapay"><?php echo e(__('Apply')); ?></a>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right fedapay-coupon-tr" style="display: none">
                                            <b><?php echo e(__('Coupon Discount')); ?></b> : <b class="fedapay-coupon-price"></b>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 my-2 px-2">
                                    <div class="text-end">
                                        <input type="hidden" name="plan_id" id="plan_id"
                                            value="<?php echo e(\Illuminate\Support\Facades\Crypt::encrypt($plan->id)); ?>">

                                        <button type="submit" data-from="fedapay" value="<?php echo e(__('Pay Now')); ?>" id="pay_with_fedapay"
                                            class="btn btn-xs btn-primary" ><?php echo e(__('Pay Now')); ?>(<span class="fedapay-final-price"><?php echo e($plan->price); ?></span>)</button>
                                    </div>
                                </div>

                    </div>
                </form>
                </div>
            <?php endif; ?>
            

            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/yuri/www/adv-flow/main-file/resources/views/payment.blade.php ENDPATH**/ ?>