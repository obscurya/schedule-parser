<?php

require_once 'config.php';

function get_teachers($exec) {
    $dom = new DOMDocument();
    $dom->loadHTML(mb_convert_encoding($exec, 'HTML-ENTITIES', 'UTF-8'));

    $selects = $dom->getElementsByTagName('select');
    $data = [];

    foreach ($selects as $select) {
        if ($select->getAttribute('name') == 'ctl00$ContentPlaceHolder1$ddlObjectValue') {
            $options = $select->childNodes;

            foreach ($options as $option) {
                $data[] = trim($option->textContent);
            }

            break;
        }
    }

    return $data;
}

function get_targets($exec) {
    $dom = new DOMDocument();
    $dom->loadHTML(mb_convert_encoding($exec, 'HTML-ENTITIES', 'UTF-8'));

    $selects = $dom->getElementsByTagName('select');
    $inputs = $dom->getElementsByTagName('input');
    $data = [];

    foreach ($selects as $select) {
        $attr = $select->getAttribute('name');
        $options = [];

        foreach ($select->childNodes as $option) {
            $options[] = [
                'value' => $option->getAttribute('value'),
                'content' => trim($option->textContent)
            ];
        }

        if ($attr == 'ctl00$ContentPlaceHolder1$ddlSchedule') {
            $data['schedule_type'] = [
                'name' => $attr,
                'options' => $options
            ];
        } else if ($attr == 'ctl00$ContentPlaceHolder1$ddlSubDivision') {
            $data['faculty'] = [
                'name' => $attr,
                'options' => $options
            ];
        } else if ($attr == 'ctl00$ContentPlaceHolder1$ddlCorse') {
            $data['course'] = [
                'name' => $attr,
                'options' => $options
            ];
        } else if ($attr == 'ctl00$ContentPlaceHolder1$ddlObjectValue') {
            $data['group'] = [
                'name' => $attr,
                'options' => $options
            ];
        }
    }

    foreach ($inputs as $input) {
        $attr = $input->getAttribute('name');

        if ($attr == 'ctl00$ContentPlaceHolder1$rblObject') {
            $data['user']['name'] = $attr;
            $data['user']['options'][] = $input->getAttribute('value');
        }
    }

    return $data;
}

function first_visit($url) {
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $exec = curl_exec($curl);

    curl_close($curl);

    return $exec;
}

function validation($exec) {
    preg_match_all('/id=\"__VIEWSTATE\" value=\"(.*?)\"/', $exec, $__VIEWSTATE);
    preg_match_all('/id=\"__EVENTVALIDATION\" value=\"(.*?)\"/', $exec, $__EVENTVALIDATION);

    $validation = [
        0 => $__VIEWSTATE[1][0],
        1 => $__EVENTVALIDATION[1][0]
    ];

    return $validation;
}

function curl($url, $target, $target_value, $validation) {
    $data = [
        '__LASTFOCUS' => '',
        '__EVENTTARGET' => $target,
        '__EVENTARGUMENT' => '',
        '__VIEWSTATE' => $validation[0],
        '__VIEWSTATEGENERATOR' => 'CA0B0334',
        '__EVENTVALIDATION' => $validation[1],
        $target => $target_value
    ];

    $query = http_build_query($data);

    $http_header = [
        'Content-Type: application/x-www-form-urlencoded',
        'Content-Length: ' . mb_strlen($query)
    ];

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $http_header);

    $exec = curl_exec($curl);

    curl_close($curl);

    return $exec;
}

libxml_use_internal_errors(true);

$times = [
    0 => '8.00-9.35',
    1 => '9.50-11.25',
    2 => '11.40-13.15',
    3 => '14.00-15.35',
    4 => '15.50-17.25',
    5 => '17.40-19.15',
    6 => '19.25-21.00'
];

$url = 'http://schedule.ispu.ru/';

// get ALL teachers

$exec = first_visit($url);
$targets = get_targets($exec);

// pretty_print($targets);
// die;

$teachers = [];
$_teachers = [];
$first_visit = true;
foreach ($targets['schedule_type']['options'] as $schedule_type_option) {
    if (!$first_visit) {
        $exec = curl($url, $targets['schedule_type']['name'], $schedule_type_option['value'], validation($exec));

        $first_visit = true;
    } else {
        $exec = curl($url, $targets['user']['name'], $targets['user']['options'][1], validation($exec));

        $first_visit = false;
    }
    $_teachers[] = get_teachers($exec);
}

foreach ($_teachers as $_t) {
    foreach ($_t as $_teacher) {
        if (!in_array($_teacher, $teachers)) {
            $teachers[] = $_teacher;
        }
    }
}

// parse schedule

