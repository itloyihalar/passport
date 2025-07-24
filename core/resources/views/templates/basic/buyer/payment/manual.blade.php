@extends($activeTemplate.'layouts.buyer_master')

@section('content')
    <div class="container">
        <div class="row justify-content-center my-60">
            <div class="col-xxl-8 col-lg-10 col-md-10">
                <div class="card custom--card">
                    <div class="card-body  ">
                        <form action="{{ route('buyer.deposit.manual.update') }}" method="POST" class="disableSubmission" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-primary">
                                        <p class="mb-0"><i class="las la-info-circle"></i> @lang('You are requesting') <b>{{ showAmount($data['amount'])  }}</b> @lang('to deposit.') @lang('Please pay')
                                            <b>{{showAmount($data['final_amount'],currencyFormat:false) .' '.$data['method_currency'] }} </b> @lang('for successful payment.')</p>
                                    </div>

                                    <div class="mb-3">@php echo  $data->gateway->description @endphp</div>

                                </div>

                                <x-viser-form identifier="id" identifierValue="{{ $gateway->form_id }}" />

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn--base w-100">@lang('Pay Now')</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
