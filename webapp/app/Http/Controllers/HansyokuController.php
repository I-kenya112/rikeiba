<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HansyokuController extends Controller
{
    public function ajaxSearch(Request $request)
    {
        $keyword = trim($request->get('q', ''));
        if ($keyword === '') {
            return response()->json([]);
        }

        $list = DB::table('ri_hansyoku')
            ->select(
                'HansyokuNum as id',
                'Bamei as name'
            )
            ->where('Bamei', 'LIKE', "{$keyword}%")
            ->orderBy('Bamei')
            ->limit(20)
            ->get();

        return response()->json($list);
    }
}
