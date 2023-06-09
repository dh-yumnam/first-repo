<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\WildlifePopulation;
use Illuminate\Http\Request;
use Redirect, Response;

class ChartJSController extends Controller
{
    public function showChart()
    {
        $population = WildlifePopulation::select(
            DB::raw("year(created_at) as year"),
            DB::raw("SUM(bears) as bears"),
            DB::raw("SUM(dolphins) as dolphins")
        )
            ->orderBy(DB::raw("YEAR(created_at)"))
            ->groupBy(DB::raw("YEAR(created_at)"))
            ->get();

        $res[] = ['Year', 'bears', 'dolphins'];
        foreach ($population as $key => $val) {
            $res[++$key] = [$val->year, (int)$val->bears, (int)$val->dolphins];
        }
        return view('common-ui.chart-js')
            ->with('population', json_encode($res));
    }
}
