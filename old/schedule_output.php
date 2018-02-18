<?php

if (isset($_REQUEST)) {
    require_once('schedule_options.php');

    $schedule_type = $_POST['schedule_type'];
    $faculty = $_POST['faculty'];
    $course = $_POST['course'];
    $group = $_POST['group'];
    $overlay_color = $_POST['overlay_color'];
    $bottom = $_POST['bottom'];
    $background_image_name = $_POST['background_image_name'];
    $width = $_POST['width'];
    $height = $_POST['height'];

    if (isset($_POST['group_add'])) {
        $group_add = $_POST['group_add'];
    }

    if ($background_image_name != 'none') { $image = 'url(/images/schedule/full/' . $background_image_name . ')'; } else { $image = 'none'; }

    if (isset($group_add)) {
        $schedule = json_decode(file_get_contents('http://' . $_SERVER['SERVER_NAME'] . '/schedule_api.php?schedule=' . $schedule_type . '&object=student&subdivision=' . $faculty . '&course=' . $course . '&group=' . $group . '&group_add=' . $group_add), true);
    } else {
        $schedule = json_decode(file_get_contents('http://' . $_SERVER['SERVER_NAME'] . '/schedule_api.php?schedule=' . $schedule_type . '&object=student&subdivision=' . $faculty . '&course=' . $course . '&group=' . $group), true);
    }

    $table['weeks'] = [];
    $days_counter = 0;
    foreach ($schedule['weeks']['first'] as $day) {
        $lessons_counter = 0;
        foreach ($day['lessons'] as $lesson) {
            $table['weeks'][0][$days_counter][$lessons_counter] = $lesson;
            $lessons_counter++;
        }
        $days_counter++;
    }
    $days_counter = 0;
    foreach ($schedule['weeks']['second'] as $day) {
        $lessons_counter = 0;
        foreach ($day['lessons'] as $lesson) {
            $table['weeks'][1][$days_counter][$lessons_counter] = $lesson;
            $lessons_counter++;
        }
        $days_counter++;
    }

    $schedule = $table;

    echo '<style>* { margin: 0; padding: 0; -webkit-box-sizing: border-box; -moz-box-sizing: border-box; box-sizing: border-box; } .screen { position: relative; width: ' . $width . '; height: ' . $height . '; overflow: hidden; } .image { position: absolute; width: 100%; height: 100%; background: center no-repeat; background-size: cover; background-image: ' . $image . '; transform: scale(1.1); filter: blur(5px); } .overlay { position: absolute; width: 100%; height: 100%; background: ' . $overlay_color . '; } table { font-family: "Roboto", sans-serif; position: absolute; bottom: ' . $bottom . '; width: 100%; border-collapse: collapse; table-layout: fixed; } table tr td { padding: 5px 10px; vertical-align: top; border-bottom: 1px solid rgba(255, 255, 255, 0.05); border-right: 1px solid rgba(255, 255, 255, 0.05); } table tr td:last-child { border-right: none; } table tr:first-child td { border-top: 1px solid rgba(255, 255, 255, 0.05); } table tr td span { overflow: hidden; } table tr .lesson { position: relative; } table tr .lesson span { display: block; white-space: pre; } table tr .lesson .subject { font-size: 13px; margin-bottom: 2px; color: #fff; } table tr .lesson .time { font-size: 11px; color: rgba(255, 255, 255, 0.7); } table tr .lesson .room { font-size: 11px; color: rgba(255, 255, 255, 0.7); } table .days td { padding: 10px; font-size: 11px; color: rgba(255, 255, 255, 0.7); background: rgba(255, 255, 255, 0.05); } .lecture { border-bottom: 1px solid rgba(238, 255, 65, 0.3); } .seminar { border-bottom: 1px solid rgba(105, 240, 174, 0.3); } .lab { border-bottom: 1px solid rgba(255, 64, 129, 0.3); } </style>';

    echo '<div class="screen">';
    echo '<div class="image"></div>';
    echo '<div class="overlay"></div>';
    echo '<table>';
    for ($i = 0; $i < count($times); $i++) {
        $empty = true;
        for ($j = 0; $j < count($days) - 1; $j++) {
            if (!empty($schedule['weeks'][0][$j][$i]['subject'])) {
                $empty = false;
            }
        }
        if ($empty == false) {
            list($time_start, $time_end) = explode('-', $times[$i]);
            $time_start = str_replace('.', ':', $time_start);
            $time_end = str_replace('.', ':', $time_end);
            echo '<tr>';
            for ($j = 0; $j < count($days) - 1; $j++) {
                echo '<td class="lesson ' . $schedule['weeks'][0][$j][$i]['type'] . '">';
                    if (empty($schedule['weeks'][0][$j][$i]['subject'])) {
                        $schedule['weeks'][0][$j][$i]['subject'] = '&nbsp;';
                    }
                    echo '<span class="subject">' . mb_strimwidth($schedule['weeks'][0][$j][$i]['subject'], 0, 8, '') . '</span>';
                    echo '<span class="time">' . $time_start . ' - ' . $time_end . '</span>';
                    echo '<span class="room">' . $schedule['weeks'][0][$j][$i]['room'] . '</span>';
                echo '</td>';
            }
            echo '</tr>';
        }
    }
    echo '<tr class="days">';
    for ($i = 0; $i < count($days) - 1; $i++) {
        echo '<td><span>' . $days_map[$days[$i]] . '</span></td>';
    }
    echo '</tr>';
    for ($i = 0; $i < count($times); $i++) {
        $empty = true;
        for ($j = 0; $j < count($days) - 1; $j++) {
            if (!empty($schedule['weeks'][1][$j][$i]['subject'])) {
                $empty = false;
            }
        }
        if ($empty == false) {
            list($time_start, $time_end) = explode('-', $times[$i]);
            $time_start = str_replace('.', ':', $time_start);
            $time_end = str_replace('.', ':', $time_end);
            echo '<tr>';
            for ($j = 0; $j < count($days) - 1; $j++) {
                echo '<td class="lesson ' . $schedule['weeks'][1][$j][$i]['type'] . '">';
                    if (empty($schedule['weeks'][1][$j][$i]['subject'])) {
                        $schedule['weeks'][1][$j][$i]['subject'] = '&nbsp;';
                    }
                    echo '<span class="subject">' . mb_strimwidth($schedule['weeks'][1][$j][$i]['subject'], 0, 8, '') . '</span>';
                    echo '<span class="time">' . $time_start . ' - ' . $time_end . '</span>';
                    echo '<span class="room">' . $schedule['weeks'][1][$j][$i]['room'] . '</span>';
                echo '</td>';
            }
            echo '</tr>';
        }
    }
}