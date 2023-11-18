<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceTransaction;

class ServiceTransactionController extends Controller
{
    public function show(ServiceTransaction $serviceTransaction)
    {
        return view('print-service-transaction', compact('serviceTransaction'));
    }
}
