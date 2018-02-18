<?php

require_once 'config.php';

function create_key($options) {
    return implode('_', $options);
}

$file = file_get_contents('cache.json');
$cache = json_decode($file);
$options = $cache->options;
$settings = $cache->settings;

if (isset($_POST['get_schedule']) && isset($_POST['schedule_key'])) {
    $keys = implode(';', $_POST['schedule_key']);
    header('Location: ?schedule_key=' . $keys);
    exit;
}

if (isset($_GET['schedule_key'])) {
    $keys = explode(';', $_GET['schedule_key']);

    $schedules = [];
    $schedule_options = [];
    foreach ($keys as $key) {
        $schedule = $cache->schedule->$key;
        if ($schedule) {
            $schedule_options[] = explode('_', $key);
            $schedules[] = $schedule;
        }
    }
    $new_schedule = [];
    foreach ($schedules as $schedule) {
        foreach ($schedule->data as $week_key => $week) {
            if (empty($new_schedule[$week_key])) {
                $new_schedule[$week_key] = [];
            }
            foreach ($week as $day_key => $day) {
                if (empty($new_schedule[$week_key][$day_key])) {
                    $new_schedule[$week_key][$day_key] = [];
                }
                foreach ($day as $lesson_key => $lesson) {
                    if (empty($new_schedule[$week_key][$day_key][$lesson_key])) {
                        $new_schedule[$week_key][$day_key][$lesson_key] = $lesson;
                    } else {
                        if ($lesson->subject != -1) {
                            $new_schedule[$week_key][$day_key][$lesson_key] = $lesson;
                        }
                    }
                }
            }
        }
    }
    if (!empty($new_schedule)) {
        // delete empty days
        $weeks = [];
        $lessons_all = 0;
        foreach ($new_schedule as $week) {
            $days = [];
            foreach ($week as $day_key => $day) {
                $lessons_all = count($day);
                $new_day = [];
                foreach ($day as $lesson) {
                    if ($lesson->subject != -1) {
                        $new_day[] = 1;
                    } else {
                        $new_day[] = 0;
                    }
                }
                $add = false;
                foreach ($new_day as $lesson) {
                    if ($lesson) {
                        $add = true;
                    }
                }
                if ($add) {
                    $days[$day_key] = $new_day;
                }
            }
            $weeks[] = $days;
        }

        // delete empty rows
        $weeks_rows = [];
        foreach ($weeks as $week) {
            $rows = [];
            for ($i = 0; $i < $lessons_all; $i++) {
                $row = [];
                foreach ($week as $day_key => $day) {
                    if ($day[$i]) {
                        $row[$day_key] = 1;
                    } else {
                        $row[$day_key] = 0;
                    }
                }
                $add = false;
                foreach ($row as $lesson) {
                    if ($lesson) {
                        $add = true;
                    }
                }
                if ($add) {
                    $rows[$i] = $row;
                }
            }
            $weeks_rows[] = $rows;
        }

        // form schedule table
        $table = [];
        foreach ($weeks_rows as $week_key => $week) {
            $table_week = [];
            foreach ($week as $row_key => $row) {
                $table_row = [];
                foreach ($row as $cell_key => $cell) {
                    $lesson = $new_schedule[$week_key][$cell_key][$row_key];

                    $subject = ($cache->subjects[$lesson->subject]) ? $cache->subjects[$lesson->subject] : '&nbsp;';
                    $room = ($cache->rooms[$lesson->room]) ? $cache->rooms[$lesson->room] : '&nbsp;';
                    $type = $lesson->lesson_type;
                    $time = $lesson->time_start . ' - ' . $lesson->time_end;
                    $class = 'no_type';

                    if ($type == 0) {
                        $class = 'lecture';
                    } else if ($type == 1) {
                        $class = 'lab';
                    } else if ($type == 2) {
                        $class = 'course';
                    } else if ($type == 3) {
                        $class = 'seminar';
                    }

                    $table_row[$cell_key] = [
                        'subject' => $subject,
                        'type' => $type,
                        'room' => $room,
                        'time' => $time,
                        'css_class' => $class
                    ];
                }
                $table_week[] = $table_row;
            }
            $table[] = $table_week;
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700&amp;subset=cyrillic" rel="stylesheet">
    <title>Schedule</title>
</head>
<body>
    <div class="wrapper">
        <div class="settings">
            <?php if (isset($_GET['schedule_key'])) { ?>
                <a href="/">Загрузить другое расписание</a>
            <?php } else { ?>
                <form method="post">
                    <div class="schedule_type">
                        <h1>Расписание</h1>
                        <?php foreach ($options->schedule_type as $key => $schedule_type) { ?>
                            <div class="option">
                                <input id="radio<?=$key?>" type="radio" name="schedule_type" value="<?=$key?>" onclick="set_schedule_type(this);">
                                <label for="radio<?=$key?>"><?=$options->schedule_type->$key?></label>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="faculty">
                        <h1>Факультет</h1>
                        <?php foreach ($options->faculty as $key => $faculty) { ?>
                            <div class="option">
                                <input id="radio<?=$key?>" type="radio" name="faculty" value="<?=$key?>" onclick="set_faculty(this);">
                                <label for="radio<?=$key?>"><?=$options->faculty->$key?></label>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="course" id="course"></div>
                    <div class="group" id="group"></div>
                    <div class="input" id="input"></div>
                </form>
            <?php } ?>
        </div>
        <div class="design">
            <div class="title">
                <?php if (isset($_GET['schedule_key'])) { ?>
                    <?php if (isset($schedule_options) && !empty($schedule_options)) { ?>
                        <?php foreach ($schedule_options as $so) { ?>
                            <p><?=$options->schedule_type->$so[0] . ' ' . $options->faculty->$so[1] . ' ' . $options->course->$so[2] . '-' . $options->group->$so[3]?></p>
                        <?php } ?>
                    <?php } else { ?>
                        <p>Такого расписания не существует</p>
                    <?php } ?>
                <?php } else { ?>
                    <p>Выберите расписание</p>
                <?php } ?>
            </div>
            <div class="table">
                <?php if (isset($new_schedule) && !empty($new_schedule)) { ?>
                    <div class="schedule">
<!--                        <div class="background" style="background-image: url(background.jpg);"></div>-->
                        <div class="background" style="background-color: #90A4AE;"></div>
                        <div class="overlay"></div>
                        <?php if (isset($table)) { ?>
                            <div class="schedule-table">
                                <?php foreach ($table as $key => $week) { ?>
                                    <table class="week">
                                        <tr class="days">
                                            <?php foreach ($week[0] as $key_lesson => $lesson) { ?>
                                                <td><p><?=$cache->days[$key_lesson]?></p></td>
                                            <?php } ?>
                                        </tr>
                                        <?php foreach ($week as $row) { ?>
                                            <tr>
                                                <?php foreach ($row as $lesson) { ?>
                                                    <?php if (!isset($lesson['missing'])) { ?>
                                                        <td class="<?=$lesson['css_class']?>">
                                                            <p class="subject"><?=$lesson['subject']?></p>
                                                            <p class="time"><?=$lesson['time']?></p>
                                                            <p class="room"><?=$lesson['room']?></p>
                                                        </td>
                                                    <?php } ?>
                                                <?php } ?>
                                            </tr>
                                        <?php } ?>
                                    </table>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php if (!isset($_GET['schedule_key'])) { ?>
        <script src="options.js"></script>
        <script>
            set_cache('<?=$file?>');
        </script>
    <?php } ?>
</body>
</html>
