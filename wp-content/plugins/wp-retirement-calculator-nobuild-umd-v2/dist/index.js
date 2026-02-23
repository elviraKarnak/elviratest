(function(){
  function $(sel, ctx){ return (ctx||document).querySelector(sel); }
  function el(html){ const d=document.createElement('div'); d.innerHTML=html.trim(); return d.firstChild; }
  function num(v){ return (v===""||isNaN(v)) ? 0 : Number(v); }
  function currency(n){ return '$' + Number(n||0).toLocaleString(undefined,{maximumFractionDigits:0}); }

  function ui(container){
    container.innerHTML = [
      '<div class="wprc-app">',
      '  <header class="hero">',
      '    <h2>Retirement Planner</h2>',
      '    <p>Estimate your retirement income and projected super balance. Results are indicative only and shown in today\\s dollars.</p>',
      '  </header>',
      '  <div class="layout">',
      '    <div class="left">',
      '      <section class="card"><h3>Basics</h3><div class="grid">',
      '        <label class="field"><span>Current age</span><input id="age" type="number" value="35" min="18" max="74"></label>',
      '        <label class="field"><span>Retirement age</span><input id="retireAge" type="number" value="67" min="50" max="75"></label>',
      '        <label class="field"><span>Current salary (annual)</span><input id="salary" type="number" value="90000" step="1000"></label>',
      '        <label class="field"><span>Current super balance</span><input id="currentBalance" type="number" value="80000" step="1000"></label>',
      '      </div></section>',
      '      <section class="card"><h3>Contributions</h3><div class="grid">',
      '        <label class="field"><span>Employer SG rate</span><input id="sgRate" type="number" value="12" step="0.1"></label>',
      '        <label class="field"><span>Voluntary pre-tax (salary sacrifice)</span><input id="volPreTaxPct" type="number" value="0" step="0.1"></label>',
      '        <label class="field"><span>Voluntary after-tax (annual)</span><input id="volAfterTaxAnnual" type="number" value="0" step="500"></label>',
      '      </div></section>',
      '      <section class="card"><h3>Assumptions</h3><div class="grid">',
      '        <label class="field"><span>Investment return (nominal p.a.)</span><input id="returnNominal" type="number" value="6.5" step="0.1"></label>',
      '        <label class="field"><span>Earnings tax (effective)</span><input id="earningsTax" type="number" value="7.0" step="0.1"></label>',
      '        <label class="field"><span>Contribution tax</span><input id="contribTax" type="number" value="15.0" step="0.1"></label>',
      '        <label class="field"><span>Fees (% of balance)</span><input id="feePct" type="number" value="0.7" step="0.1"></label>',
      '        <label class="field"><span>Fees (fixed $ p.a.)</span><input id="feeFixed" type="number" value="100" step="10"></label>',
      '        <label class="field"><span>Salary growth</span><input id="salaryGrowth" type="number" value="3.5" step="0.1"></label>',
      '        <label class="field"><span>Inflation</span><input id="inflation" type="number" value="2.5" step="0.1"></label>',
      '        <label class="field"><span>Plan to age</span><input id="longevityAge" type="number" value="92" min="60" max="110"></label>',
      '        <label class="field"><span>Expected Age Pension (annual)</span><input id="agePensionPa" type="number" value="0" step="500"></label>',
      '      </div></section>',
      '      <div class="actions"><button id="calcBtn">Update results</button></div>',
      '    </div>',
      '    <div class="right">',
      '      <section class="card">',
      '        <h3>Results</h3>',
      '        <div class="kpis">',
      '          <div class="kpi"><span class="label">Projected balance at <span id="kpi-age">67</span></span><strong id="kpi-balance">$0</strong></div>',
      '          <div class="kpi"><span class="label">Annual income from super (today\\s $)</span><strong id="kpi-super-income">$0</strong></div>',
      '          <div class="kpi"><span class="label">Expected Age Pension (today\\s $)</span><strong id="kpi-pension">$0</strong></div>',
      '          <div class="kpi"><span class="label">Estimated total annual income</span><strong id="kpi-total-income">$0</strong></div>',
      '        </div>',
      '        <div class="chart">',
      '          <div class="wprc-toggle" id="chartToggle">',
      '            <button data-mode="balance" class="active">Super balance</button>',
      '            <button data-mode="income">Income</button>',
      '          </div>',
      '          <div style="height:8px"></div>',
      '          <canvas id="wprc-chart"></canvas>',
      '        </div>',
      '        <p class="disclaimer">This tool is for general information only and does not consider your personal objectives, financial situation or needs. Consider seeking independent advice.</p>',
      '      </section>',
      '    </div>',
      '  </div>',
      '</div>'
    ].join('');
  }

  function collectPayload(root){
    function gv(id){ return num($(id, root).value); }
    return {
      age: gv('#age'), retireAge: gv('#retireAge'), salary: gv('#salary'), currentBalance: gv('#currentBalance'),
      sgRate: gv('#sgRate')/100, volPreTaxPct: gv('#volPreTaxPct')/100, volAfterTaxAnnual: gv('#volAfterTaxAnnual'),
      salaryGrowth: gv('#salaryGrowth')/100, returnNominal: gv('#returnNominal')/100, earningsTax: gv('#earningsTax')/100,
      contribTax: gv('#contribTax')/100, feePct: gv('#feePct')/100, feeFixed: gv('#feeFixed'),
      inflation: gv('#inflation')/100, longevityAge: gv('#longevityAge'), agePensionPa: gv('#agePensionPa')
    };
  }

  function renderKPIs(root, result){
    $('#kpi-age', root).textContent = result.atRetirement.age;
    $('#kpi-balance', root).textContent = currency(result.atRetirement.balance);
    $('#kpi-super-income', root).textContent = currency(result.income.annualSuperIncomeToday);
    $('#kpi-pension', root).textContent = currency(result.income.annualAgePensionToday);
    $('#kpi-total-income', root).textContent = currency(result.income.annualTotalIncomeToday);
  }

  function makeChart(ctx, labels, values, label){
    return new Chart(ctx, {
      type:'line',
      data:{ labels, datasets:[{ label, data: values }]},
      options:{
        responsive:true, maintainAspectRatio:false,
        scales:{ y:{ ticks:{ callback:(v)=>'$'+Number(v).toLocaleString() } } }
      }
    });
  }

  function buildSeries(result, mode){
    if(mode==='income'){
      const labels = (result.chart.incomeSeries || []).map(p=>p[0]);
      const values = (result.chart.incomeSeries || []).map(p=>p[1]);
      return { labels, values, label:"Estimated annual income (today's $)" };
    } else {
      const labels = (result.chart.balanceSeries || []).map(p=>p[0]);
      const values = (result.chart.balanceSeries || []).map(p=>p[1]);
      return { labels, values, label:"Projected Super Balance" };
    }
  }

  function attach(root){
    const btn = $('#calcBtn', root);
    const canvas = $('#wprc-chart', root);
    const toggle = $('#chartToggle', root);
    let currentMode = 'balance';
    let chart;

    function setActive(mode){
      currentMode = mode;
      const buttons = toggle.querySelectorAll('button');
      buttons.forEach(b => b.classList.toggle('active', b.dataset.mode === mode));
    }

    async function run(){
      try{
        const payload = collectPayload(root);
        const res = await fetch(WPRC_CFG.restUrl, {
          method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
        });
        const data = await res.json();
        if(!res.ok) throw new Error(data?.error || 'Calculation error');
        renderKPIs(root, data);
        const s = buildSeries(data, currentMode);
        if(chart) chart.destroy();
        chart = makeChart(canvas.getContext('2d'), s.labels, s.values, s.label);
      }catch(e){
        alert(e.message);
      }
    }

    toggle.addEventListener('click', (e)=>{
      const b = e.target.closest('button'); if(!b) return;
      setActive(b.dataset.mode);
      // re-render chart using last known data by recomputing (simpler, keeps logic central)
      run();
    });

    btn.addEventListener('click', run);
    run(); // initial
  }

  document.addEventListener('DOMContentLoaded', function(){
    var container = document.getElementById('retcalc-root');
    if(!container) return;
    ui(container);
    attach(container);
  });
})();