var cache,
    schedule_type = -1,
    faculty = -1,
    course = -1,
    group = [];

var courses,
    groups;

var divCourse = document.getElementById('course'),
    divGroup = document.getElementById('group'),
    divInput = document.getElementById('input');

function set_cache(file) {
    cache = JSON.parse(file);
}

function set_schedule_type(input) {
    schedule_type = Number(input.value);
    if (faculty !== -1) {
        get_courses(schedule_type, faculty);
    }
}

function set_faculty(input) {
    faculty = Number(input.value);
    if (schedule_type !== -1) {
        get_courses(schedule_type, faculty);
    }
}

function set_course(input) {
    group = [];
    course = Number(input.value);
    if (schedule_type !== -1 && faculty !== -1) {
        get_groups(schedule_type, faculty, course);
    }
}

function set_group(input) {
    var value = Number(input.value),
        index = group.indexOf(value);
    if (schedule_type !== -1 && faculty !== -1 && course !== -1) {
        if (index === -1) {
            group.push(Number(input.value));
        } else {
            group.splice(index, 1);
        }
        if (group.length) {
            divInput.innerHTML = '<input type="submit" name="get_schedule" value="Загрузить расписание">';
            for (var i = 0; i < group.length; i++) {
                divInput.innerHTML += '<input type="hidden" name="schedule_key[]" value="' + schedule_type + '_' + faculty + '_' + course + '_' + group[i] + '">';
            }
        } else {
            divInput.innerHTML = '';
        }
    }
}

function get_courses(schedule_type, faculty) {
    courses = cache.settings[schedule_type][faculty];
    divCourse.innerHTML = '<h1>Курс</h1>';
    divGroup.innerHTML = '';
    divInput.innerHTML = '';

    var str = '';

    for (var i = 0; i < Object.keys(courses).length; i++) {
        var key = Object.keys(courses)[i];

        str += '<div class="option">' +
            '<input id="radio' + key + '" type="radio" name="course" value="' + key + '" onclick="set_course(this);">' +
            '<label for="radio' + key + '">' + cache.options.course[key] + '</label>' +
            '</div>';
    }
    divCourse.innerHTML += str;
}

function get_groups(schedule_type, faculty, course) {
    groups = cache.settings[schedule_type][faculty][course];
    divGroup.innerHTML = '<h1>Группа</h1>';
    divInput.innerHTML = '';
    group = [];

    var str = '';

    for (var i = 0; i < groups.length; i++) {
        var key = groups[i];

        str += '<div class="option">' +
            '<input id="checkbox' + key + '" type="checkbox" name="group" value="' + key + '" onclick="set_group(this);">' +
            '<label for="checkbox' + key + '">' + cache.options.group[key] + '</label>' +
            '</div>';
    }
    divGroup.innerHTML += str;
}