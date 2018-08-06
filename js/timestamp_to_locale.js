$(function() {
    $(".a-timestamp-full-date-time").each(function() {
        let ts = $(this).data('ts');
        if (ts) {
            let d = new Date(ts * 1000);
            $(this).text(d.toLocaleDateString() + ' ' + d.toLocaleTimeString());
        }
    });

});