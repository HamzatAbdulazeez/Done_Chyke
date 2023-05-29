@extends('layouts.admin_frontend')

@section('page-content')
<div class="content-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="mb-3">Transactions</h4>
                        <p class="mb-0">All transactions in one place</p>
                    </div>
                    <a href="#" data-toggle="modal" data-target="#fund-account" class="btn btn-primary add-list"><i class="las la-plus mr-3"></i>Fund Account</a>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="table-responsive rounded mb-3">
                    <table class="data-table table mb-0 tbl-server-info">
                        <thead class="bg-white text-uppercase">
                            <tr class="ligth ligth-data">
                                <th>S/N</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Reference</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="ligth-body">
                            @foreach($transactions as $transaction)
                            <tr>
                                <td>
                                    {{$loop->iteration}}
                                </td>
                                <td>
                                    @if (App\Models\User::where('id', $transaction->user_id)->exists())
                                    <p>{{App\Models\User::find($transaction->user_id)->account_type}}</p>
                                    {{App\Models\User::find($transaction->user_id)->name}}
                                    @else
                                        <b>{{ 'USER DELETED' }}</b> 
                                    @endif
                                </td>
                                <td>₦{{number_format($transaction->amount, 2)}}</td$transaction->
                                <td>{{$transaction->reference}}</td>
                                <td>{{$transaction->created_at->toDayDateTimeString()}}</td>
                                <td>
                                    @if($transaction->status == 'Top Up')
                                    <span class="badge bg-success">{{$transaction->status}}</span>
                                    @elseif($transaction->status == 'Expense')
                                    <span class="badge bg-danger">{{$transaction->status}}</span>
                                    @else
                                    <span class="badge bg-success">{{$transaction->status}}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Page end  -->
    </div>
</div>
@endsection