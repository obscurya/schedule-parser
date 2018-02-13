<?php

// ?schedule=56&object=0&subdivision=30000&course=3&group=789 (3-48)

function debug($array) {
    echo '<pre>' . print_r($array, true) . '</pre>';
}

function first_visit($url) {
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}

function validation($string) {
    preg_match_all('/id=\"__VIEWSTATE\" value=\"(.*?)\"/', $string, $__VIEWSTATE);
    preg_match_all('/id=\"__EVENTVALIDATION\" value=\"(.*?)\"/', $string, $__EVENTVALIDATION);

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

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}

require_once ('schedule_options.php');

//echo '{"weeks":{"first":{"monday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","type":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"","type":"","teacher":"","room":""},{"time_start":"11:40","time_end":"13:15","subject":"","type":"","teacher":"","room":""},{"time_start":"14:00","time_end":"15:35","subject":"Web-\u0442\u0435\u0445\u043d \u0438 \u043f\u0440\u043e\u0433\u0440 \u043b\u0435\u043a","type":"lecture","teacher":"\u0420\u0443\u0434\u0430\u043a\u043e\u0432 \u041d.\u0412.","room":"\u0411326"},{"time_start":"15:50","time_end":"17:25","subject":"\u041e\u0441\u043d \u0438\u043d\u0444 \u0434\u0435\u044f\u0442 \u043b\u0435\u043a","type":"lecture","teacher":"\u0413\u043d\u0430\u0442\u044e\u043a \u0410.\u0411.","room":"\u0411319\u0430"},{"time_start":"17:40","time_end":"19:15","subject":"","type":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","type":"","teacher":"","room":""}]},"tuesday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","type":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"","type":"","teacher":"","room":""},{"time_start":"11:40","time_end":"13:15","subject":"\u0424\u0438\u0437\u0432\u043e\u0441","type":"seminar","teacher":"","room":""},{"time_start":"14:00","time_end":"15:35","subject":"\u041c\u0430\u0442 \u043c\u0435\u0442 \u043f\u0440 \u0440\u0448 \u043b\u0435\u043a","type":"lecture","teacher":"\u0415\u043b\u0438\u0437\u0430\u0440\u043e\u0432\u0430 \u041d.\u041d.","room":"\u0411319\u0430"},{"time_start":"15:50","time_end":"17:25","subject":"\u0421\u043e\u0446\u0438\u0430\u043b\u044c\u043d\u0430\u044f \u0438\u043d\u0444\u043e\u0440\u043c\u0430\u0442\u0438\u043a\u0430 \u043b\u0435\u043a","type":"lecture","teacher":"\u041f\u0430\u0434\u044b\u043b\u0438\u043d\u0430 \u0410.\u041b.","room":"\u0411017\u0431"},{"time_start":"17:40","time_end":"19:15","subject":"","type":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","type":"","teacher":"","room":""}]},"thursday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","type":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"","type":"","teacher":"","room":""},{"time_start":"11:40","time_end":"13:15","subject":"","type":"","teacher":"","room":""},{"time_start":"14:00","time_end":"15:35","subject":"","type":"","teacher":"","room":""},{"time_start":"15:50","time_end":"17:25","subject":"","type":"","teacher":"","room":""},{"time_start":"17:40","time_end":"19:15","subject":"","type":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","type":"","teacher":"","room":""}]},"wednesday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","type":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"","type":"","teacher":"","room":""},{"time_start":"11:40","time_end":"13:15","subject":"","type":"","teacher":"","room":""},{"time_start":"14:00","time_end":"15:35","subject":"\u041f\u0441\u0438\u0445\u043e\u043b\u043e\u0433 \u043b\u0435\u043a","type":"lecture","teacher":"\u041a\u0440\u044e\u043a\u043e\u0432\u0430 \u0422.\u0411.","room":"\u0411318"},{"time_start":"15:50","time_end":"17:25","subject":"\u041d\u0435\u0447\u0435\u0442 \u043c\u043d\u043e\u0436 \u0438 \u043b\u043e\u0433 \u043b\u0435\u043a","type":"lecture","teacher":"\u0420\u0443\u0434\u0430\u043a\u043e\u0432 \u041d.\u0412.","room":"\u0411001\u0432"},{"time_start":"17:40","time_end":"19:15","subject":"","type":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","type":"","teacher":"","room":""}]},"friday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","type":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"","type":"","teacher":"","room":""},{"time_start":"11:40","time_end":"13:15","subject":"\u0422\u0435\u0445\u043d\u043e\u043b \u0441\u043e\u0446 \u0438\u0441\u0441\u043b\u0435\u0434 \u043b\u0435\u043a","type":"lecture","teacher":"\u0411\u0430\u043b\u043b\u043e\u0434 \u0411.\u0410.","room":"\u0411319\u0430"},{"time_start":"14:00","time_end":"15:35","subject":"\u0424\u0438\u0437\u0432\u043e\u0441","type":"seminar","teacher":"","room":""},{"time_start":"15:50","time_end":"17:25","subject":"\u0421\u043e\u0446\u0438\u0430\u043b\u044c\u043d\u0430\u044f \u0438\u043d\u0444\u043e\u0440\u043c\u0430\u0442\u0438\u043a\u0430 \u043b\u0435\u043a","type":"lecture","teacher":"\u041f\u0430\u0434\u044b\u043b\u0438\u043d\u0430 \u0410.\u041b.","room":"\u0411027"},{"time_start":"17:40","time_end":"19:15","subject":"","type":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","type":"","teacher":"","room":""}]},"saturday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","type":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"","type":"","teacher":"","room":""},{"time_start":"11:40","time_end":"13:15","subject":"\u0418\u043d\u0444 \u0441\u0438\u0441\u0442 \u0438 \u0442\u0435\u0445\u043d \u043b\u0435\u043a","type":"lecture","teacher":"\u0411\u0430\u043b\u043b\u043e\u0434 \u0411.\u0410.","room":"\u0411319\u0430"},{"time_start":"14:00","time_end":"15:35","subject":"Web-\u0442\u0435\u0445\u043d \u0438 \u043f\u0440\u043e\u0433\u0440 \u043b\u0435\u043a","type":"lecture","teacher":"\u0420\u0443\u0434\u0430\u043a\u043e\u0432 \u041d.\u0412.","room":"\u0411319\u0430"},{"time_start":"15:50","time_end":"17:25","subject":"","type":"","teacher":"","room":""},{"time_start":"17:40","time_end":"19:15","subject":"","type":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","type":"","teacher":"","room":""}]},"sunday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","type":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"","type":"","teacher":"","room":""},{"time_start":"11:40","time_end":"13:15","subject":"","type":"","teacher":"","room":""},{"time_start":"14:00","time_end":"15:35","subject":"","type":"","teacher":"","room":""},{"time_start":"15:50","time_end":"17:25","subject":"","type":"","teacher":"","room":""},{"time_start":"17:40","time_end":"19:15","subject":"","type":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","type":"","teacher":"","room":""}]}},"second":{"monday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"","teacher":"","room":""},{"time_start":"11:40","time_end":"13:15","subject":"","teacher":"","room":""},{"time_start":"14:00","time_end":"15:35","subject":"\u041c\u0430\u0442 \u043c\u0435\u0442 \u043f\u0440 \u0440\u0448 \u043b\u0435\u043a","teacher":"\u0415\u043b\u0438\u0437\u0430\u0440\u043e\u0432\u0430 \u041d.\u041d.","room":"\u0411319\u0430","type":"lecture"},{"time_start":"15:50","time_end":"17:25","subject":"\u0418\u043d\u0444 \u0441\u0438\u0441\u0442 \u0438 \u0442\u0435\u0445\u043d \u043b\u0435\u043a","teacher":"\u0411\u0430\u043b\u043b\u043e\u0434 \u0411.\u0410.","room":"\u0411319\u0430","type":"lecture"},{"time_start":"17:40","time_end":"19:15","subject":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","teacher":"","room":""}]},"tuesday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"","teacher":"","room":""},{"time_start":"11:40","time_end":"13:15","subject":"\u0424\u0438\u0437\u0432\u043e\u0441","teacher":"","room":"","type":"seminar"},{"time_start":"14:00","time_end":"15:35","subject":"\u041d\u0435\u0447\u0435\u0442 \u043c\u043d\u043e\u0436 \u0438 \u043b\u043e\u0433 \u043b\u0435\u043a","teacher":"\u0420\u0443\u0434\u0430\u043a\u043e\u0432 \u041d.\u0412.","room":"\u0411232","type":"lecture"},{"time_start":"15:50","time_end":"17:25","subject":"\u041e\u0441\u043d \u0438\u043d\u0444 \u0434\u0435\u044f\u0442 \u043b\u0435\u043a","teacher":"\u0413\u043d\u0430\u0442\u044e\u043a \u0410.\u0411.","room":"\u0411319\u0430","type":"lecture"},{"time_start":"17:40","time_end":"19:15","subject":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","teacher":"","room":""}]},"thursday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"","teacher":"","room":""},{"time_start":"11:40","time_end":"13:15","subject":"","teacher":"","room":""},{"time_start":"14:00","time_end":"15:35","subject":"","teacher":"","room":""},{"time_start":"15:50","time_end":"17:25","subject":"","teacher":"","room":""},{"time_start":"17:40","time_end":"19:15","subject":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","teacher":"","room":""}]},"wednesday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"","teacher":"","room":""},{"time_start":"11:40","time_end":"13:15","subject":"","teacher":"","room":""},{"time_start":"14:00","time_end":"15:35","subject":"\u041e\u0441\u043d \u0438\u043d\u0444 \u0434\u0435\u044f\u0442 \u043b\u0435\u043a","teacher":"\u0413\u043d\u0430\u0442\u044e\u043a \u0410.\u0411.","room":"\u0411319\u0430","type":"lecture"},{"time_start":"15:50","time_end":"17:25","subject":"\u0421\u043e\u0446\u0438\u0430\u043b\u044c\u043d\u0430\u044f \u0438\u043d\u0444\u043e\u0440\u043c\u0430\u0442\u0438\u043a\u0430 \u043b\u0435\u043a","teacher":"\u041f\u0430\u0434\u044b\u043b\u0438\u043d\u0430 \u0410.\u041b.","room":"\u0411026","type":"lecture"},{"time_start":"17:40","time_end":"19:15","subject":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","teacher":"","room":""}]},"friday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"","teacher":"","room":""},{"time_start":"11:40","time_end":"13:15","subject":"\u0422\u0435\u0445\u043d\u043e\u043b \u0441\u043e\u0446 \u0438\u0441\u0441\u043b\u0435\u0434 \u043b\u0435\u043a","teacher":"\u0411\u0430\u043b\u043b\u043e\u0434 \u0411.\u0410.","room":"\u0411319\u0430","type":"lecture"},{"time_start":"14:00","time_end":"15:35","subject":"\u0424\u0438\u0437\u0432\u043e\u0441","teacher":"","room":"","type":"seminar"},{"time_start":"15:50","time_end":"17:25","subject":"Web-\u0442\u0435\u0445\u043d \u0438 \u043f\u0440\u043e\u0433\u0440 \u043b\u0435\u043a","teacher":"\u0420\u0443\u0434\u0430\u043a\u043e\u0432 \u041d.\u0412.","room":"\u0411319\u0430","type":"lecture"},{"time_start":"17:40","time_end":"19:15","subject":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","teacher":"","room":""}]},"saturday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"\u041f\u0441\u0438\u0445\u043e\u043b\u043e\u0433 \u043b\u0435\u043a","teacher":"\u041a\u0440\u044e\u043a\u043e\u0432\u0430 \u0422.\u0411.","room":"\u0410501","type":"lecture"},{"time_start":"11:40","time_end":"13:15","subject":"\u041c\u0430\u0442 \u043c\u0435\u0442 \u043f\u0440 \u0440\u0448 \u043b\u0435\u043a","teacher":"\u0415\u043b\u0438\u0437\u0430\u0440\u043e\u0432\u0430 \u041d.\u041d.","room":"\u0411319\u0430","type":"lecture"},{"time_start":"14:00","time_end":"15:35","subject":"","teacher":"","room":""},{"time_start":"15:50","time_end":"17:25","subject":"","teacher":"","room":""},{"time_start":"17:40","time_end":"19:15","subject":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","teacher":"","room":""}]},"sunday":{"lessons":[{"time_start":"8:00","time_end":"9:35","subject":"","teacher":"","room":""},{"time_start":"9:50","time_end":"11:25","subject":"","teacher":"","room":""},{"time_start":"11:40","time_end":"13:15","subject":"","teacher":"","room":""},{"time_start":"14:00","time_end":"15:35","subject":"","teacher":"","room":""},{"time_start":"15:50","time_end":"17:25","subject":"","teacher":"","room":""},{"time_start":"17:40","time_end":"19:15","subject":"","teacher":"","room":""},{"time_start":"19:25","time_end":"21:00","subject":"","teacher":"","room":""}]}}}}';
//exit;



