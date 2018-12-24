<h1>Assessment Marviq</h1>

<div>
    <table>
        <tr>
            <th>Machine</th>
            <th>TotalNet</th>
            <th>PercentageScrap</th>
            <th>Status</th>
            <th>Downtime</th>
            <th>Overall Equipment Efficiency</th>
        </tr>

        <?php
        foreach ($dashboardArray as $machine) {

            /*    temperature calculations: if the temperature has been above 100C, its a fatal. If it has been 85 degrees thrice in a row, it gets a warning status.  */

        $counter = 0;
        $status = 'good';

        foreach ($machine['Temperature'] as $temperature) {

            if ($temperature->value > 100) {
                $status = 'fatal';
            } else if ($temperature->value > 85) {
                $counter = $counter + 1;
            } else {
                $counter = 0;
            };

            if ($counter >= 4) {
                if ($status = 'good') {
                    $status = 'warning';
                }
            }
        }
        ?>
        <tr>

            <td><?php echo $machine['Machine_name'] ?></td>

            {{--Total Net Production--}}
            <td><?php echo $machine['TotalPercentageAndTotalNet'][0]->totalNetValue?></td>

            {{--Percentage scrap--}}
            <td><?php echo round($machine['TotalPercentageAndTotalNet'][0]->percentage, 2); echo '%';?></td>

            {{--status--}}
            <td><?php echo $status ?></td>

            {{--downtime--}}
            <td><?php if($machine['LastRun'][0]->isrunning === 0)

            /* because there is no endpoint for the last downtime,
            I added the length of the last downtime up till 00:00 the next day,
            provided the machine was 'down' on it's last entry */

            {$totalMinuteDowntime = $machine['LastRun'][0]->minutes + $machine['TotalDowntime'][0]->difference;}

            else {$totalMinuteDowntime = $machine['TotalDowntime'][0]->difference;}

            $percentageDowntime = ($totalMinuteDowntime / 1440) * 100;
            echo round($percentageDowntime, 2); echo '%';?></td>

            {{--OEE Calculations--}}

            <?php

                $totalUptime = 1440 - $totalMinuteDowntime;

                $normproduction = 720000;
                $normUptimeMinutes = 960;

            $performance = $machine['TotalPercentageAndTotalNet'][0]->totalNetValue / $normproduction;
            $availability = $totalUptime / $normUptimeMinutes;
            $quality = $machine['TotalPercentageAndTotalNet'][0]->totalNetValue / $machine['TotalPercentageAndTotalNet'][0]->grossProduction;

            $OEE = $performance * $availability * $quality * 100;
            ?>
            <td><?php echo round($OEE, 2); echo "%"; ?></td>


        </tr>

        <?php } ?>
    </table>


    {{-- production per hour --}}


<?php
foreach ($dashboardArray as $machine) { ?>

    <table>
        <thead><th><?php echo $machine['Machine_name'] ?></th></thead>
        <tr>
            <th>Hour</th>
            <th>NetProduction</th>
        </tr>



        <?php foreach ($machine['netProductionHourly'] as $netProductionHourly) { ?>
        <tr>
        <td><?php echo $netProductionHourly->productionHour; ?></td>

        <td><?php echo $netProductionHourly->hourlyNetProduction; ?></td>
        </tr>
        <?php } ?>

    </table>
    <?php } ?>
</div>