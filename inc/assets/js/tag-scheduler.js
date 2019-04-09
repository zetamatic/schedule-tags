jQuery(document).ready(function($) {

	if( document.body.classList.contains( 'block-editor-page' ) ) {
		$('#tags-scheduler-table').find('thead').hide();
    wp.data.subscribe(function () {
      var isSavingPost = wp.data.select('core/editor').isSavingPost()
      var isAutosavingPost = wp.data.select('core/editor').isAutosavingPost()

      if (isSavingPost && !isAutosavingPost) {
        $("#wpts_field").load(location.href + " #wpts_field>*", "");
      }
    });
  }

  function ShowTableHead() {
  	var SchedulerTable = $('table#tags-scheduler-table tbody').find('tr');
  	if( SchedulerTable.length ) {
  		$('table#tags-scheduler-table thead').show();
  	}
  	else {
  		$('table#tags-scheduler-table thead').hide();
  	}
  }

  ShowTableHead();

	function capitalize(string) {
  	return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
  }

	//Prepare html for the tags
	$('body').on('change', 'select#wpts_field', function() {
		var SelectedVal = $(this).val();
		var UpperCaseVal = capitalize(SelectedVal);
		var RowCount = $('#tags-scheduler-table tbody').find('tr').length;
		var LastRow = $('#tags-scheduler-table tbody').find('tr').last();
		var CustomHtml = '<tr>';
				CustomHtml += '<td>'+UpperCaseVal+' <input type="hidden" name="tags_scheduler['+RowCount+'][tag_name]" value="'+SelectedVal+'"> </td>';
				CustomHtml += '<td><input name="tags_scheduler['+RowCount+'][startDate]"  class="startDate"></td>';
				CustomHtml += '<td><input name="tags_scheduler['+RowCount+'][endDate]" class="endDate"></td>';
				CustomHtml += '<td><input type="button" data-name="'+SelectedVal+'" class="button tags-scheduler-remove-row" value="Remove"></td>';
				CustomHtml += '</tr>';

		if(SelectedVal) {
			jQuery('#wpts_field option:contains("'+SelectedVal+'")').attr("disabled","disabled");
			$('#tags-scheduler-table').find('thead').show();
			if( RowCount == 0 ) {
				$('#tags-scheduler-table tbody').append(CustomHtml);
			}
			else {
				$(CustomHtml).insertAfter(LastRow);
			}
			$('#wpts_field').val('');
			ShowTableHead();
		}

		$('#tags-scheduler-table .startDate').each(function(){
    	$(this).datepicker({
    		dateFormat : 'yy-mm-dd',
    		minDate: 0,
    		onSelect: function(selected) {
    			var EndDate = $(this).parents('tr').find('input.endDate');
        	$(EndDate).datepicker("option","minDate", selected);
    		}
    	});
		});

		$('#tags-scheduler-table .endDate').each(function(){
    	$(this).datepicker({
    		dateFormat : 'yy-mm-dd',
    		minDate: 0,
    		onSelect: function(selected) {
    			var StartDate = $(this).parents('tr').find('input.startDate');
        	$(StartDate).datepicker("option","maxDate", selected);
    		}
    	});
		});

	});

	$('body').on('click', '.tags-scheduler-remove-row', function() {
		var TagName = $(this).attr('data-name');
		if( TagName !== '' ) {
			$('#wpts_field option:contains("'+TagName+'")').attr("disabled",false);
		}
		$(this).parents('tr').remove();
		$('#wpts_field').val('');
		ShowTableHead();
	});

	//jQuery('#tags-scheduler-table .startDate').datepicker('destroy');
	$('#tags-scheduler-table .startDate').each(function(){
    $(this).datepicker({
    	dateFormat : 'yy-mm-dd',
    	minDate: 0,
    	onSelect: function(selected) {
    		var EndDate = $(this).parents('tr').find('input.endDate');
        $(EndDate).datepicker("option","minDate", selected);
    	}
    });
	});

	$('#tags-scheduler-table .endDate').each(function(){
  	$(this).datepicker({
    	dateFormat : 'yy-mm-dd',
    	minDate: 0,
    	onSelect: function(selected) {
    		var StartDate = $(this).parents('tr').find('input.startDate');
        $(StartDate).datepicker("option","maxDate", selected);
    	}
    });
	});

});