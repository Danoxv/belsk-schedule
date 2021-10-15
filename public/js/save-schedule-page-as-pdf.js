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
    'Сохранено в ' +
    (orientation === 'l' ? 'альбомном' : 'портретном') +
    ' режиме'
  ;

  if (usedDefaultOrientation) {
    orientationInfo += ' [автоматически определить не удалось]';
  }

  document.getElementById('orientation-info').innerHTML = orientationInfo;

  html2pdf(document.getElementById('schedule-page-content'), {
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