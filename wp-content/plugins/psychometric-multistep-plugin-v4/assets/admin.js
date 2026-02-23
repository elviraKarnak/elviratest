jQuery(function($){
    var questions = psychAdminData.questions || [];

    function buildGUI(){
        var $wrap = $('#psych-questions-gui').empty();
        questions.forEach(function(step, sidx){
            var $card = $('<div class="psy-step-card"></div>');
            var $header = $('<div class="psy-step-header"><strong>Step '+(sidx+1)+'</strong> <div class="step-controls"><button class="button remove-step" data-step="'+sidx+'">Remove step</button> <button class="button add-q" data-step="'+sidx+'">Add question</button></div></div>');
            var $body = $('<div class="psy-step-body"></div>');
            var $list = $('<div class="psy-q-list"></div>');
            step.forEach(function(q, qidx){
                $list.append(renderQRow(q, sidx, qidx));
            });
            $body.append($list);
            $card.append($header).append($body);
            $wrap.append($card);
        });
        $wrap.append('<p><button id="add-step" class="button button-primary">Add Step</button></p>');
    }

    function renderQRow(q, sidx, qidx){
        var $r = $('<div class="psy-q-row"></div>');
        $r.append('<input class="q-id" value="'+q.id+'" readonly style="width:120px;margin-right:8px">');
        $r.append('<input class="q-text" value="'+escapeHtml(q.text)+'" style="width:58%;margin-right:8px">');
        var sel = '<select class="q-pol"><option value="positive">positive</option><option value="reverse">reverse</option></select>';
        $r.append(sel);
        $r.find('.q-pol').val(q.polarity || 'positive');
        $r.append('<button class="button remove-q" data-step="'+sidx+'" data-q="'+qidx+'">Remove</button>');
        $r.append('<button class="button move-up" data-step="'+sidx+'" data-q="'+qidx+'">↑</button>');
        $r.append('<button class="button move-down" data-step="'+sidx+'" data-q="'+qidx+'">↓</button>');
        return $r;
    }

    function escapeHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    buildGUI();

    // add handlers
    $('#psych-questions-gui').on('click', '.add-q', function(){
        var s = $(this).data('step');
        var newid = 'q' + (Math.floor(Math.random()*90000)+10000);
        questions[s].push({id:newid, text:'New question', polarity:'positive'});
        buildGUI();
    });

    // remove question
    $('#psych-questions-gui').on('click', '.remove-q', function(){
        var s = $(this).data('step'), q = $(this).data('q');
        questions[s].splice(q,1); buildGUI();
    });

    // remove step (fixed)
    $('#psych-questions-gui').on('click', '.remove-step', function(){
        var s = $(this).data('step');
        if (!confirm('Remove this step and all its questions?')) return;
        questions.splice(s,1);
        buildGUI();
    });

    $('#psych-questions-gui').on('click', '.move-up', function(){
        var s = $(this).data('step'), q = $(this).data('q');
        if (q===0) return;
        var a = questions[s]; var t = a[q-1]; a[q-1]=a[q]; a[q]=t; buildGUI();
    });
    $('#psych-questions-gui').on('click', '.move-down', function(){
        var s = $(this).data('step'), q = $(this).data('q');
        var a = questions[s]; if (q===a.length-1) return;
        var t = a[q+1]; a[q+1]=a[q]; a[q]=t; buildGUI();
    });

    $('#add-step').on('click', function(){
        var newStep = [];
        for(var i=0;i<6;i++){ newStep.push({id:'q'+(Math.floor(Math.random()*90000)+10000), text:'New question', polarity:'positive'}); }
        questions.push(newStep);
        buildGUI();
    });

    $('#psych-questions-gui').on('click', '.remove-step', function(){
        var s = $(this).data('step');
        if (!confirm('Remove this step and all its questions?')) return;
        questions.splice(s,1);
        buildGUI();
    });

    $(document).on('click', '#psych-save-questions', function(){
        // collect
        $('#psych-questions-gui .psy-step-card').each(function(sidx){
            questions[sidx] = [];
            $(this).find('.psy-q-row').each(function(qidx){
                var id = $(this).find('.q-id').val();
                var text = $(this).find('.q-text').val();
                var pol = $(this).find('.q-pol').val();
                questions[sidx].push({id:id, text:text, polarity:pol});
            });
        });
        $('#psych-hidden-questions').val(JSON.stringify(questions, null, 2));
        $('#psych-questions-form').submit();
    });

    // reset
    $('#psych-reset-questions').on('click', function(){
        if (!confirm('Reset to default questions? This will overwrite current questions.')) return;
        // submit form with empty to trigger server default (user can reload plugin to get defaults)
        $('#psych-hidden-questions').val('');
        $('#psych-questions-form').submit();
    });

    // View result on front-end submissions (for admin shortcode page)
    $('body').on('click', '.view-result', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        var btn = $(this);
        btn.prop('disabled', true);
        $.post(psychAdmin.ajax_url, {action:'psych_view_result', nonce:psychAdmin.nonce, id:id}, function(resp){
            btn.prop('disabled', false);
            if (resp.success){
                var d = resp.data;
                var color = '#fff'; var bg = '#eee';
                if (d.risk && d.risk.toLowerCase().indexOf('safe')!==-1){ bg='#dff5e9'; color='#147a3a'; }
                else if (d.risk && d.risk.toLowerCase().indexOf('moderate')!==-1){ bg='#fff7d6'; color='#7a5b00'; }
                else if (d.risk && d.risk.toLowerCase().indexOf('concerning')!==-1){ bg='#ffe6d6'; color='#8a3b00'; }
                else { bg='#ffd6d6'; color='#8a0202'; }
                var html = '<div class="psych-modal"><div class="modal-header" style="background:'+bg+';padding:12px;border-radius:8px 8px 0 0;"><h3 style="margin:0;color:'+color+'">Result: '+d.risk+'</h3></div>';
                html += '<div style="padding:14px"><p><strong>Name:</strong> '+escapeHtml(d.name)+'</p>';
                html += '<p><strong>Email:</strong> '+escapeHtml(d.email)+'</p>';
                html += '<p><strong>Phone:</strong> '+escapeHtml(d.phone)+'</p>';
                html += '<p><strong>Score:</strong> '+escapeHtml(d.score)+'</p>';
                html += '<p><strong>Answers:</strong></p><pre style="background:#fafafa;padding:8px;border-radius:6px;max-height:240px;overflow:auto">'+escapeHtml(JSON.stringify(d.answers, null, 2))+'</pre>';
                html += '<p style="text-align:right"><button class="button close-modal">Close</button></p></div></div>';
                $('body').append(html);
            } else {
                alert('Error: '+resp.data);
            }
        }, 'json');
    });

    $(document).on('click', '.close-modal', function(){ $('.psych-modal').remove(); });

    function escapeHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
});