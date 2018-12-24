<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function show($machine_name)
    {

        $netProductionHourly = DB::select("select 
                                                     (a.productionValue - b.scrapValue) as hourlyNetProduction,
                                                      a.productionHour
                                                  from
                                                           (select HOUR(datetime_from) as productionHour,
                                                        sum(value) as productionValue
                                                        from production
                                                        WHERE machine_name = ? 
                                                        AND (datetime_from 
                                                        BETWEEN '2018-01-07 00:00:00' AND '2018-01-07 23:55:00')
                                                        AND variable_name = 'PRODUCTION' GROUP BY HOUR(datetime_from)) a,
                                                        
                                                           (select HOUR(datetime_from) as scrapHour,
                                                        sum(value) as scrapValue
                                                        from production
                                                        WHERE machine_name = ? 
                                                        AND (datetime_from 
                                                        BETWEEN '2018-01-07 00:00:00' AND '2018-01-07 23:55:00')
                                                        AND variable_name = 'SCRAP' GROUP BY HOUR(datetime_from)) b
                                      
                                                 where a.productionHour = b.scrapHour order by a.productionHour", [$machine_name, $machine_name]);

        $TotalPercentageAndTotalNet = DB::select("select 
                                                  (b.scrapValue/a.productionValue * 100) as percentage,
                                                  (a.productionValue - b.scrapValue) as totalNetValue,
                                                  a.productionValue as grossProduction
                                              from
                                                       (select
                                                    sum(value) as productionValue
                                                    from production
                                                    WHERE machine_name = ?
                                                    AND (datetime_from 
                                                    BETWEEN '2018-01-07 00:00:00' AND '2018-01-07 23:55:00')
                                                    AND variable_name = 'PRODUCTION') a,
                                                    
                                                       (select 
                                                    sum(value) as scrapValue
                                                    from production
                                                    WHERE machine_name = ?
                                                    AND (datetime_from 
                                                    BETWEEN '2018-01-07 00:00:00' AND '2018-01-07 23:55:00')
                                                    AND variable_name = 'SCRAP') b", [$machine_name, $machine_name]);

        $TotalDowntime = DB::select("SELECT sum(TIMESTAMPDIFF
                                                    (MINUTE, 
                                                            (SELECT b.datetime 
                                                            FROM Runtime b 
                                                            WHERE b.id < a.id 
                                                            AND machine_name = ? 
                                                            AND (datetime 
                                                            BETWEEN '2018-01-07 00:00:00' 
                                                            AND '2018-01-07 23:55:00') 
                                                            ORDER BY id DESC  LIMIT 1 ), 
                                                    a.datetime)) 
                                              AS difference
                                              FROM Runtime a
                                              WHERE machine_name = ?
                                              AND (datetime 
                                              BETWEEN '2018-01-07 00:00:00' AND '2018-01-08 00:00:00')
                                              AND isrunning = 1
                                              ORDER BY a.id", [$machine_name, $machine_name]);

        $lastRun = DB::select("SELECT TIMESTAMPDIFF
                                            (MINUTE, datetime, '2018-01-08 00:00:00') 
                                            as minutes, 
                                            isrunning 
                                            FROM Runtime 
                                            WHERE machine_name = ? 
                                            ORDER BY id DESC LIMIT 1", [$machine_name]);

        $temperature = DB::select("SELECT value 
                                          FROM production 
                                          WHERE machine_name = ?
                                          AND variable_name = 'CORE TEMPERATURE'
                                          AND (datetime_from 
                                          BETWEEN '2018-01-07 00:00:00' AND '2018-01-07 23:55:00')
                                          ORDER BY datetime_from", [$machine_name]);

        return ['netProductionHourly' => $netProductionHourly,
                'TotalPercentageAndTotalNet' => $TotalPercentageAndTotalNet,
                'TotalDowntime' => $TotalDowntime,
                'Machine_name' => $machine_name,
                'LastRun' => $lastRun,
                'Temperature' => $temperature,
        ];

    }

    public function Dashboard()
    {

        $dashboardArray = [
            'machine1' => $this->show('4x2 brick mould'),
            'machine2' => $this->show('3x2 brick mould'),
            'machine3' => $this->show('2x2 brick mould')
        ];

        $machines = [
            'dashboardArray' => $dashboardArray
        ];

        return view('machines', $machines);

    }

}
