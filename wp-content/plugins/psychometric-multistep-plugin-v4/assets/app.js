(function(){
    const container = document.getElementById('psych-test-app');
    const configEl = document.getElementById('psych-questions-config');
    if (!container || !configEl) return;
    const steps = JSON.parse(configEl.textContent);
    let curStep = 0;
    const answers = {}; // id -> 1..7

    function render(){
        container.innerHTML = '';
        const wrapper = document.createElement('div');
        wrapper.className = 'psych-wrapper';
        const header = document.createElement('div');
        header.className = 'psych-header';
        header.innerHTML = `<h2>Psychometric Test</h2><p>Step ${curStep+1} of ${steps.length}</p>`;
        wrapper.appendChild(header);

        const progress = document.createElement('div');
        progress.className = 'psych-progress';
        for (let i=0;i<steps.length;i++){
            const stepDot = document.createElement('div');
            stepDot.className = 'step-dot'+(i===curStep?' active':'') ;
            stepDot.innerText = i+1;
            progress.appendChild(stepDot);
        }
        wrapper.appendChild(progress);

        const stepBlock = document.createElement('div');
        stepBlock.className = 'step';
        const stepQuestions = steps[curStep];
        stepQuestions.forEach((q, idx)=>{
            const qWrap = document.createElement('div');
            qWrap.className = 'question';
            const qt = document.createElement('div');
            qt.className = 'qtext';
            qt.innerText = q.text;
            qWrap.appendChild(qt);
            const radios = document.createElement('div');
            radios.className = 'radios';
            for (let v=1; v<=7; v++){
                const label = document.createElement('label');
                label.className = 'circle';
                label.setAttribute('data-value', v);
                label.innerHTML = `<input type="radio" name="${q.id}" value="${v}" aria-label="Option ${v}"><span class="inner-circle"></span><svg class="tick" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6L9 17l-5-5" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
                label.addEventListener('click', (e)=>{
                    const val = v;
                    answers[q.id] = val;
                    const parent = label.parentNode;
                    parent.querySelectorAll('.circle').forEach(c=>c.classList.remove('selected'));
                    label.classList.add('selected');
                    // enable next question immediately
                    enableQuestionsInStep();
                });
                radios.appendChild(label);
            }
            qWrap.appendChild(radios);
            stepBlock.appendChild(qWrap);
        });

        if (curStep === steps.length - 1){
            const cand = document.createElement('div');
            cand.className = 'candidate-info';
            cand.innerHTML = `
                <h4>Candidate details</h4>
                <label>Name <input id="cand_name" type="text"></label>
                <label>Email <input id="cand_email" type="email"></label>
                <label>Phone <input id="cand_phone" type="text"></label>
                <label>Interview date <input id="cand_interview" type="date"></label>
            `;
            stepBlock.appendChild(cand);
        }

        const nav = document.createElement('div');
        nav.className = 'nav';
        if (curStep > 0){
            const back = document.createElement('button'); back.className='btn back'; back.innerText='Back';
            back.addEventListener('click', ()=>{ curStep--; render(); });
            nav.appendChild(back);
        }
        const next = document.createElement('button');
        next.className = 'btn next';
        next.innerText = curStep === steps.length - 1 ? 'Submit' : 'Next';
        next.addEventListener('click', onNext);
        nav.appendChild(next);

        wrapper.appendChild(stepBlock);
        wrapper.appendChild(nav);
        container.appendChild(wrapper);

        enableQuestionsInStep();
        restoreSelections();
    }

    function enableQuestionsInStep(){
        const qnodes = container.querySelectorAll('.step .question');
        for (let i=0;i<qnodes.length;i++){
            const qn = qnodes[i];
            const qid = steps[curStep][i].id;
            if (i===0){ qn.classList.remove('blur'); qn.querySelectorAll('input').forEach(inp=>inp.disabled=false); }
            else {
                const prevId = steps[curStep][i-1].id;
                if (answers[prevId]){
                    qn.classList.remove('blur'); qn.querySelectorAll('input').forEach(inp=>inp.disabled=false);
                } else {
                    qn.classList.add('blur'); qn.querySelectorAll('input').forEach(inp=>inp.disabled=true);
                }
            }
        }
    }

    function restoreSelections(){
        const qnodes = container.querySelectorAll('.step .question');
        for (let i=0;i<qnodes.length;i++){
            const qn = qnodes[i];
            const qid = steps[curStep][i].id;
            if (answers[qid]){
                const sel = qn.querySelector(`input[value="${answers[qid]}"]`);
                if (sel) sel.checked = true;
                const lab = qn.querySelector(`label[data-value="${answers[qid]}"]`);
                if (lab) lab.classList.add('selected');
            }
        }
    }

    function validateStep(){
        const stepQuestions = steps[curStep];
        for (let q of stepQuestions){
            if (!answers[q.id]) return false;
        }
        return true;
    }

    function onNext(){
        if (!validateStep()){
            alert('Please answer all questions in this step.');
            return;
        }
        if (curStep === steps.length -1){
            const name = document.getElementById('cand_name').value.trim();
            const email = document.getElementById('cand_email').value.trim();
            if (!name || !email){ alert('Please enter candidate name and email.'); return; }
            const phone = document.getElementById('cand_phone').value.trim();
            const interview_date = document.getElementById('cand_interview').value.trim();
            submitAll({name,email,phone,interview_date});
            return;
        }
        curStep++;
        render();
    }

    function submitAll(candidate){
        const payload = { answers: answers, candidate: candidate };
        const data = new FormData();
        data.append('action','psych_submit');
        data.append('nonce', psychL10n.nonce);
        data.append('payload', JSON.stringify(payload));

        fetch(psychL10n.ajax_url, { method:'POST', body: data, credentials: 'same-origin' })
        .then(r=>r.json())
        .then(d=>{
            if (d.success){
                container.innerHTML = `<div class="result"><h3 style="color:${getRiskColor(d.data.risk)}">Result: ${d.data.risk}</h3><p>Score: ${d.data.score}</p><p>Saved (id: ${d.data.post_id})</p></div>`;
            } else {
                alert('Error: ' + (d.data || 'Unknown'));
            }
        })
        .catch(err => { alert('Network error'); console.error(err); });
    }

    function getRiskColor(risk){
        if (!risk) return '#333';
        risk = risk.toLowerCase();
        if (risk.indexOf('safe')!==-1) return '#0b8a3a';
        if (risk.indexOf('moderate')!==-1) return '#b58900';
        if (risk.indexOf('concerning')!==-1) return '#d35400';
        return '#a31616';
    }

    render();
})();