//$result = first_visit($url);
//
//$validation = validation($result);
//$target = $targets[1];
//$target_value = 1;
//$result = curl($url, $target, $target_value, $validation);
//
//$dom = new \DOMDocument();
//$dom->loadHTML(mb_convert_encoding($result, 'HTML-ENTITIES', 'UTF-8'));
//
//$opts = $dom->getElementsByTagName('option');
//
//$arr = [];
//
//foreach ($opts as $opt) {
//    $arr[] = trim($opt->textContent);
//}
//
//$j = 0;
//for ($i = 56; $i < count($arr); $i++) {
//    echo $j . ' => \'' . $arr[$i] . '\',<br>';
//    $j++;
//}
//
//exit;

if (isset($_REQUEST)) {
    $options = [
        0 => $schedule_types[$_GET['schedule']],
        1 => $objects[$_GET['object']],
        2 => $subdivisions[$_GET['subdivision']],
        3 => $courses[$_GET['course']],
        4 => $groups[$_GET['group']]
    ];

    if (isset($_GET['group_add'])) $options[5] = $groups[$_GET['group_add']];

    $result = first_visit($url);

    if (count($options) > 5) {
        $count = count($options) - 1;
    } else {
        $count = count($options);
    }

    for ($i = 0; $i < $count; $i++) {
        $validation = validation($result);
        $target = $targets[array_search($options[$i], $options)];
        $target_value = $options[$i];
        $result = curl($url, $target, $target_value, $validation);
    }

//    foreach ($options as $opt) {
//        $validation = validation($result);
//        $target = $targets[array_search($opt, $options)];
//        $target_value = $opt;
//        $result = curl($url, $target, $target_value, $validation);
//    }

    libxml_use_internal_errors(true);

    $dom = new \DOMDocument();
    $dom->loadHTML(mb_convert_encoding($result, 'HTML-ENTITIES', 'UTF-8'));

    libxml_clear_errors();

    $table = [];
    $tds = $dom->getElementsByTagName('td');

    $i = 0;
    foreach ($tds as $td) {
        $string = trim($td->textContent);
        $string = str_replace('   ', '', $string);
        $string = str_replace('  ', ' ', $string);
        $string = str_replace(' - ', '-', $string);

        if (empty($table[$i])) {
            $table[$i] = $string;
        } else {
            $i++;
            $table[$i] = $string;
        }

        if ($td->hasAttribute('rowspan')) {
            if ($td->getAttribute('rowspan') == 2 && $td->textContent != 'нед' && $td->textContent != 'Время') {
                $table[$i+8] = $string;
            }
        }

        $i++;
    }

    if (count($options) > 5) {
        $options[4] = $options[5];

        $result = first_visit($url);

        for ($i = 0; $i < count($options) - 1; $i++) {
            $validation = validation($result);
            $target = $targets[array_search($options[$i], $options)];
            $target_value = $options[$i];
            $result = curl($url, $target, $target_value, $validation);
        }

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML(mb_convert_encoding($result, 'HTML-ENTITIES', 'UTF-8'));

        libxml_clear_errors();

        $table2 = [];
        $tds = $dom->getElementsByTagName('td');

        $i = 0;
        foreach ($tds as $td) {
            $string = trim($td->textContent);
            $string = str_replace('   ', '', $string);
            $string = str_replace('  ', ' ', $string);
            $string = str_replace(' - ', '-', $string);

            if (empty($table2[$i])) {
                $table2[$i] = $string;
            } else {
                $i++;
                $table2[$i] = $string;
            }

            if ($td->hasAttribute('rowspan')) {
                if ($td->getAttribute('rowspan') == 2 && $td->textContent != 'нед' && $td->textContent != 'Время') {
                    $table2[$i+8] = $string;
                }
            }

            $i++;
        }

        for ($i = 0; $i < count($table); $i++) {
            if (!empty($table2[$i])) {
                $table[$i] = $table2[$i];
            }
        }
    }

    $week1_key = array_search('1', $table);
    $week2_key = array_search('2', $table);

    $schedule = [
        'weeks' => [
            'first' => [],
            'second' => []
        ]
    ];

    $days_counter = 0;
    $lessons_counter = 0;
    $times_counter = 0;
    for ($i = $week1_key + 1; $i < $week2_key; $i++) {
        if (!in_array($table[$i], $times)) {
            list($time_start, $time_end) = explode('-', $times[$times_counter]);
            $time_start = str_replace('.', ':', $time_start);
            $time_end = str_replace('.', ':', $time_end);
            $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['time_start'] = $time_start;
            $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['time_end'] = $time_end;
            $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['subject'] = '';
            $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['type'] = '';
            $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['teacher'] = '';
            $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['room'] = '';

//            if (!empty($table[$i]) && strpos($table[$i], 'История') === false && strpos($table[$i], 'Англ.с') === false) {
            if (!empty($table[$i])) {
//                if (strpos($table[$i], 'Физк') !== false || strpos($table[$i], 'физ культ') !== false) {
                if (strpos($table[$i], 'Элект. курсы по физ. культ.') !== false) {
                    $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['subject'] = 'Физвос';
                    $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['type'] = 'seminar';
                } else {
                    foreach ($teachers as $t) {
                        if (strstr($table[$i], $t)) {
                            $table[$i] = str_replace($t, '---' . $t . '---', $table[$i]);
                            list($subject, $teacher, $room) = explode('---', $table[$i]);
                            if (strpos($subject, 'лек.')) {
                                $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['type'] = 'lecture';
                            } else if (strpos($subject, 'лаб.')) {
                                $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['type'] = 'lab';
                            } else {
                                $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['type'] = 'seminar';
                            }
                            $subject = str_replace('.', ' ', $subject);
                            $subject = str_replace('"', ' ', $subject);
                            $subject = str_replace('  ', ' ', $subject);
                            $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['subject'] = trim($subject);
                            $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['teacher'] = trim($teacher);
                            $schedule['weeks']['first'][$days[$days_counter]]['lessons'][$lessons_counter]['room'] = trim($room);
                            break;
                        }
                    }
                }
            }

            $days_counter++;
        }

        if ($days_counter == count($days)) {
            $days_counter = 0;
            $lessons_counter++;
            $times_counter++;
        }

        if ($lessons_counter == count($times)) {
            $lessons_counter = 0;
            $times_counter = 0;
        }
    }

    $days_counter = 0;
    $lessons_counter = 0;
    $times_counter = 0;
    for ($i = $week2_key + 1; $i < $week2_key*2 - $week1_key; $i++) {
        if (!in_array($table[$i], $times)) {
            list($time_start, $time_end) = explode('-', $times[$times_counter]);
            $time_start = str_replace('.', ':', $time_start);
            $time_end = str_replace('.', ':', $time_end);
            $schedule['weeks']['second'][$days[$days_counter]]['lessons'][$lessons_counter]['time_start'] = $time_start;
            $schedule['weeks']['second'][$days[$days_counter]]['lessons'][$lessons_counter]['time_end'] = $time_end;
            $schedule['weeks']['second'][$days[$days_counter]]['lessons'][$lessons_counter]['subject'] = '';
            $schedule['weeks']['second'][$days[$days_counter]]['lessons'][$lessons_counter]['teacher'] = '';
            $schedule['weeks']['second'][$days[$days_counter]]['lessons'][$lessons_counter]['room'] = '';

//            if (!empty($table[$i]) && strpos($table[$i], 'История') === false && strpos($table[$i], 'Англ.с') === false) {
            if (!empty($table[$i])) {
//                if (strpos($table[$i], 'Физк') !== false || strpos($table[$i], 'Элект') !== false) {
                if (strpos($table[$i], 'Элект. курсы по физ. культ.') !== false) {
                    $schedule['weeks']['second'][$days[$days_counter]]['lessons'][$lessons_counter]['subject'] = 'Физвос';
                    $schedule['weeks']['second'][$days[$days_counter]]['lessons'][$lessons_counter]['type'] = 'seminar';
                } else {
                    foreach ($teachers as $t) {
                        if (strstr($table[$i], $t)) {
                            $table[$i] = str_replace($t, '---' . $t . '---', $table[$i]);
                            list($subject, $teacher, $room) = explode('---', $table[$i]);
                            if (strpos($subject, 'лек.')) {
                                $schedule['weeks']['second'][$days[$days_counter]]['lessons'][$lessons_counter]['type'] = 'lecture';
                            } else if (strpos($subject, 'лаб.')) {
                                $schedule['weeks']['second'][$days[$days_counter]]['lessons'][$lessons_counter]['type'] = 'lab';
                            } else {
                                $schedule['weeks']['second'][$days[$days_counter]]['lessons'][$lessons_counter]['type'] = 'seminar';
                            }
                            $subject = str_replace('.', ' ', $subject);
                            $subject = str_replace('"', ' ', $subject);
                            $subject = str_replace('  ', ' ', $subject);
                            $schedule['weeks']['second'][$days[$days_counter]]['lessons'][$lessons_counter]['subject'] = trim($subject);
                            $schedule['weeks']['second'][$days[$days_counter]]['lessons'][$lessons_counter]['teacher'] = trim($teacher);
                            $schedule['weeks']['second'][$days[$days_counter]]['lessons'][$lessons_counter]['room'] = trim($room);
                            break;
                        }
                    }
                }
            }

            $days_counter++;
        }

        if ($days_counter == count($days)) {
            $days_counter = 0;
            $lessons_counter++;
            $times_counter++;
        }

        if ($lessons_counter == count($times)) {
            $lessons_counter = 0;
            $times_counter = 0;
        }
    }

    if ($schedule) {
        echo json_encode($schedule);
    }
//    debug(json_encode($schedule, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}