$('#authorized_people_checkbox').change(function() {
    if(this.checked) {
        $('.authorized_people').show();
        $('#authorized_people_title').show();
    }else{
       $('.authorized_people').hide();
       $('#authorized_people_title').hide();
    }
  });
