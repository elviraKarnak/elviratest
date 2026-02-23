(function($){
    // ITData injected by wp_localize_script
    var questions = (window.ITData && ITData.questions) || [];
    var settings = (window.ITData && ITData.settings) || {questions_per_step:10, primary_color:'#0077AC', secondary_color:'#FF9900', ajax_url:'', nonce:''};

    function buildUI(){
        // Apply admin colors as CSS variables
        try{
            var root = document.documentElement;
            root.style.setProperty('--it-primary', settings.primary_color || '#0077AC');
            root.style.setProperty('--it-secondary', settings.secondary_color || '#FF9900');
        }catch(e){}

        var $root = $('#it-app');
        if(!$root.length) return;
        $root.html('');

        // basic styles applied via CSS file; build multi-step form
        var total = questions.length;
        var per = parseInt(settings.questions_per_step) || 10;
        var steps = Math.ceil(total / per) || 1;

        var $form = $('<form id="it-form" class="it-form" />');
        var $progress = $('<div class="it-progress"><div class="it-progress-bar"></div><div class="it-progress-text"></div></div>');
        $form.append($progress);

        for(var s=0; s<steps; s++){
            var start = s*per;
            var end = Math.min(start+per, total);
            var $step = $('<div class="it-step" data-step="'+s+'"></div>');
            for(var i=start;i<end;i++){
                var q = questions[i];
                var $q = $('<div class="it-question"></div>');
                $q.append('<div class="it-qtitle">'+(i+1)+'. '+q.text+'</div>');
                var choices = [
                    {k:'sa',t:'Strongly agree'},
                    {k:'a',t:'Agree'},
                    {k:'n',t:'Neutral'},
                    {k:'d',t:'Disagree'},
                    {k:'sd',t:'Strongly disagree'}
                ];
                var $grid = $('<div class="it-grid"></div>');
                choices.forEach(function(ch){
                    var id = 'q_'+q.id+'_'+ch.k;
                    var $cell = $('<label class="it-cell" for="'+id+'"><input type="radio" name="q_'+q.id+'" id="'+id+'" value="'+ch.k+'" /> <span class="it-ctitle">'+ch.t+'</span></label>');
                    $grid.append($cell);
                });
                $q.append($grid);
                if(q.required){
                    $q.attr('data-required','1');
                }
                $step.append($q);
            }
            $form.append($step);
        }

        var $nav = $('<div class="it-nav"><button type="button" class="it-prev">Previous</button><button type="button" class="it-next">Next</button></div>');
        var $final = $('<div class="it-final" style="display:none"></div>');
        $final.append('<h3>Personal Info</h3>');
        $final.append('<p><label>Name<br/><input name="name" required /></label></p>');
        $final.append('<p><label>Email<br/><input name="email" type="email" required /></label></p>');
        $final.append('<p><label>Phone<br/><input name="phone" /></label></p>');
        $final.append('<p><label>Interview date/time<br/><input name="datetime" type="datetime-local" /></label></p>');
        $final.append('<p><button type="submit" class="it-submit">Submit</button></p>');

        $form.append($final);
        $form.append($nav);
        $root.append($form);

        var current = 0;
        showStep(0);
        updateProgress();

        // highlight selected cells
        $form.on('change', 'input[type=radio]', function(){
            var name = $(this).attr('name');
            // remove selected class for group
            $form.find('input[name="'+name+'"]').each(function(){
                $(this).closest('label.it-cell').removeClass('it-selected');
            });
            // add to checked
            $(this).closest('label.it-cell').addClass('it-selected');
        });


        $root.on('click','.it-next',function(){
            if(!validateStep(current)) return;
            if(current < steps-1){
                showStep(current+1);
            } else {
                // show final
                $('.it-step').hide();
                $final.show();
                $nav.hide();
                updateProgress(true);
            }
        });
        $root.on('click','.it-prev',function(){
            if($final.is(':visible')){
                $final.hide();
                $('.it-step').show();
                $nav.show();
                showStep(steps-1);
                updateProgress();
                return;
            }
            if(current>0) showStep(current-1);
        });

        $form.on('submit', function(e){
            e.preventDefault();
            if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }
            // collect answers
            var answers = {};
            questions.forEach(function(q){
                var val = $form.find('input[name="q_'+q.id+'"]:checked').val() || 'n';
                answers[q.id] = val;
            });
            var data = {
                name: $form.find('[name=name]').val(),
                email: $form.find('[name=email]').val(),
                phone: $form.find('[name=phone]').val(),
                datetime: $form.find('[name=datetime]').val(),
                answers: answers
            };
            // send via AJAX
            $.post(settings.ajax_url, {action:'it_submit', data: JSON.stringify(data), nonce: settings.nonce}, function(resp){
                if(resp.success){
                    $root.html('<div class="it-thanks"><h3>Thank you</h3><p>Your score: '+resp.data.pct+'% — '+resp.data.label+'</p></div>');
                } else {
                    alert('Error: '+(resp.data || 'Submission failed'));
                }
            });
        });

        function showStep(i){
            current = i;
            $('.it-step').hide();
            $('.it-step[data-step="'+i+'"]').show();
            updateProgress();
        }
        function updateProgress(final){
            var totalSteps = steps + 1; // final page counts
            var cur = final ? totalSteps : (current+1);
            var pct = Math.round((cur/totalSteps)*100);
            $('.it-progress-bar').css('width', pct+'%');
            $('.it-progress-text').text('Step '+cur+' of '+totalSteps);
        }
        function validateStep(i){
            var $s = $('.it-step[data-step="'+i+'"]');
            var ok = true;
            $s.find('.it-question[data-required="1"]').each(function(){
                var qname = $(this).find('input[type=radio]').first().attr('name');
                if(!$s.find('input[name="'+qname+'"]:checked').length){
                    ok = false;
                    $(this).addClass('it-error');
                } else {
                    $(this).removeClass('it-error');
                }
            });
            if(!ok){
                alert('Please answer required questions on this page.');
            }
            return ok;
        }
    }

    $(document).ready(function(){
        buildUI();
    });

})(jQuery);