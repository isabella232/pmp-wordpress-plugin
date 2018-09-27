(function() {
    var $ = jQuery;

    $('button#pmp_client_secret_reset_button').click(function(e) {
        e.preventDefault();
        $('#mode-change').addClass('hidden');
        $('#mode-reset').removeClass('hidden').children('[disabled]').prop('disabled', false).addClass('to-disable');
        $('#pmp_client_secret_reset').attr('checked', true);
        $('#pmp_client_secret').focus();
    });
    $('button#pmp_client_secret_reset_reset').click(function(e) {
        e.preventDefault();
        $('#mode-change').removeClass('hidden');
        $('#pmp_client_secret').val('');
        $('#pmp_client_secret_reset').prop('checked', false).val('');
        $('#mode-reset').addClass('hidden').children('.to-disable').prop('disabled', true);
    });
})();
