<?php

namespace App\Http\Controllers;

use App\Models\Member\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Display a listing of the members.
     */
    public function index()
    {
        $members = Member::query()
            ->latest()
            ->paginate(10);

        return view('admin.members.index', [
            'members' => $members,
        ]);
    }
}
