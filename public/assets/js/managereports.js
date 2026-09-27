function showTab(name) {
    var summary    = document.getElementById('summaryTab');
    var raw        = document.getElementById('rawTab');
    var btnSummary = document.getElementById('tabSummary');
    var btnRaw     = document.getElementById('tabRaw');
    if (name === 'summary') {
        summary.style.display = 'block'; raw.style.display = 'none';
        btnSummary.classList.add('rpt-tab-btn--active');
        btnRaw.classList.remove('rpt-tab-btn--active');
    } else {
        summary.style.display = 'none'; raw.style.display = 'block';
        btnSummary.classList.remove('rpt-tab-btn--active');
        btnRaw.classList.add('rpt-tab-btn--active');
    }
}

function openShareModal()  { document.getElementById('shareOverlay').style.display = 'flex'; }
function closeShareModal() { document.getElementById('shareOverlay').style.display = 'none'; }