$exec = first_visit($url);
$targets = get_targets($exec);

$cache = [
    'teachers' => $teachers,
    'days' => [
        0 => 'Понедельник',
        1 => 'Вторник',
        2 => 'Среда',
        3 => 'Четверг',
        4 => 'Пятница',
        5 => 'Суббота',
        6 => 'Воскресенье'
    ],
    'lesson_types' => [
        0 => 'Лекция',
        1 => 'Лабораторная',
        2 => 'Курсовое проектирование',
        3 => 'Семинар'
    ],
    'rooms' => [],
    'subjects' => [],
    'options' => [
        'schedule_type' => [],
        'faculty' => [],
        'course' => [],
        'group' => []
    ],
    'settings' => [],
    'schedule' => []
];

$first_visit = true;
foreach ($targets['schedule_type']['options'] as $schedule_type_option) {
    $cache['options']['schedule_type'][$schedule_type_option['value']] = $schedule_type_option['content'];

    if (!$first_visit) {
        $exec = curl($url, $targets['schedule_type']['name'], $schedule_type_option['value'], validation($exec));

        $_targets = get_targets($exec);
        $targets['faculty'] = $_targets['faculty'];
        $targets['course'] = $_targets['course'];
        $targets['group'] = $_targets['group'];

        $first_visit = true;
    }

    foreach ($targets['faculty']['options'] as $faculty_option) {
        $cache['options']['faculty'][$faculty_option['value']] = $faculty_option['content'];

        if (!$first_visit) {
            $exec = curl($url, $targets['faculty']['name'], $faculty_option['value'], validation($exec));

            $_targets = get_targets($exec);
            $targets['course'] = $_targets['course'];
            $targets['group'] = $_targets['group'];

            $first_visit = true;
        }

        foreach ($targets['course']['options'] as $course_option) {
            $cache['options']['course'][$course_option['value']] = $course_option['content'];

            if (!$first_visit) {
                $exec = curl($url, $targets['course']['name'], $course_option['value'], validation($exec));

                $_targets = get_targets($exec);
                $targets['group'] = $_targets['group'];

                $first_visit = true;
            }

            foreach ($targets['group']['options'] as $group_option) {
                $cache['options']['group'][$group_option['value']] = $group_option['content'];
                $cache['settings'][$schedule_type_option['value']][$faculty_option['value']][$course_option['value']][] = $group_option['value'];

                if ($first_visit) {
                    $first_visit = false;
                } else {
                    $exec = curl($url, $targets['group']['name'], $group_option['value'], validation($exec));
                }

                $dom = new DOMDocument();
                $dom->loadHTML(mb_convert_encoding($exec, 'HTML-ENTITIES', 'UTF-8'));

                $table = [];
                $tds = $dom->getElementsByTagName('td');
                $key = 0;

                foreach ($tds as $td) {
                    $string = trim($td->textContent);
                    $string = str_replace('   ', '', $string);
                    $string = str_replace('  ', ' ', $string);
                    $string = str_replace(' - ', '-', $string);

                    if (empty($table[$key])) {
                        $table[$key] = $string;
                    } else {
                        $key++;
                        $table[$key] = $string;
                    }

                    if ($td->hasAttribute('rowspan')) {
                        if ($td->getAttribute('rowspan') == 2 && $td->textContent != 'нед' && $td->textContent != 'Время') {
                            $table[$key + 8] = $string;
                        }
                    }

                    $key++;
                }

                $week_key = [
                    0 => array_search('1', $table),
                    1 => array_search('2', $table)
                ];

                $schedule = [];

                for ($week = 0; $week < 2; $week++) {
                    $days_counter = 0;
                    $lessons_counter = 0;
                    $times_counter = 0;

                    $begin = $week_key[$week] + 1;
                    $end = ($week == 0) ? $week_key[$week + 1] : $week_key[$week] * 2 - $week_key[$week - 1];

                    for ($i = $begin; $i < $end; $i++) {
                        if (!in_array($table[$i], $times)) {
                            list($time_start, $time_end) = explode('-', $times[$times_counter]);

                            $time_start = str_replace('.', ':', $time_start);
                            $time_end = str_replace('.', ':', $time_end);

                            $subject_id = -1;
                            $type_id = -1;
                            $teacher_id = -1;
                            $room_id = -1;

                            if (!empty($table[$i])) {
                                $has_teacher = false;
                                $strings = explode(';', $table[$i]);
                                $string = $strings[0];

                                foreach ($cache['teachers'] as $teacher_key => $t) {
                                    if (strstr($string, $t)) {
                                        $string = str_replace($t, '---' . $t . '---', $string);

                                        list($subject, $teacher, $room) = explode('---', $string);

                                        $subject = preg_replace('/\s+/', ' ', $subject);
                                        $teacher = trim($teacher);
                                        $room = trim($room);

                                        if (strpos($subject, 'лек.')) {
                                            $subject = str_replace('лек.', '', $subject);
                                            $type_id = 0;
                                        } else if (strpos($subject, 'лаб.')) {
                                            $subject = str_replace('лаб.', '', $subject);
                                            $type_id = 1;
                                        } else if (strpos($subject, 'к.пр.')) {
                                            $subject = str_replace('к.пр.', '', $subject);
                                            $type_id = 2;
                                        } else if (strpos($subject, 'сем.')) {
                                            $subject = str_replace('сем.', '', $subject);
                                            $type_id = 3;
                                        } else {
                                            $type_id = 3;
                                        }

                                        $subject = trim($subject);
                                        // $subject = str_replace('.', ' ', $subject);
                                        $subject = str_replace('"', ' ', $subject);

                                        if (in_array($subject, $cache['subjects'])) {
                                            $subject_id = array_search($subject, $cache['subjects']);
                                        } else {
                                            $subject_id = count($cache['subjects']);
                                            $cache['subjects'][] = $subject;
                                        }

                                        $teacher_id = $teacher_key;

                                        if (in_array($room, $cache['rooms'])) {
                                            $room_id = array_search($room, $cache['rooms']);
                                        } else {
                                            $room_id = count($cache['rooms']);
                                            $cache['rooms'][] = $room;
                                        }

                                        $has_teacher = true;
                                        break;
                                    } else {
                                        $has_teacher = false;
                                    }
                                }
                                if (!$has_teacher) {
                                    $strings = explode(';', $table[$i]);
                                    $string = $strings[0];
//                                    $string = preg_replace('/\s+/', ' ', $strings[0]);
                                    $type = '';

                                    if (strpos($string, 'лек.')) {
                                        $type = 'лек.';
                                        $type_id = 0;
                                    } else if (strpos($string, 'лаб.')) {
                                        $type = 'лаб.';
                                        $type_id = 1;
                                    } else if (strpos($string, 'к.пр.')) {
                                        $type = 'к.пр.';
                                        $type_id = 2;
                                    } else if (strpos($string, 'сем.')) {
                                        $type = 'сем.';
                                        $type_id = 3;
                                    }else {
                                        $type_id = 3;
                                    }

                                    if ($type != '') {
                                        list($subject, $room) = explode($type, $string);

                                        $subject = preg_replace('/\s+/', ' ', $subject);
                                        $room = trim($room);

                                        if (in_array($subject, $cache['subjects'])) {
                                            $subject_id = array_search($subject, $cache['subjects']);
                                        } else {
                                            $subject_id = count($cache['subjects']);
                                            $cache['subjects'][] = $subject;
                                        }

                                        if (in_array($room, $cache['rooms'])) {
                                            $room_id = array_search($room, $cache['rooms']);
                                        } else {
                                            $room_id = count($cache['rooms']);
                                            $cache['rooms'][] = $room;
                                        }
                                    } else {
                                        if (in_array($string, $cache['subjects'])) {
                                            $subject_id = array_search($string, $cache['subjects']);
                                        } else {
                                            $subject_id = count($cache['subjects']);
                                            $cache['subjects'][] = $string;
                                        }
                                    }
                                }
                            }

                            $schedule[$week][$days_counter][$lessons_counter] = [
                                'time_start' => trim($time_start),
                                'time_end' => trim($time_end),
                                'subject' => $subject_id,
                                'lesson_type' => $type_id,
                                'teacher' => $teacher_id,
                                'room' => $room_id
                            ];

                            $days_counter++;
                        }

                        if ($days_counter > 6) {
                            $days_counter = 0;
                            $lessons_counter++;
                            $times_counter++;
                        }

                        if ($lessons_counter == count($times)) {
                            $lessons_counter = 0;
                            $times_counter = 0;
                        }
                    }
                }

                $schedule_key = $schedule_type_option['value'] . '_' . $faculty_option['value'] . '_' . $course_option['value'] . '_' . $group_option['value'];

                $cache['schedule'][$schedule_key] = [
                    'schedule_id' => $schedule_type_option['value'],
                    'faculty_id' => $faculty_option['value'],
                    'course_id' => $course_option['value'],
                    'group_id' => $group_option['value'],
                    'data' => $schedule
                ];
            }
        }
    }
}

$cache = json_encode($cache, JSON_UNESCAPED_UNICODE);

//echo json_encode($cache, JSON_UNESCAPED_UNICODE);
//pretty_print($cache);

$file_name = 'cache.json';
$file = fopen($file_name, 'w');
fwrite($file, $cache);
fclose($file);