$(document).ready(function(){

	function updateCount(){
		selectedCount = $('.subscriptions-item.selected').length;
		$('.subcount-value').text(selectedCount);
	}

	updateCount();
	
	$(document).on('click', '.subscriptions-item:not(.selected)', function(){
		if(selectedCount < 30){
			$(this).addClass('selected');
			$(this).attr('data-type', 'all');
			updateCount();
		}
	});
	$(document).on('click', '.subscriptions-item.selected', function(){
		$(this).removeClass('selected');
		updateCount();
	});

	$(document).on('click', '.subscriptions-item .subscriptions-item-keywords', function(e){
		if(selectedCount < 30){
			e.stopPropagation();
			$(this).parent('.subscriptions-item').addClass('selected');
			$(this).parent('.subscriptions-item').attr('data-type', 'keywords');
			updateCount();
		}
	});
	$(document).on('click', '.subscriptions-item.selected[data-type="keywords"] .subscriptions-item-keywords', function(e){
		e.stopPropagation();
		$(this).parent('.subscriptions-item').removeClass('selected');
		updateCount();
	});

	$(document).on('click', '.subscriptions-item .subscriptions-item-select', function(e){
		if(selectedCount < 30){
			e.stopPropagation();
			$(this).parent('.subscriptions-item').addClass('selected');
			$(this).parent('.subscriptions-item').attr('data-type', 'all');
			updateCount();
		}
	});
	$(document).on('click', '.subscriptions-item.selected[data-type="all"] .subscriptions-item-select', function(e){
		e.stopPropagation();
		$(this).parent('.subscriptions-item').removeClass('selected');
		updateCount();
	});



	$('.update').click(function(){
		
		$(this).addClass('loading');

		var selectedSubs = [];
		var keywordSubs = [];
		var keywords = [];

		$('.subscriptions-item.selected').each(function(){
			if($(this).attr('data-type') == 'all'){
				selectedSubs.push($(this).attr('data-id'));
			}
			if($(this).attr('data-type') == 'keywords'){
				keywordSubs.push($(this).attr('data-id'));
			}
		});

		$('.keywords-item').each(function(){
			if($(this).find('.keywords-input').val() !== ""){
				keywords.push($(this).find('.keywords-input').val());
			}

		});
     
		$.ajax({
		   url : 'http://localhost:8000/update', // La ressource ciblée
		   type : 'POST', // Le type de la requête HTTP.
		   data : {selected_subs : selectedSubs, keyword_subs : keywordSubs, keywords : keywords},
		   dataType: 'json',
		   success: function(data){
			$('.update').removeClass('loading');
			$('.subcount-error').text(data);
		  }
		});
	   
	});


	$('.keywords-add').click(function(){

		var keywordItem = '<div class="keywords-item"><input class="keywords-input" type="text"><span class="keywords-item-delete"></span></div>'

		$(this).before(keywordItem);

	});

	$(document).on('click', '.keywords-item-delete', function(){
		
		$(this).parent('.keywords-item').remove();

	});

});