<?php

namespace app\controllers;

use app\models\Main;

class ScheduleController extends AppController {

    public $layout = 'schedule';

    public function indexAction() {
        $model = new Main;

        if (!empty($this->route['alias'])) {
            $alias = $this->route['alias'];
        } else {
            $alias = null;
        }
        $this->setMeta('Расписание', 'Описание страницы', 'Ключевые слова');
        $meta = $this->meta;
        $page = $this->getPage();
        $styles = ['schedule', 'inputs'];
        if (isset($_SESSION['user'])) {
            $session = $_SESSION['user'];
        } else {
            $session = null;
        }

        require_once(WWW . '/schedule_options.php');

        $data = [];

        $paths = [
            'full' => 'images/schedule/full/',
            'thumbs' => 'images/schedule/thumbs/'
        ];

        $images = scandir($paths['thumbs']);
        $images = preg_grep('/\\.(?:png|gif|jpe?g|JPE?G)/', $images);
        rsort($images);

        $url = 'http://schedule.ispu.ru/';

        if (isset($_POST['create_schedule'])) {
            if (!empty($_POST['schedule_type'])) {
                $_SESSION['options']['schedule_type'] = $_POST['schedule_type'];
            } else {
                $_SESSION['options']['schedule_type'] = '57';
            }

            if (!empty($_POST['faculty'])) {
                $_SESSION['options']['faculty'] = $_POST['faculty'];
            } else {
                $_SESSION['options']['faculty'] = 'ivtf';
            }

            if (!empty($_POST['course']) && !empty($_POST['group'])) {
                $_SESSION['options']['course'] = $_POST['course'];
                $_SESSION['options']['group'] = $_POST['group'];
            } else {
                $_SESSION['options']['course'] = '1';
                $_SESSION['options']['group'] = '41';
            }

            if (!empty($_POST['group_add']) && $_POST['group_add'] != 'none') {
                $_SESSION['options']['group_add'] = $_POST['group_add'];
            } else {
                $_SESSION['options']['group_add'] = null;
            }

            if (!empty($_POST['overlay_color'])) {
                $_SESSION['options']['overlay_color'] = $_POST['overlay_color'];
            } else {
                $_SESSION['options']['overlay_color'] = 'rgba(33, 33, 33, 0.8)';
            }

            if (!empty($_POST['bottom'])) {
                $_SESSION['options']['bottom'] = $_POST['bottom'];
            } else {
                $_SESSION['options']['bottom'] = '180px';
            }

            if (!empty($_POST['background_image_name'])) {
                $_SESSION['options']['background_image_name'] = $_POST['background_image_name'];
            } else {
                $_SESSION['options']['background_image_name'] = 'none';
            }

            if ($_FILES['file']['error'] != 4) {
                include LIBS . 'Image.php';
                $image = new \Image();
                $image->load($_FILES);
                $image->save($paths['full']);
                $image->resizeAuto(200, 200);
                $image->save($paths['thumbs'], false);
                $_SESSION['options']['background_image_name'] = $image->getImageName();
            }

            if (!empty($_POST['width'])) {
                $_SESSION['options']['width'] = $_POST['width'];
            } else {
                $_SESSION['options']['width'] = '640px';
            }

            if (!empty($_POST['height'])) {
                $_SESSION['options']['height'] = $_POST['height'];
            } else {
                $_SESSION['options']['height'] = '1136px';
            }

//            debug($_SESSION['options']);
        }

        if (isset($_SESSION['options']['schedule_type'], $_SESSION['options']['faculty'], $_SESSION['options']['course'], $_SESSION['options']['overlay_color'], $_SESSION['options']['bottom'], $_SESSION['options']['background_image_name'], $_SESSION['options']['width'], $_SESSION['options']['height'])) {
            if (isset($_POST['create_schedule'])) {
                $paramsArray = [
                    'schedule_type' => $_SESSION['options']['schedule_type'],
                    'faculty' => $_SESSION['options']['faculty'],
                    'course' => $_SESSION['options']['course'],
                    'group' => $_SESSION['options']['group'],
                    'overlay_color' => $_SESSION['options']['overlay_color'],
                    'bottom' => $_SESSION['options']['bottom'],
                    'background_image_name' => $_SESSION['options']['background_image_name'],
                    'width' => $_SESSION['options']['width'],
                    'height' => $_SESSION['options']['height']
                ];

                if (isset($_SESSION['options']['group_add'])) {
                    $paramsArray = array_merge([
                        'group_add' => $_SESSION['options']['group_add']
                    ], $paramsArray);
                }

                $vars = http_build_query($paramsArray);
                $options = [
                    'http' => [
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $vars
                    ]
                ];
                $context = stream_context_create($options);
                $result = file_get_contents('http://' . $_SERVER['SERVER_NAME'] . '/schedule_output.php', false, $context);
            }
        }

        $this->set(compact('meta', 'alias', 'styles', 'page', 'images', 'paths', 'data', 'session', 'result', 'schedule_types', 'schedule_types_map', 'faculties', 'courses', 'groups', 'images'));
    }

}