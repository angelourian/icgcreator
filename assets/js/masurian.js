(function(w, $){
	$( document ).ready( function() {
		$(document).on('change','#post-type-1',function(){
			if ($('#post-type-1').is(":checked"))
			{
				console.log('checked');
				$('input[data-post="news-1"]').show();
				$('p[data-post="news-1"]').show();
				$( 'input[data-post="news-1"]' ).parents('tr').show();
			}
			else
			{
				console.log('unchecked');
				$('input[data-post="news-1"]').hide();
				$('p[data-post="news-1"]').hide();
				$( 'input[data-post="news-1"]' ).parents('tr').hide();
			}
		});

		if ( masurian_check == 'checked' ){
			$( 'input[data-post="news-1"]' ).parents('tr').show();
		} else {
			$( 'input[data-post="news-1"]' ).parents('tr').hide();
		}

	});
})(window, jQuery);