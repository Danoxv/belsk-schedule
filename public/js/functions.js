/*
 * Helpers
 */
function O(elementId) { return typeof elementId === 'object' ? elementId : document.getElementById(elementId) }
function S(elementId) { return O(elementId).style }
function C(className) { return document.getElementsByClassName(className) }
function byName(name) { return document.getElementsByName(name) }

/**
 * @param fileName
 */
function saveSchedulePageAsPdf(fileName = 'schedule') {
  let orientation = detectOrientation();

  let usedDefaultOrientation = false;
  if (!orientation) {
    usedDefaultOrientation = true;
    orientation = 'l';
  }

  let orientationInfo =
    'Скачана ' +
    (orientation === 'l' ? 'альбомная' : 'портретная') +
    ' версия'
  ;

  if (usedDefaultOrientation) {
    orientationInfo += ' [автоматически определить не удалось]';
  }

  O('orientation-info').innerHTML = orientationInfo;

  html2pdf(O('schedule-page-content'), {
    filename:     fileName + '.pdf',
    jsPDF:        { orientation: orientation }
  });
}

/**
 * @returns {string|undefined} 'p' (portrait) or 'l' (landscape)
 */
function detectOrientation() {
  const o = (screen.orientation || {}).type || screen.mozOrientation || screen.msOrientation;

  if (!o) return undefined;

  return o[0];
}

function onScheduleFileChange() {
  let scheduleLinkElem = getCheckedScheduleLinkRadio();
  if (!scheduleLinkElem) return;

  scheduleLinkElem.checked = false;
}

function onScheduleLinkChange() {
  let scheduleFileElem = O('scheduleFile');
  if (!scheduleFileElem) return;

  scheduleFileElem.value = null;
}

function getCheckedScheduleLinkRadio() {
  const radios = byName('scheduleLink');

  for (var i = 0, length = radios.length; i < length; i++) {
    if (radios[i].checked) {
      return radios[i];
    }
  }

  return null;
}