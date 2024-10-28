<?php
// generate_roster.php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize input data
    $department = htmlspecialchars($_POST['department']);
    $month = $_POST['month'];
    $num_staff = intval($_POST['num_staff']);
    $staff_names = $_POST['staff_names'];

    // Ensure the number of staff matches the entered names
    if (count($staff_names) != $num_staff) {
        die("Number of staff names entered does not match the specified number of staff.");
    }

    // Define the patterns
    $patterns = [
        ['P', 'P', 'P', 'O', 'O'], // Pattern A
        ['P', 'O', 'O', 'P', 'P'], // Pattern B
        ['P', 'P', 'O', 'O', 'P']  // Pattern C
    ];

    // Get year and month
    $year = date('Y', strtotime($month));
    $month_num = date('m', strtotime($month));

    // Get all dates in the month (Monday to Friday)
    $start_date = new DateTime("$year-$month_num-01");
    $end_date = clone $start_date;
    $end_date->modify('last day of this month');

    // Create an array of weeks, each week contains Monday to Friday
    $weeks = [];
    $current_date = clone $start_date;

    // Move to the first Monday on or after the 1st
    if ($current_date->format('N') != 1) {
        $current_date->modify('next monday');
    }

    while ($current_date <= $end_date) {
        $week = [];
        for ($i = 0; $i < 5; $i++) { // Monday to Friday
            $day = clone $current_date;
            $day->modify("+$i day");
            if ($day->format('m') == $month_num) {
                $week[] = $day;
            }
        }
        $weeks[] = $week;
        $current_date->modify('+1 week');
    }

    // Prepare the roster
    $roster = [];

    // Assign patterns randomly to each staff for each week
    foreach ($staff_names as $staff) {
        $roster[$staff] = [];
        foreach ($weeks as $week) {
            // Randomly select a pattern
            $pattern = $patterns[array_rand($patterns)];
            // Assign 'P' to Monday regardless
            $pattern[0] = 'P';
            $roster[$staff][] = $pattern;
        }
    }

    // Prepare Word document content
    $output = "<html>
    <head><meta charset='UTF-8'></head>
    <body>";
    $output .= "<h1> <Center> POLICE HEALTH MAINTENANCE LIMITED </Center> </h1>";
    $output .= "<h2><Center> DUTY ROSTER FOR $department </Center></h2>";
    $output .= "<p>MONTH: " . date('F Y', strtotime($month)) . "</p>";
    $output .= "<p>DEPARTMENT: $department</p>";

    // Create header with staff names
    $output .= "<table border='1' cellpadding='5' cellspacing='0' style='width:100%;'>
            <tr>
                <th>Day</th>";

    foreach ($staff_names as $staff) {
        // Use only first name
        $first_name = explode(' ', $staff)[0];
        $output .= "<th>$first_name</th>";
    }

    $output .= "</tr>";

    // Populate the table with roster data, divided into weeks
    foreach ($weeks as $week_index => $week) {
        // Print the week header in bold
        $start_day = $week[0]->format('jS F');
        $end_day = $week[count($week) - 1]->format('jS F');
        $output .= "<tr><td colspan='" . ($num_staff + 1) . "'><strong>WEEK " . ($week_index + 1) . " ($start_day – $end_day)</strong></td></tr>";

        foreach ($week as $day) {
            $output .= "<tr>";
            // Show the day of the week and date
            $output .= "<td>" . $day->format('l (d/m/Y)') . "</td>";
            foreach ($roster as $staff => $weeks_patterns) {
                // Get the current week's pattern for the staff
                $pattern = $weeks_patterns[$week_index];

                // Get the day index (Monday is 0, Tuesday is 1, etc.)
                $day_index = $day->format('N') - 1;

                // Show staff name or "Remotely"
                if ($pattern[$day_index] == 'P') {
                    $first_name = explode(' ', $staff)[0];
                    $output .= "<td>$first_name</td>";
                } else {
                    $output .= "<td>Remotely</td>";
                }
            }
            $output .= "</tr>";
        }
    }

    $output .= "</table>";
    $output .= "</body></html>";

    // Send the generated document as a Word file
    $word_filename = "duty_roster_" . strtolower(str_replace(' ', '_', $department)) . "_" . date('F_Y', strtotime($month)) . ".doc";
    header("Content-type: application/vnd.ms-word");
    header("Content-Disposition: attachment;Filename=$word_filename");
    echo $output;
    exit();
}
?>
