<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\domain\Customer;
use App\domain\CreditPayment;
use App\domain\CreditReceipt;

class CreditPaymentsController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function create(Customer $customer){

    	return view('customers.creditpayments.create',['customer'=>$customer]);

    }

    public function store(Customer $customer,Request $request){

    	$this->validate($request,[
    		'amount'=>'required|numeric',
    		'transaction_date'=>'required',
            'place'=>'required',
            'description'=>'required'
    		]);
    	$creditPayment = new CreditPayment();
    	$creditPayment->amount=$request['amount'];
    	$creditPayment->transaction_date=Carbon::createFromFormat('m/d/Y',$request['transaction_date']);
        $creditPayment->place=$request['place'];
        $creditPayment->description=$request['description'];
        


    	$customer->creditPayments()->save($creditPayment);
        $creditReceipt=new CreditReceipt();
        $creditReceipt->amount=$creditPayment->amount;
        //$creditReceipt->receipt_no=$request['receipt_no'];

        $creditPayment->creditReceipt()->save($creditReceipt);


    	return redirect()->route('credits.index',['customer'=>$customer->id])->with('message','Payment Added');
    }
    public function edit(CreditPayment $creditPayment){
    	return view('customers.creditpayments.edit',['creditPayment'=>$creditPayment]);

    }
    public function update(CreditPayment $creditPayment,Request $request){

    	$this->validate($request,[
    		'transaction_date'=>'required',
    		'amount'=>'required|numeric',
            'place'=>'required'
    		]);

    	$creditPayment->transaction_date=Carbon::createFromFormat('m/d/Y',$request['transaction_date']);
    	$creditPayment->amount=$request['amount'];
        $creditPayment->from=$request['place'];
        $creditReceipt=$creditPayment->creditReceipt;
        $creditReceipt->amount=$creditPayment->amount;

    	$creditPayment->save();
        $creditReceipt->save();
    	return redirect()->route('credits.index',['customer'=>$creditPayment->customer->id])->with('message','Payment Edited');
    }
}
