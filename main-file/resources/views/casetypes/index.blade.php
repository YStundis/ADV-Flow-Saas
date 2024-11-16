@extends('layouts.app')

@section('page-title', __('Case Type'))

@section('action-button')
    @can('create casetype')
    <div class="text-sm-end d-flex all-button-box justify-content-sm-end">
        <a href="#" class="btn btn-sm btn-primary mx-1" data-ajax-popup="true" data-size="md" data-title="Add Case Type"
            data-url="{{ route('casetype.create') }}" data-toggle="tooltip" title="{{ __('Create New Case Type') }}">
            <i class="ti ti-plus"></i>
        </a>
    </div>
    @endcan
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Case Type') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header card-body table-border-style">
                    <h5></h5>
                    <table class="table dataTable data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th width="100px">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($casetype as $type)
                                <tr>
                                    <td>{{ __($type->name) }}</td>
                                    <td>
                                        @can('edit casetype')
                                        <div class="action-btn bg-light-secondary ms-2">
                                            <a href="#" class="mx-3 btn btn-sm d-inline-flex align-items-center "
                                                data-url="{{ route('casetype.edit', $type->id) }}" data-size="md"
                                                data-ajax-popup="true" data-title="{{ __('Edit Case Type') }}"
                                                title="{{ __('Edit') }}" data-bs-toggle="tooltip"
                                                data-bs-placement="top">
                                                <i class="ti ti-edit "></i></a>
                                        </div>
                                        @endcan
                                        @can('delete casetype')
                                        <div class="action-btn bg-light-secondary ms-2">
                                            <a href="#"
                                                class="mx-3 btn btn-sm d-inline-flex align-items-center bs-pass-para"
                                                data-confirm="{{ __('Are You Sure?') }}"
                                                data-confirm-yes="delete-form-{{ $type->id }}"
                                                title="{{ __('Delete') }}" data-bs-toggle="tooltip"
                                                data-bs-placement="top">
                                                <i class="ti ti-trash"></i>
                                            </a>
                                        </div>
                                        @endcan
                                        {!! Form::open([
                                            'method' => 'DELETE',
                                            'route' => ['casetype.destroy', $type->id],
                                            'id' => 'delete-form-' . $type->id,
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
@endsection
