function saveSchedulePageAsPdf(fileName = 'schedule') {
  html2pdf(document.getElementById('schedule-page-content'), {
    filename:     fileName + '.pdf',
    jsPDF:        { orientation: 'landscape' }
  });
}