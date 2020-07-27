function clearString(str) {
  return str.replace(/\s+/g, ' ').trim();
}

const SCHEDULE_URL = 'http://schedule.ispu.ru/';

const express = require('express');
const app = express();
const PORT = 5000;

const path = require('path');
const htmlParser = require('node-html-parser');
const FormData = require('form-data');
const axios = require('axios');

axios.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
axios.defaults.headers.post['User-Agent'] =
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.61 Safari/537.36';

const admin = require('firebase-admin');
const serviceAccount = require(path.resolve(__dirname, 'firebase-key.json'));

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount),
  databaseURL: 'https://schedule-ispu.firebaseio.com',
});

const db = admin.database();

const dataParams = {
  scheduleType: 'ctl00$ContentPlaceHolder1$ddlSchedule',
  faculty: 'ctl00$ContentPlaceHolder1$ddlSubDivision',
  year: 'ctl00$ContentPlaceHolder1$ddlCorse',
  group: 'ctl00$ContentPlaceHolder1$ddlObjectValue',
  subgroup: 'ctl00$ContentPlaceHolder1$rblSubGroup',
};

function getWeekNumber(date) {
  let d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));

  d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));

  let yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
  let currentWeekNumber = Math.ceil(((d - yearStart) / (24 * 60 * 60 * 1000) + 1) / 7);

  return currentWeekNumber;
}

async function getCurrentWeekNumber(date) {
  const creationDate = (await db.ref('meta').child('creationDate').once('value')).val();
  const difference = getWeekNumber(date) - getWeekNumber(new Date(creationDate));
  const week = (await db.ref('meta').child('weekNumber').once('value')).val();

  if (difference % 2 === 0) return week;
  if (week === 1) return week + 1;

  return week - 1;
}

let globalCache = {};

async function getScheduleByKeys({ stk, fk, yk, gk, sgk }) {
  const scheduleKey = [stk, fk, yk, gk, sgk].join('_');
  const weekNumber = await getCurrentWeekNumber(new Date());
  let schedule;

  if (globalCache.schedules[scheduleKey]) {
    schedule = { ...globalCache.schedules[scheduleKey], weekNumber };

    return schedule;
  }

  const scheduleParams = globalCache.params;
  const metaData = globalCache.meta;

  schedule = {
    weekNumber,
    type: scheduleParams[stk].value,
    faculty: scheduleParams[stk].faculties[fk].value,
    year: scheduleParams[stk].faculties[fk].years[yk].value,
    group: scheduleParams[stk].faculties[fk].years[yk].groups[gk].value,
    subgroup: scheduleParams[stk].faculties[fk].years[yk].groups[gk].subgroups[sgk],
    weeks: {},
  };

  let weeks = globalCache.tables[scheduleKey];

  weeks.forEach((week, weekIndex) => {
    const weekKey = (weekIndex + 1).toString();

    schedule.weeks[weekKey] = {};

    for (let di = 0; di < metaData.days.length; di++) {
      const dayKey = metaData.days[di];

      schedule.weeks[weekKey][dayKey] = {};

      const day = week[di];

      if (day) {
        for (let lai = 0; lai < metaData.lessonTimePeriods.length; lai++) {
          const lessonArrayKey = (lai + 1).toString();

          schedule.weeks[weekKey][dayKey][lessonArrayKey] = [];

          let lessonArray = day[lai];

          if (!lessonArray) {
            schedule.weeks[weekKey][dayKey][lessonArrayKey].push(metaData.lessonTimePeriods[lai]);

            continue;
          }

          if (!lessonArray.length) lessonArray = [lessonArray];

          for (let li = 0; li < lessonArray.length; li++) {
            const lesson = lessonArray[li];
            let lessonObj = { ...metaData.lessonTimePeriods[lai] };

            for (let lessonKey in lesson) {
              if (lessonKey === 'dates' || lessonKey === 'isResearch') {
                lessonObj[lessonKey] = lesson[lessonKey];
              } else if (lessonKey === 'type') {
                lessonObj.type = metaData.lessonTypes[lesson.type];
              } else {
                lessonObj[lessonKey] = metaData[lessonKey + 's'][lesson[lessonKey]];
              }
            }

            schedule.weeks[weekKey][dayKey][lessonArrayKey][li] = lessonObj;
          }
        }
      } else {
        metaData.lessonTimePeriods.forEach((timePeriod, timePeriodIndex) => {
          const lessonKey = (timePeriodIndex + 1).toString();

          schedule.weeks[weekKey][dayKey][lessonKey] = [timePeriod];
        });
      }
    }
  });

  globalCache.schedules[scheduleKey] = schedule;

  delete globalCache.tables[scheduleKey];

  return schedule;
}

