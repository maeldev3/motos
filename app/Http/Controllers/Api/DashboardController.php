<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;


class DashboardController extends Controller
{

    public function __construct(
        private readonly DashboardService $service
    ) {
    }



    /**
     * KPI Dashboard
     */
    public function index(Request $request)
    {

        $dates = $this->dateRange($request);


        return response()->json([

            'success' => true,

            'data' => $this->service->kpis(
                $dates['start'],
                $dates['end']
            )

        ]);

    }





    /**
     * Graphiques Dashboard
     */
    public function graphiques(Request $request)
    {

        $dates = $this->dateRange($request);


        return response()->json([

            'success' => true,

            'data' => $this->service->graphiques(
                $dates['start'],
                $dates['end']
            )

        ]);

    }





    /**
     * Revenus par moto
     */
    public function revenusMotos(Request $request)
    {

        $dates = $this->dateRange($request);


        return response()->json([

            'success' => true,

            'data' => $this->service->revenusMotos(
                $dates['start'],
                $dates['end']
            )

        ]);

    }






    /**
     * Dépenses par moto
     */
    public function depensesMotos(Request $request)
    {

        $dates = $this->dateRange($request);


        return response()->json([

            'success' => true,

            'data' => $this->service->depensesMotos(
                $dates['start'],
                $dates['end']
            )

        ]);

    }





    /**
     * Bénéfices par moto
     */
    public function beneficesMotos(Request $request)
    {

        $dates = $this->dateRange($request);


        return response()->json([

            'success' => true,

            'data' => $this->service->beneficesMotos(
                $dates['start'],
                $dates['end']
            )

        ]);

    }





    /**
     * Rentabilité depuis création
     */
    public function rentabiliteMotos()
    {

        return response()->json([

            'success' => true,

            'data' => $this->service->rentabiliteMotos()

        ]);

    }






    /**
     * Conducteurs en retard
     */
    public function retardsPaiement()
    {
        return response()->json([
    
            "success"=>true,
    
            "data"=>$this->service->retardsPaiement()
    
        ]);
    }






    /**
     * Gestion des filtres dates
     */
    private function dateRange(Request $request): array
    {

        return match ($request->get('period', 'month')) {


            'today' => [

                'start' => now()->startOfDay(),

                'end' => now()->endOfDay()

            ],



            'week' => [

                'start' => now()->startOfWeek(),

                'end' => now()->endOfWeek()

            ],



            'year' => [

                'start' => now()->startOfYear(),

                'end' => now()->endOfYear()

            ],




            'custom' => [

                'start' => Carbon::parse(
                    $request->start_date
                )->startOfDay(),


                'end' => Carbon::parse(
                    $request->end_date
                )->endOfDay()

            ],




            default => [

                'start' => now()->startOfMonth(),

                'end' => now()->endOfMonth()

            ]

        };

    }

}