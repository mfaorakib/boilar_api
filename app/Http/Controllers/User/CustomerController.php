<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\CustomerResource;
use App\Models\User\Customer;
use App\Models\User\CustomerAddress;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use App\Rules\Mobile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Lang;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class CustomerController extends Controller
{
    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('view customer');

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);
        $status = $request->query('status');
        $from_date = $request->query('fromDate');
        $to_date = $request->query('toDate');

        $customers = Customer::query();
        if ($query) {
            $customers = $customers->where('name', 'like', '%' . $query . '%')
            ->orWhere('mobile', 'like', '%' . $query . '%')
            ->orWhere('email', 'like', '%' . $query . '%');
        }

        $statusList = explode(",",$status);
        if (isset($status) && count($statusList) === 1) {
            $customers = $customers->where('status', $statusList);
        };
        
        if (isset($to_date) && isset($from_date)) {
            $customers = $customers->whereBetween('created_at', [strval($from_date), strval($to_date)]);
        };

        if ($sortBy) {
            $customers = $customers->orderBy($sortBy, $direction);
        } else {
            $customers = $customers->latest();
        }

        if ($per_page === '-1') {
            $results = $customers->get();
            $customers = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $customers = $customers->paginate($per_page);
        }

        return CustomerResource::collection($customers);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
       
        $this->authorize('create customer');

        // begin database transaction
        DB::beginTransaction();
        try {
            $customer = Customer::query()->create($request->all());

            // commit database
            DB::commit();
            // return success message
            return response()->json($customer);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback database
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param $locale
     * @param Customer $customer
     * @return AnonymousResourceCollection|JsonResponse
     * @throws AuthorizationException
     */
    public function show($locale, Customer $customer)
    {
        App::setLocale($locale);

        $this->authorize('view customer');

        try {
            return new CustomerResource($customer);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getAddress(Request $request)
    {
        $this->authorize('view customer');

        $customerId = $request->query('customer_id');

        $customerAddress = CustomerAddress::latest();
        if ($customerId) {
            $customerAddress = $customerAddress->where('customer_id','=', $customerId);
        }

        return response()->json([
            'data' => $customerAddress->get()
        ]);
    }

    /**
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function updateProfile(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'name' => 'nullable',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'mobile' => ['nullable', new Mobile(), 'unique:customers,mobile,' . $customer->id],
        ]);
        DB::beginTransaction();
        try {
            $customer->update($request->all());

            DB::commit();
            // return success message
            return response()->json($customer);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback database
            DB::rollBack();
            // return failed message
            return response()->json([
                'error' => $exception->getMessage(),
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

     /**
     * Get all customers.
     * @param Request $request
      * @return JsonResponse
     */
    public function getCustomer(Request $request)
    {
        $this->authorize('view customer');
        $query = $request->query('query');

        $customers = DB::table('customers as c')
            ->where('c.name', 'like', '%' . $query . '%')
            ->orWhere('c.email', 'like', '%' . $query . '%')
            ->orWhere('c.mobile', 'like', '%' . $query . '%')
            ->where('c.status', '=', 'active')
            ->select('c.name', 'c.id', 'c.email', 
            'c.mobile', 'c.created_at', 'c.status',
            'c.avatar')
            ->get();

        return response()->json([
            'data' => $customers
        ]);
    }

     /**
     * Get customer getStatistic.
     * @param Request $request
      * @return JsonResponse
     */
    public function getStatistic(Request $request):JsonResponse
    {
        $this->authorize('view customer');
        $query = $request->query('query');
        $from_date = $request->query('fromDate');
        $to_date = $request->query('toDate');
        $total_customer = 0;
        $date_range = 'all';

        $total_today_new_customer = Customer::whereDate('created_at', Carbon::today())->count();

        if (isset($to_date) && isset($from_date)) {
            $total_customer = Customer::whereBetween('created_at', [strval($from_date), strval($to_date)])->count();
        } else $total_customer = Customer::count();

        if($from_date && $to_date) {
            $from_date = Carbon::parse($from_date)->format('d/m/Y');
            $to_date = Carbon::parse($to_date)->format('d/m/Y');

           $date_range = "$from_date to $to_date";
        }

        $last_thirty_days_customer = Customer::whereDate('created_at', '>', Carbon::now()->subDays(30))->count();
        $last_prev_month_customer = Customer::whereBetween('created_at', [strval(Carbon::now()->subDays(60)), strval(Carbon::now()->subDays(30))])->count();
        $total_customer_creation_progress = $last_thirty_days_customer - $last_prev_month_customer;
        
        if($total_customer_creation_progress>0) $total_customer_creation_progress = "+$total_customer_creation_progress";

        return response()->json([
            'today'=> Carbon::now()->isoFormat('D MMM Y'),
            'today_new_customer'=> $total_today_new_customer?:0,
            'last_thirty_days_new_customer'=> $last_thirty_days_customer?:0,
            'last_thirty_days_progress'=> "$total_customer_creation_progress"?:0,
            'total_customer'=> $total_customer?:0,
            'date_range_total_cusotmer'=> $date_range?:'all'
        ]);
    }
}
