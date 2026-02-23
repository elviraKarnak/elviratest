(function(){
  function $(id){ return document.getElementById(id); }
  function fmt(n){ n = Number(n||0); return '$' + n.toLocaleString(undefined,{maximumFractionDigits:0}); }
  function num(v){ if(v===''||isNaN(v)) return 0; return Number(v); }

  // Load Chart.js UMD safely (no module imports)
  function loadChart(){
    return new Promise(function(resolve, reject){
      if (window.Chart) return resolve(window.Chart);
      var s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
      s.onload = function(){ resolve(window.Chart); };
      s.onerror = function(){ reject(new Error('Chart.js failed to load')); };
      document.head.appendChild(s);
    });
  }

  function payload(){
    return {
      age: num($('wprc-age').value),
      retireAge: num($('wprc-retireAge').value),
      salary: num($('wprc-salary').value),
      currentBalance: num($('wprc-balance').value),
      sgRate: num($('wprc-sgRate').value)/100,
      volPreTaxPct: num($('wprc-volPre').value)/100,
      volAfterTaxAnnual: num($('wprc-volAfter').value),
      salaryGrowth: num($('wprc-salGrowth').value)/100,
      returnNominal: num($('wprc-return').value)/100,
      earningsTax: num($('wprc-earnTax').value)/100,
      contribTax: num($('wprc-contribTax').value)/100,
      feePct: num($('wprc-feePct').value)/100,
      feeFixed: num($('wprc-feeFixed').value),
      inflation: num($('wprc-infl').value)/100,
      longevityAge: num($('wprc-longevity').value),
      agePensionPa: num($('wprc-pension').value),
    };
  }

  var chart = null;

  function drawChart(ChartJS, series){
    var ctx = $('wprc-chart').getContext('2d');
    var labels = series.map(function(p){ return p[0]; });
    var data   = series.map(function(p){ return p[1]; });
    if(chart){ chart.destroy(); }
    chart = new ChartJS(ctx, {
      type:'line',
      data:{ labels: labels, datasets:[{ label:'Projected Super Balance', data: data }] },
      options:{
        responsive:true, maintainAspectRatio:false,
        scales:{ y:{ ticks:{ callback:function(v){ return '$'+Number(v).toLocaleString(); } } } }
      }
    });
  }

  function run(){
    $('wprc-error').style.display = 'none';
    var body = JSON.stringify(payload());
    fetch(WPRC_CFG.restUrl, {
      method:'POST',
      headers:{ 'Content-Type':'application/json' },
      body: body
    })
    .then(function(r){ return r.json().then(function(j){ return { ok:r.ok, data:j }; }); })
    .then(function(resp){
      if(!resp.ok) throw new Error(resp.data && resp.data.error || 'Calculation error');
      var r = resp.data;
      $('wprc-balance-label').textContent = 'Projected balance at ' + r.atRetirement.age;
      $('wprc-balance-out').textContent   = fmt(r.atRetirement.balance);
      $('wprc-super-income').textContent  = fmt(r.income.annualSuperIncomeToday);
      $('wprc-pension-out').textContent   = fmt(r.income.annualAgePensionToday);
      $('wprc-total').textContent         = fmt(r.income.annualTotalIncomeToday);
      loadChart().then(function(ChartJS){
        drawChart(ChartJS, r.chart.balanceSeries || []);
      }).catch(function(){
        // Silently ignore chart failure
      });
    })
    .catch(function(err){
      var el = $('wprc-error');
      el.textContent = err.message || String(err);
      el.style.display = 'block';
    });
  }

  document.addEventListener('click', function(e){
    if(e.target && e.target.id === 'wprc-run'){ run(); }
  });

  // First run after DOM ready
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();