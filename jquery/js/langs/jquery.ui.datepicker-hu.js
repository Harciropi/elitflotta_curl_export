/* Hungarian initialisation for the jQuery UI date picker plugin. */
/* Datepicker written by Istvan Karaszi (jquery@spam.raszi.hu). */
/* Timepicker written by Ferenc Hohl (hohl.ferenc@netgo.hu). */
jQuery(function($){
	$.datepicker.regional['hu'] = {
		closeText: 'bezárás',
		prevText: '&laquo;&nbsp;vissza',
		nextText: 'előre&nbsp;&raquo;',
		currentText: 'ma',
		monthNames: ['Január', 'Február', 'Március', 'Április', 'Május', 'Június',
		'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'],
		monthNamesShort: ['Jan', 'Feb', 'Már', 'Ápr', 'Máj', 'Jún',
		'Júl', 'Aug', 'Szep', 'Okt', 'Nov', 'Dec'],
		dayNames: ['Vasárnap', 'Hétfő', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat'],
		dayNamesShort: ['Vas', 'Hét', 'Ked', 'Sze', 'Csü', 'Pén', 'Szo'],
		dayNamesMin: ['V', 'H', 'K', 'Sze', 'Cs', 'P', 'Szo'],
		weekHeader: 'Hét',
		dateFormat: 'yy.mm.dd.',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: true,
		yearSuffix: ''};
	$.datepicker.setDefaults($.datepicker.regional['hu']);
        
        $.timepicker.regional['hu'] = {
        	timeOnlyTitle: 'Válasszon időt!',
        	timeText: 'Idő',
                hourText: 'Óra',
                minuteText: 'Perc',
                secondText: 'Másodperc',
                currentText: 'Most',
                closeText: 'Bezárás',
                ampm: false};
        $.timepicker.setDefaults($.timepicker.regional['hu']);
});
