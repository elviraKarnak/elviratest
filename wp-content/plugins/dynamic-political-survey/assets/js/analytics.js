/**
 * Analytics JavaScript
 * Handles dynamic filtering, chart generation, and data export
 */

(function($) {
    'use strict';
    
    let currentChart = null;
    
    const Analytics = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Survey change - load questions
            $('#filter-survey').on('change', this.loadQuestions);
            
            // District change - load loksabha
            $('#filter-district').on('change', this.loadLoksabha);
            
            // Loksabha change - load assembly
            $('#filter-loksabha').on('change', this.loadAssembly);
            
            // Chart type change
            $('input[name="chart_type"]').on('change', function() {
                $('.chart-type-option').removeClass('active');
                $(this).closest('.chart-type-option').addClass('active');
            });
            
            // Generate analytics
            $('#generate-analytics-btn').on('click', this.generateAnalytics);
            
            // Reset filters
            $('#reset-filters-btn').on('click', this.resetFilters);
            
            // Export CSV
            $('#export-csv-btn').on('click', this.exportCSV);
            
            // Export Image
            $('#export-image-btn').on('click', this.exportImage);
        },
        
        loadQuestions: function() {
            const surveyId = $(this).val();
            const $questionSelect = $('#filter-question');
            
            if (!surveyId) {
                $questionSelect.html('<option value="">Select survey first...</option>').prop('disabled', true);
                return;
            }
            
            $questionSelect.html('<option value="">Loading questions...</option>').prop('disabled', true);
            
            $.ajax({
                url: dpsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dps_get_survey_questions',
                    survey_id: surveyId,
                    nonce: dpsAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.questions) {
                        let options = '<option value="">Select a question...</option>';
                        
                        response.data.questions.forEach(function(question) {
                            options += `<option value="${question.id}">${question.code}: ${question.text}</option>`;
                        });
                        
                        $questionSelect.html(options).prop('disabled', false);
                    } else {
                        $questionSelect.html('<option value="">No questions found</option>');
                    }
                },
                error: function() {
                    $questionSelect.html('<option value="">Error loading questions</option>');
                }
            });
        },
        
        loadLoksabha: function() {
            const districtId = $(this).val();
            const $loksabhaSelect = $('#filter-loksabha');
            const $assemblySelect = $('#filter-assembly');
            
            // Reset assembly
            $assemblySelect.html('<option value="">Select loksabha first</option>').prop('disabled', true);
            
            if (!districtId) {
                $loksabhaSelect.html('<option value="">Select district first</option>').prop('disabled', true);
                return;
            }
            
            $loksabhaSelect.html('<option value="">Loading...</option>').prop('disabled', true);
            
            $.ajax({
                url: dpsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dps_get_loksabha',
                    district_id: districtId,
                    nonce: dpsAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.loksabha) {
                        let options = '<option value="">All Loksabha</option>';
                        
                        response.data.loksabha.forEach(function(item) {
                            options += `<option value="${item.id}">${item.name}</option>`;
                        });
                        
                        $loksabhaSelect.html(options).prop('disabled', false);
                    } else {
                        $loksabhaSelect.html('<option value="">No loksabha found</option>');
                    }
                }
            });
        },
        
        loadAssembly: function() {
            const loksabhaId = $(this).val();
            const $assemblySelect = $('#filter-assembly');
            
            if (!loksabhaId) {
                $assemblySelect.html('<option value="">Select loksabha first</option>').prop('disabled', true);
                return;
            }
            
            $assemblySelect.html('<option value="">Loading...</option>').prop('disabled', true);
            
            $.ajax({
                url: dpsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dps_get_assembly',
                    loksabha_id: loksabhaId,
                    nonce: dpsAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.assembly) {
                        let options = '<option value="">All Assembly</option>';
                        
                        response.data.assembly.forEach(function(item) {
                            options += `<option value="${item.id}">${item.name}</option>`;
                        });
                        
                        $assemblySelect.html(options).prop('disabled', false);
                    } else {
                        $assemblySelect.html('<option value="">No assembly found</option>');
                    }
                }
            });
        },
        
        generateAnalytics: function() {
            const surveyId = $('#filter-survey').val();
            const questionId = $('#filter-question').val();
            
            if (!surveyId) {
                alert('Please select a survey');
                return;
            }
            
            if (!questionId) {
                alert('Please select a question to analyze');
                return;
            }
            
            // Show loading
            const $button = $(this);
            const buttonText = $button.html();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Generating...');
            
            // Collect all filters
            const filters = Analytics.collectFilters();
            
            $.ajax({
                url: dpsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dps_generate_analytics',
                    survey_id: surveyId,
                    question_id: questionId,
                    filters: filters,
                    nonce: dpsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        Analytics.displayResults(response.data);
                        Analytics.displayActiveFilters(filters);
                    } else {
                        alert(response.data.message || 'Error generating analytics');
                    }
                    
                    $button.prop('disabled', false).html(buttonText);
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false).html(buttonText);
                }
            });
        },
        
        collectFilters: function() {
            const filters = {
                district: $('#filter-district').val(),
                loksabha: $('#filter-loksabha').val(),
                assembly: $('#filter-assembly').val(),
                year: $('#filter-year').val(),
                month: $('#filter-month').val(),
                age: $('#filter-age').val(),
                gender: [],
                religion: [],
                caste: []
            };
            
            // Collect checkboxes
            $('input[name="gender[]"]:checked').each(function() {
                filters.gender.push($(this).val());
            });
            
            $('input[name="religion[]"]:checked').each(function() {
                filters.religion.push($(this).val());
            });
            
            $('input[name="caste[]"]:checked').each(function() {
                filters.caste.push($(this).val());
            });
            
            return filters;
        },
        
        displayActiveFilters: function(filters) {
            const $container = $('#active-filters-list');
            $container.empty();
            
            const filterLabels = {
                district: 'District',
                loksabha: 'Loksabha',
                assembly: 'Assembly',
                year: 'Year',
                month: 'Month',
                age: 'Age'
            };
            
            // Display simple filters
            for (const [key, label] of Object.entries(filterLabels)) {
                if (filters[key]) {
                    const value = key === 'district' || key === 'loksabha' || key === 'assembly' 
                        ? $(`#filter-${key} option:selected`).text()
                        : filters[key];
                    
                    $container.append(`<span class="filter-tag">${label}: ${value}</span>`);
                }
            }
            
            // Display array filters
            ['gender', 'religion', 'caste'].forEach(function(key) {
                if (filters[key] && filters[key].length > 0) {
                    filters[key].forEach(function(value) {
                        $container.append(`<span class="filter-tag">${key}: ${value}</span>`);
                    });
                }
            });
            
            if ($container.children().length > 0) {
                $('#selected-filters-display').show();
            } else {
                $('#selected-filters-display').hide();
            }
        },
        
        displayResults: function(data) {
            // Hide no-data message
            $('#no-data-message').hide();
            $('#analytics-results').show();
            $('#export-csv-btn, #export-image-btn').show();
            
            // Update stats
            $('#total-responses').text(data.total_responses || 0);
            $('#unique-answers').text(data.unique_answers || 0);
            $('#most-popular').text(data.most_popular || '-');
            $('#date-range').text(data.date_range || '-');
            
            // Generate chart
            Analytics.generateChart(data);
            
            // Populate table
            Analytics.populateTable(data);
        },
        
        generateChart: function(data) {
            // Destroy existing chart
            if (currentChart) {
                currentChart.destroy();
            }
            
            const chartType = $('input[name="chart_type"]:checked').val();
            const ctx = document.getElementById('analytics-chart').getContext('2d');
            
            // Prepare data
            const labels = data.labels || [];
            const values = data.values || [];
            const percentages = data.percentages || [];
            
            // Color palette
            const colors = [
                'rgba(102, 126, 234, 0.8)',
                'rgba(118, 75, 162, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 99, 71, 0.8)',
                'rgba(144, 238, 144, 0.8)'
            ];
            
            const borderColors = colors.map(c => c.replace('0.8', '1'));
            
            // Chart configuration
            const config = {
                type: chartType,
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Responses',
                        data: values,
                        backgroundColor: colors,
                        borderColor: borderColors,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: chartType === 'pie' ? 'right' : 'top',
                            labels: {
                                font: { size: 12 },
                                padding: 15,
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map(function(label, i) {
                                            const value = data.datasets[0].data[i];
                                            const percentage = percentages[i] || 0;
                                            return {
                                                text: `${label}: ${value} (${percentage}%)`,
                                                fillStyle: data.datasets[0].backgroundColor[i],
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: data.question_text || 'Survey Results',
                            font: { size: 16, weight: 'bold' },
                            padding: 20
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed.y || context.parsed;
                                    const percentage = percentages[context.dataIndex] || 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            };
            
            // Bar chart specific options
            if (chartType === 'bar') {
                config.options.scales = {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                };
            }
            
            currentChart = new Chart(ctx, config);
        },
        
        populateTable: function(data) {
            const $tbody = $('#analytics-table-body');
            $tbody.empty();
            
            const labels = data.labels || [];
            const values = data.values || [];
            const percentages = data.percentages || [];
            
            labels.forEach(function(label, index) {
                const row = `
                    <tr>
                        <td><strong>${label}</strong></td>
                        <td>${values[index]}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; background: #f0f0f1; height: 20px; border-radius: 10px; overflow: hidden;">
                                    <div style="background: #2271b1; height: 100%; width: ${percentages[index]}%;"></div>
                                </div>
                                <span style="min-width: 50px; text-align: right; font-weight: 600;">${percentages[index]}%</span>
                            </div>
                        </td>
                    </tr>
                `;
                $tbody.append(row);
            });
        },
        
        resetFilters: function() {
            // Reset all select dropdowns
            $('#filter-district, #filter-loksabha, #filter-assembly, #filter-year, #filter-month, #filter-age').val('');
            
            // Uncheck all checkboxes
            $('.filter-checkbox').prop('checked', false);
            
            // Disable dependent dropdowns
            $('#filter-loksabha, #filter-assembly').prop('disabled', true);
            $('#filter-loksabha').html('<option value="">Select district first</option>');
            $('#filter-assembly').html('<option value="">Select loksabha first</option>');
            
            // Hide results
            $('#analytics-results').hide();
            $('#no-data-message').show();
            $('#selected-filters-display').hide();
            $('#export-csv-btn, #export-image-btn').hide();
        },
        
        exportCSV: function() {
            // This would generate and download a CSV file
            const surveyId = $('#filter-survey').val();
            const questionId = $('#filter-question').val();
            const filters = Analytics.collectFilters();
            
            const form = $('<form>', {
                method: 'POST',
                action: dpsAdmin.ajax_url
            });
            
            form.append($('<input>', { type: 'hidden', name: 'action', value: 'dps_export_csv' }));
            form.append($('<input>', { type: 'hidden', name: 'survey_id', value: surveyId }));
            form.append($('<input>', { type: 'hidden', name: 'question_id', value: questionId }));
            form.append($('<input>', { type: 'hidden', name: 'filters', value: JSON.stringify(filters) }));
            form.append($('<input>', { type: 'hidden', name: 'nonce', value: dpsAdmin.nonce }));
            
            $('body').append(form);
            form.submit();
            form.remove();
        },
        
        exportImage: function() {
            if (currentChart) {
                const url = currentChart.toBase64Image();
                const link = document.createElement('a');
                link.download = 'survey-analytics.png';
                link.href = url;
                link.click();
            }
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        if ($('.dps-analytics-page').length) {
            Analytics.init();
        }
    });
    
})(jQuery);