<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\domain\PaymentSchedule;
use App\domain\Policy;
use App\domain\Customer;
use Carbon\Carbon;
use App\Http\Requests\ScheduleRequest;

class PaymentSchedulesController extends Controller
{   
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index(){

    }

    public function store(Policy $policy,ScheduleRequest $request){

    	//$days=$policy->expiry_date->diffInDays($policy->effective_date);

    	//$daysPerMonth=$days/$request['remaining-payments'];
        if($policy->checkPaymentSchedule()){
          $deletedRows=PaymentSchedule::where('policy_id',$policy->id)->delete();  
        }

        if($policy->total_premium<=0){
         return redirect()->back()->withErrors('Add Endorsement First');
        }
        if($request['is_pay_daily']=="yes"){
           

            PaymentSchedule::create([
            'policy_id'=>$policy->id,
            'due_date'=>$policy->expiry_date,
            'amount'=>$policy->total_premium,
            'amount_paid'=>0,
            'status'=>'open'
            ]);

        }else{
            $premium=$policy->total_premium/$request['remaining-payments'];

            $dueDate=$policy->effective_date;

            for($i=0;$i<$request['remaining-payments'];$i++){

             PaymentSchedule::create([
                'policy_id'=>$policy->id,
                'due_date'=>$dueDate,
                'amount'=>$premium,
                'amount_paid'=>0,
                'status'=>'open'
                ]);
              $dueDate=$dueDate->addMonths(1);

            }
    

        }
            
        $customer = $policy->customer;
        //session()->flash('payment-schedules-create-message','Schedules created successfully');
        //return view('policies.index',compact('customer'));
        return redirect()->action('CustomerPoliciesController@index',['customer'=>$customer])->with('message','new Payments Generated Created');

    	
    }

    public function dueForm(){

        return view('payments.due');
    }

    public function due(Request $request){

        $this->validate($request,[
          'start_date'=>'required|date|before:end_date',
          'end_date'=>'required|date|after:start_date',
            ]);

        $start_date=Carbon::createFromFormat('m/d/Y',$request['start_date']);
        $end_date=Carbon::createFromFormat('m/d/Y',$request['end_date']);

        
        
        $paymentSchedules=PaymentSchedule::whereBetween('due_date',[$start_date,$end_date])
        ->where('status','open')->get();
        //dd($paymentSchedules);

        return view('payments.due',['paymentSchedules'=>$paymentSchedules]);
        
        
    }
    public function CustomerDue(Customer $customer,Request $request){

        $date=Carbon::createFromFormat('m/d/Y',$request['date']);

        $this->validate($request,[
            'date'=>'required|date'
            ]);


        $paymentSchedules=DB::table('payment_schedules')->select('*','payment_schedules.id as pid','payment_schedules.amount as pamount')
                ->join('policies','payment_schedules.policy_id','=','policies.id')
                ->join('customers','policies.customer_id','=','customers.id')
                ->join('vehicles','policies.vehicle_id','=','vehicles.id')
                ->whereDate('payment_schedules.due_date','<',$date)
                ->where('payment_schedules.status','open')
                ->where('customers.id',$customer->id)
                ->get();

            return view('customers.show',['customer'=>$customer,'paymentSchedules'=>$paymentSchedules]);

    }
}
