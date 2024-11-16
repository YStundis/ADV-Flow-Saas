@extends('layouts.app')

@section('page-title', __('Expense'))

@section('action-button')
    @can('create expense')
        <div class="text-sm-end d-flex all-button-box justify-content-sm-end">
            <a href="{{ route('expenses.export') }}" class="btn btn-sm btn-primary mx-1" data-bs-toggle="tooltip"
                data-title=" {{ __('Export') }}" title="{{ __('Export') }}">
                <i class="ti ti-file-export"></i>
            </a>
            <a href="#" class="btn btn-sm btn-primary mx-1" data-ajax-popup="true" data-size="md" data-title="Add Expense"
                data-url="{{ route('expenses.create') }}" data-toggle="tooltip" title="{{ __('Create New Expense') }}"
                data-bs-original-title="{{ __('Create New Expense') }}" data-bs-placement="top" data-bs-toggle="tooltip">
                <i class="ti ti-plus"></i>
            </a>
        </div>
    @endcan

@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Expense') }}</li>
@endsection

@section('content')
    <div class="row p-0">
        <div class="col-xl-12">
            <div class="card shadow-none">
                <div class=" card-body table-border-style">
                    <h5></h5>
                    <div class="table-responsive">
                        <table class="table dataTable data-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Case') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Particulars') }}</th>
                                    <th>{{ __('Money') }}</th>
                                    <th>{{ __('Payment Method') }}</th>
                                    <th>{{ __('Team Member') }}</th>
                                    <th width="100px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($expenses as $expense)
                                    <tr>
                                        <td><a href="#" class="btn btn-sm"
                                                data-url="{{ route('expenses.show', $expense->id) }}" data-size="md"
                                                data-ajax-popup="true" data-title="{{ __('View Expense') }}">
                                                {{ App\Models\Cases::getCasesById($expense->case) }}
                                            </a></td>
                                        <td>{{ $expense->date }}</td>
                                        <td>{{ $expense->particulars }}</td>
                                        <td>{{ $expense->money }}</td>
                                        <td>{{ App\Models\Utility::getPaymentMethodName($expense->method) }}</td>
                                        <td>{{ App\Models\User::getTeams($expense->member) }}</td>
                                        <td>
                                            @can('view expense')
                                                <div class="action-btn bg-light-secondary ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm d-inline-flex align-items-center "
                                                        data-url="{{ route('expenses.show', $expense->id) }}" data-size="md"
                                                        data-ajax-popup="true" data-title="{{ __(' View Expense') }}"
                                                        title="{{ __('View Expense') }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"><i class="ti ti-eye "></i></a>
                                                </div>
                                            @endcan
                                            @can('edit expense')
                                                <div class="action-btn bg-light-secondary ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm d-inline-flex align-items-center "
                                                        data-url="{{ route('expenses.edit', $expense->id) }}" data-size="md"
                                                        data-ajax-popup="true" data-title="{{ __('Edit Expense') }}"
                                                        title="{{ __('Edit Expense') }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"><i class="ti ti-edit "></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('delete expense')
                                                <div class="action-btn bg-light-secondary ms-2">
                                                    <a href="#"
                                                        class="mx-3 btn btn-sm d-inline-flex align-items-center bs-pass-para"
                                                        data-confirm="{{ __('Are You Sure?') }}"
                                                        data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                        data-confirm-yes="delete-form-{{ $expense->id }}"
                                                        title="{{ __('Delete') }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top">
                                                        <i class="ti ti-trash"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            {!! Form::open([
                                                'method' => 'DELETE',
                                                'route' => ['expenses.destroy', $expense->id],
                                                'id' => 'delete-form-' . $expense->id,
                                            ]) !!}
                                            {!! Form::close() !!}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('custom-script')
    <script>
        $(document).ready(function() {
            var case_id = $('#case').val();
            getAdvocte(case_id);
        });
        $(document).on('change', 'select[name=case]', function() {

            var case_id = $(this).val();
            // console.log(case_id);
            getAdvocte(case_id);
        });

        function getAdvocte(case_id) {
            if (case_id) {

                $.ajax({
                    url: "{{ route('get.advocate') }}",
                    type: "POST",
                    data: {
                        case_id: case_id,
                        _token: "{{ csrf_token() }}"
                    },
                    dataType: "json",
                    success: function(result) {
                        // console.log(result.data);

                        $('#member').empty();

                        $("#advocate_div").html('');
                        $('#advocate_div').append(
                            '<select class="form-control multi-select" name="member" id="member"></select>'
                        );

                        // $('#member').append(
                        //     '<option value="">{{ __('Select Assign to') }}</option>');

                        $.each(result.data, function(key, value) {
                            // console.log(key, value);
                            $('#member').append('<option value="' + key + '">' +
                                value + '</option>');
                        });

                        var multipleCancelButton = new Choices('#member', {
                            removeItemButton: true,
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                        $("#member").append(
                            '<option value="">{{ __('Error fetching advocates') }}</option>'
                        );
                    }
                });
            }
        }
    </script>
@endpush
