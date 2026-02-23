(function(){
document.addEventListener('DOMContentLoaded', function(){
    // initialize flatpickr if available for admin datetime inputs
    if (window.flatpickr) {
        flatpickr('.esnb-datetime', {enableTime:true, dateFormat:'Y-m-d H:i', altInput:true, altFormat:'F j, Y H:i'});
    }
});
})();