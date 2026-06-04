jQuery(function($) {
    $(document).on('click', 'a[href*="action=wsc_print_label"]', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        window.open(url, 'wsc_print_popup', 'width=800,height=600,toolbar=no,scrollbars=yes,resizable=yes');
    });
});