function createScheduleTableStructure(document, metaData) {
  let scheduleTableStructure = [];
  const tds = document.querySelectorAll('.shedule td');
  let weekIndex = 0;
  let dayIndex = 0;
  let lessonIndex = 0;

  const colors = {
    FFFFCC: ['лекция', 'лек.'],
    E5FFE5: ['семинар', 'сем.'],
    FFEFFD: ['лабораторная', 'лаб.'],
  };

  for (let i = 0; i < tds.length; i++) {
    const td = tds[i];

    if (td.getAttribute('width') !== '150px') continue;
    if (scheduleTableStructure[weekIndex] && scheduleTableStructure[weekIndex][dayIndex] && scheduleTableStructure[weekIndex][dayIndex][lessonIndex])
      dayIndex++;
    if (dayIndex === 6) {
      dayIndex = 0;

      lessonIndex++;

      if (lessonIndex === 7) {
        dayIndex = 0;
        lessonIndex = 0;

        weekIndex++;

        if (weekIndex === 2) break;
      }

      continue;
    }

    const tdParts = td.text.split(';');

    tdParts.forEach((tdPart, tdPartIndex) => {
      let lessonData = {};

      if (tdPart !== '') {
        let tdText = clearString(tdPart);

        if (tdText === 'Элект. курсы по физ. культ.' || tdText === 'Физкультура и спорт' || tdText === 'Физическая культура и спорт') {
          lessonData.type = 'семинар';
          lessonData.subject = 'Физкультура';
        } else {
          tdText = tdText.replace(/[^А-Яа-яЁё0-9\s.\/"]/g, '');

          // удаление лишнего пробела в инициалах преподавателей

          let professorInitialsIndex = tdText.search(/\s[\А-Я]\.\s[\А-Я]\./); // ' X. X.'

          if (professorInitialsIndex !== -1) tdText = tdText.substring(0, professorInitialsIndex + 3) + tdText.substring(professorInitialsIndex + 4);

          // определение наличия УПМ (учебно-производственная мастерская) вместо стандартного обозначения аудитории и удаление пробела

          let strangeRoomIndex = tdText.indexOf('УПМ ');

          if (strangeRoomIndex !== -1) tdText = tdText.substring(0, strangeRoomIndex + 3) + tdText.substring(strangeRoomIndex + 4);

          // определение наличия 'НИР'

          let researchIndex = tdText.indexOf('НИР');

          if (researchIndex !== -1) {
            tdText = clearString(tdText.substring(0, researchIndex) + tdText.substring(researchIndex + 3));
            lessonData.isResearch = true;
          }

          let tdTextParts = [];

          if (tdText.indexOf('к.пр.') === -1) {
            const lessonType = colors[td.getAttribute('style').split('#')[1]];

            tdTextParts = tdText.split(lessonType[1]);
            lessonData.type = lessonType[0];
          } else {
            lessonData.type = 'курсовое проектирование';
            tdTextParts = tdText.split('к.пр.');
          }

          let probSubject = '';

          if (tdTextParts.length === 2) {
            probSubject = clearString(tdTextParts[0]);
            tdTextParts = clearString(tdTextParts[1]).split(' ');

            if (tdTextParts.length === 1) {
              lessonData.room = tdTextParts[0];
            } else {
              lessonData.professor = tdTextParts.splice(0, 2).join(' ');
              lessonData.room = tdTextParts[0];
            }
          } else {
            tdTextParts = tdTextParts[0].split(' ');
            lessonData.room = tdTextParts.splice(tdTextParts.length - 1)[0];

            if (/[\А-Я]\.[\А-Я]\./.test(tdTextParts.slice(-1)[0])) {
              lessonData.professor = tdTextParts.splice(tdTextParts.length - 2).join(' ');
            }

            probSubject = tdTextParts.join(' ');
          }

          let lessonDates = [...probSubject.matchAll(/[\0-9][\0-9]\.[\0-9][\0-9]/g)];

          if (!lessonDates.length) lessonDates = [...probSubject.matchAll(/[\0-9]\.[\0-9][\0-9]/g)];
          if (lessonDates.length) {
            lessonData.dates = [];

            lessonDates.forEach(date => {
              lessonData.dates.push(date[0]);
            });

            const lastDate = lessonDates.slice(-1)[0];

            lessonData.subject = clearString(probSubject.substring(lastDate.index + lastDate[0].length + 1));
          } else {
            lessonData.subject = probSubject;
          }

          if (lessonData.subject === '') delete lessonData.subject;
        }
      }

      if (!scheduleTableStructure[weekIndex]) scheduleTableStructure[weekIndex] = [];
      if (!scheduleTableStructure[weekIndex][dayIndex]) scheduleTableStructure[weekIndex][dayIndex] = [];
      if (!scheduleTableStructure[weekIndex][dayIndex][lessonIndex]) scheduleTableStructure[weekIndex][dayIndex][lessonIndex] = [];

      for (let lessonDataKey in lessonData) {
        if (lessonDataKey === 'dates' || lessonDataKey === 'isResearch') continue;
        if (lessonDataKey === 'type') {
          lessonData.type = metaData.lessonTypes.indexOf(lessonData.type);
        } else {
          const metaDataArray = metaData[lessonDataKey + 's'];
          const index = metaDataArray.indexOf(lessonData[lessonDataKey]);

          if (index >= 0) {
            lessonData[lessonDataKey] = index;
          } else {
            metaDataArray.push(lessonData[lessonDataKey]);

            lessonData[lessonDataKey] = metaDataArray.length - 1;
          }
        }
      }

      if (tdParts.length > 1) {
        scheduleTableStructure[weekIndex][dayIndex][lessonIndex][tdPartIndex] = lessonData;
      } else {
        scheduleTableStructure[weekIndex][dayIndex][lessonIndex] = lessonData;
      }

      if (td.getAttribute('rowspan') === '2') {
        if (!scheduleTableStructure[weekIndex][dayIndex][lessonIndex + 1]) scheduleTableStructure[weekIndex][dayIndex][lessonIndex + 1] = [];

        if (tdParts.length > 1) {
          scheduleTableStructure[weekIndex][dayIndex][lessonIndex + 1][tdPartIndex] = lessonData;
        } else {
          scheduleTableStructure[weekIndex][dayIndex][lessonIndex + 1] = lessonData;
        }
      }
    });

    dayIndex++;
  }

  return scheduleTableStructure;
}

async function parseSchedule() {
  let data = {};
  let document;
  let elements;
  let isFirstVisit = true;

  async function getDocument(param, value) {
    let result;

    if (isFirstVisit) {
      result = await axios.get(SCHEDULE_URL);
      isFirstVisit = false;
    } else {
      data = {
        ...data,
        [param]: value,
      };

      let formData = new FormData();

      Object.keys(data).forEach(key => {
        formData.append(key, data[key]);
      });

      let isSucceeded = false;

      while (!isSucceeded) {
        try {
          result = await axios.post(SCHEDULE_URL, formData, {
            headers: { ...formData.getHeaders() },
          });

          isSucceeded = true;
        } catch (err) {
          console.error('Ошибка при запросе, пробуем снова...', new Error(err));
          // continue;
        }

        if (!isSucceeded) continue;
      }
    }

    document = htmlParser.parse(result.data);

    elements = {
      scheduleTypes: document.querySelectorAll(`#${dataParams.scheduleType.split('$').splice(1, 2).join('_')} option`),
      faculties: document.querySelectorAll(`#${dataParams.faculty.split('$').splice(1, 2).join('_')} option`),
      years: document.querySelectorAll(`#${dataParams.year.split('$').splice(1, 2).join('_')} option`),
      groups: document.querySelectorAll(`#${dataParams.group.split('$').splice(1, 2).join('_')} option`),
      subgroups: document.querySelectorAll(`#${dataParams.subgroup.split('$').splice(1, 2).join('_')} input`),
    };

    data = {
      __VIEWSTATE: document.querySelector('#__VIEWSTATE').getAttribute('value'),
      __EVENTVALIDATION: document.querySelector('#__EVENTVALIDATION').getAttribute('value'),
      [dataParams.scheduleType]: elements.scheduleTypes.find(option => option.getAttribute('selected') === 'selected').getAttribute('value'),
      [dataParams.faculty]: elements.faculties.find(option => option.getAttribute('selected') === 'selected').getAttribute('value'),
      [dataParams.year]: elements.years.find(option => option.getAttribute('selected') === 'selected').getAttribute('value'),
      [dataParams.group]: elements.groups.find(option => option.getAttribute('selected') === 'selected').getAttribute('value'),
      [dataParams.subgroup]: elements.subgroups.find(input => input.getAttribute('checked') === 'checked').getAttribute('value'),
    };
  }

  // формирование объекта со всеми расписаниями

  let scheduleParams = {};
  let scheduleTables = {};
  let metaData = {
    days: ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
    lessonTypes: ['лекция', 'семинар', 'лабораторная', 'курсовое проектирование'],
    lessonTimePeriods: [
      { timeBegin: '8:00', timeEnd: '9:35' },
      { timeBegin: '9:50', timeEnd: '11:25' },
      { timeBegin: '11:40', timeEnd: '13:15' },
      { timeBegin: '14:00', timeEnd: '15:35' },
      { timeBegin: '15:50', timeEnd: '17:25' },
      { timeBegin: '17:40', timeEnd: '19:15' },
      { timeBegin: '19:25', timeEnd: '21:00' },
    ],
    subjects: [],
    professors: [],
    rooms: [],
  };

  await getDocument(); // первое посещение сайта (получение значений полей валидации и др. для дальнейшего парсинга)

  let weekNumber = document.querySelector('#form1 span').text.includes('II') ? 2 : 1;
  let timeBegin = Date.now();

  for (let st = 0; st < elements.scheduleTypes.length; st++) {
    let ste = elements.scheduleTypes[st]; // schedule type element
    let stk = ste.getAttribute('value'); // schedule type key

    scheduleParams[stk] = {
      value: clearString(ste.text),
      faculties: {},
    };

    for (let f = 0; f < elements.faculties.length; f++) {
      let fe = elements.faculties[f]; // faculty element
      let fk = fe.getAttribute('value'); // faculty key

      scheduleParams[stk].faculties[fk] = {
        value: clearString(fe.text),
        years: {},
      };

      for (let y = 0; y < elements.years.length; y++) {
        let ye = elements.years[y]; // year element
        let yk = ye.getAttribute('value'); // year key

        scheduleParams[stk].faculties[fk].years[yk] = {
          value: clearString(ye.text),
          groups: {},
        };

        for (let g = 0; g < elements.groups.length; g++) {
          let ge = elements.groups[g]; // group element
          let gk = ge.getAttribute('value'); // group key

          scheduleParams[stk].faculties[fk].years[yk].groups[gk] = {
            value: clearString(ge.text),
            subgroups: {},
          };

          for (let sg = 0; sg < elements.subgroups.length; sg++) {
            let sge = elements.subgroups[sg]; // subgroup element
            let sgk = sge.getAttribute('value'); // subgroup key
            let subgroupValue = new Array(parseInt(sge.getAttribute('id').split('_')[2]) + 1).fill('x').join(''); // формирование крестов подгруппы

            scheduleParams[stk].faculties[fk].years[yk].groups[gk].subgroups[sgk] = subgroupValue;
            scheduleTables[[stk, fk, yk, gk, sgk].join('_')] = createScheduleTableStructure(document, metaData);

            console.log((Date.now() - timeBegin) / 1000, [stk, fk, yk, gk, sgk].join('_'));

            // if (Object.keys(scheduleTables).length > 34) {
            //   console.log(metaData);

            //   return 0;
            // }

            if (sg < elements.subgroups.length - 1) await getDocument(dataParams.subgroup, elements.subgroups[sg + 1].getAttribute('value'));
          }

          // запрос расписания для следующей группы

          if (g < elements.groups.length - 1) await getDocument(dataParams.group, elements.groups[g + 1].getAttribute('value'));
        }

        // запрос расписания для следующего курса

        if (y < elements.years.length - 1) await getDocument(dataParams.year, elements.years[y + 1].getAttribute('value'));
      }

      // запрос расписания для следующего факультета

      if (f < elements.faculties.length - 1) await getDocument(dataParams.faculty, elements.faculties[f + 1].getAttribute('value'));
    }

    // запрос расписания для следующего типа расписания

    if (st < elements.scheduleTypes.length - 1) await getDocument(dataParams.scheduleType, elements.scheduleTypes[st + 1].getAttribute('value'));
  }

  const timeNow = Date.now();
  const size = JSON.stringify(scheduleParams).length + JSON.stringify(scheduleTables).length + JSON.stringify(metaData).length;

  metaData = {
    ...metaData,
    creationDate: timeNow,
    elapsedTime: timeNow - timeBegin,
    size,
    weekNumber,
  };

  console.log(metaData);

  console.log(`Расписание спарсено за ${metaData.elapsedTime / 1000} сек. (${size} байт)! 😜`);

  globalCache = {
    params: scheduleParams,
    meta: metaData,
    tables: scheduleTables,
    schedules: {},
  };

  console.log(`Загружаем расписание в Firebase... 😤`);

  await db.ref('params').set(globalCache.params);
  await db.ref('tables').set(globalCache.tables);
  await db.ref('meta').set(globalCache.meta);

  console.log(`Расписание загружено в Firebase! 😎`);
}

app.get('/api/parse-schedule', (req, res) => {
  parseSchedule();

  res.json(['Парсинг расписания запущен!']);
});

app.get('/api/get-schedule/:stk?/:fk?/:yk?/:gk?/:sgk?', async (req, res) => {
  for (let paramKey in req.params) {
    if (!req.params[paramKey] || req.params[paramKey] === 'undefined') {
      return res.json({
        error: 'Недостаточно параметров для загрузки расписания!',
      });
    }
  }

  res.json(await getScheduleByKeys(req.params));
});

app.listen(PORT, async err => {
  if (err) throw new Error(err);

  globalCache = {
    params: (await db.ref('params').once('value')).val(),
    meta: (await db.ref('meta').once('value')).val(),
    tables: (await db.ref('tables').once('value')).val(),
    schedules: {},
  };

  console.log(`Сервер расписания запущен по адресу: http://localhost:${PORT} 😉`);
});
