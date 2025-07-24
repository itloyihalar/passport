<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bid;

class ManageBidController extends Controller
{

    public function jobBids($id = 0)
    {
        $pageTitle = "All Bids";
        $bids = Bid::with(['job', 'user', 'project', 'buyer'])
            ->when($id, function ($query) use ($id) {
                $query->where('job_id', $id);
            })->searchable(['job:title', 'user:username', 'buyer:username'])->filter(['status'])->dateFilter()->orderByDesc('id')->paginate(getPaginate());

        return view('admin.jobs.bid', compact('pageTitle', 'bids'));
    }
}
