<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Expenses;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountantController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth','verified']);
    }

    public function expenses_view(Request $request)
    {
        if($request->start_date == null && $request->end_date == null && $request->source == null)
        {
            $expenses = Expenses::latest()->where('user_id', Auth::user()->id)->get();
        } elseif($request->start_date !== null && $request->end_date !== null && $request->source == null)
        {
            $expenses = Expenses::latest()->where('user_id', Auth::user()->id)->whereBetween('date', [$request->start_date, $request->end_date])->get();
        } elseif($request->start_date == null && $request->end_date == null && $request->source !== null)
        {
            $expenses = Expenses::latest()->where('user_id', Auth::user()->id)->where('payment_source', $request->source)->get();
        } else {
            $expenses = Expenses::latest()->where('user_id', Auth::user()->id)->where('payment_source', $request->source)->whereBetween('date', [$request->start_date, $request->end_date])->get();
        }

        return view('accountant.view_expenses', [
            'expenses' => $expenses,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'source' => $request->source
        ]);
    }

    public function expenses_add()
    {
        return view('accountant.add_expenses');
    }
    
    public function expenses_post(Request $request)
    {
        $this->validate($request, [
            'payment_source' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'supplier' => ['required', 'numeric'],
            'supplier_additional_field' => ['nullable', 'string'],
            'collected_by' => ['required', 'string'],
            'date' => ['required', 'date'],
        ]);

        if($request->supplier <> 0)
        {
            $supplier = User::find($request->supplier);

            if(!$supplier)
            {
                return back()->with([
                    'type' => 'danger',
                    'message' => 'Supplier not found in our database.'
                ]);
            }
            $supply = $supplier->id;
        } else {
            $supply = null;
        }

        if (request()->hasFile('receipt')) 
        {
            $this->validate($request, [
                'receipt' => 'required|mimes:jpeg,png,jpg'
            ]);
            
            $filename = request()->receipt->getClientOriginalName();
            request()->receipt->storeAs('expenses_receipts', $filename, 'public');

            $expense = Expenses::create([
                'user_id' => Auth::user()->id,
                'supplier' => $supply,
                'payment_source' => $request->payment_source,
                'category' => $request->category,
                'description' => $request->description,
                'amount' => $request->amount,
                'date' => $request->date,
                'recurring_expense' => $request->recurring_expense,
                'supplier_additional_field' => $request->supplier_additional_field,
                'collected_by' => $request->collected_by,
                'receipt' => '/storage/expenses_receipts/'.$filename
            ]);

        } else {
            $expense = Expenses::create([
                'user_id' => Auth::user()->id,
                'supplier' => $supply,
                'payment_source' => $request->payment_source,
                'category' => $request->category,
                'description' => $request->description,
                'amount' => $request->amount,
                'date' => $request->date,
                'supplier_additional_field' => $request->supplier_additional_field,
                'collected_by' => $request->collected_by,
                'recurring_expense' => $request->recurring_expense
            ]);
        }

        Transaction::create([
            'user_id' => Auth::user()->id,
            'accountant_process_id' => $expense->id,
            'amount' => $expense->amount,
            'reference' => config('app.name'),
            'status' => 'Expense'
        ]);

        return back()->with([
            'alertType' => 'success',
            'back' => route('expenses.view'),
            'message' => 'Expense added successfully!'
        ]);
    }

    public function daily_balance()
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        $balance = Balance::whereDate('date', Carbon::now()->format('Y-m-d'))->first();
        $totalClosingBalance = Balance::whereDate('date', $yesterday)->sum('closing_balance') ?? 0;

        if($balance)
        {
            $starting_balance = $balance->starting_balance + $totalClosingBalance;
            $closing_balance = $balance->closing_balance;

        } else {
            $starting_balance = null;
            $closing_balance = null;
        }

        return view('accountant.daily_balance')->with([
            'starting_balance' => $starting_balance,
            'closing_balance' => $closing_balance
        ]);

        // $date = Balance::whereDate('date', Carbon::now()->format('Y-m-d'))->first();

        // if($date)
        // {
        //     $starting_balance = $date->starting_balance;
        //     $additional_income = $date->additional_income;
        //     $amount_used = $date->amount_used;
        //     $remaining_balance = $date->remaining_balance;
        // } else {
        //     $starting_balance = null;
        //     $additional_income = null;
        //     $amount_used = null;
        //     $remaining_balance = null;
        // }

        // return view('accountant.daily_balance')->with([
        //     'starting_balance' => $starting_balance,
        //     'additional_income' => $additional_income,
        //     'amount_used' => $amount_used,
        //     'remaining_balance' => $remaining_balance
        // ]);
    }

    public function daily_balance_add(Request $request)
    {
        $this->validate($request, [
            'starting_balance' => ['required', 'numeric']
        ]);

        $balance = Balance::get();

        if($balance->count() > 0)
        {
            $date = Balance::whereDate('date', Carbon::now()->format('Y-m-d'))->first();

            if($date)
            {
                if($request->starting_balance == $date->starting_balance)
                {
                    $date->update([
                        'starting_balance' => $request->starting_balance,
                    ]);

                    return back()->with([
                        'alertType' => 'success',
                        'message' => 'Daily starting balance updated successfully.'
                    ]);
                }

            } else {
                Balance::create([
                    'starting_balance' => $request->starting_balance,
                    'date' => Carbon::now()->format('Y-m-d')
                ]);

                return back()->with([
                    'alertType' => 'success',
                    'message' => 'Daily starting balance added successfully.'
                ]);
            }
        }

        Balance::create([
            'starting_balance' => $request->starting_balance,
            'date' => Carbon::now()->format('Y-m-d')
        ]);

        return back()->with([
            'alertType' => 'success',
            'message' => 'Daily starting balance added successfully.'
        ]);
    }
}
