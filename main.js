const client = 'client';
function removeAllClass(element) {
  $(element).parent().parent().removeClass('dateEndDisable');
  $(element).parent().parent().removeClass('dateEnd');
  $(element).parent().parent().removeClass('dateEndError');
};
function checkDate() {
  $('table input.end').each(function(){
    var endDate = $(this).val().split('-').join(''), date = new Date, year = date.getFullYear(), day = date.getDate(), month = date.getMonth();
    endDate = Number(endDate);
    month = (String(month).length === 1 && month !== 9) ? (String(0)+String(month+1)) : String(month+1);
    day = (String(day).length === 1) ? (String(0)+String(day)) : String(day);
    date = [year, month, day].join('');
    date = Number(date);
    var disable = $(this).parent().parent().find('input:eq(0)');
    if (endDate < date) {
      if (disable.is(':checked')) {
        removeAllClass(this);
        $(this).parent().parent().addClass('dateEndDisable');
      } else {
        removeAllClass(this);
        $(this).parent().parent().addClass('dateEnd');
      }
    } else if(endDate >= date && disable.is(':checked')) {
      removeAllClass(this);
      $(this).parent().parent().addClass('dateEndError');
    } else {
      removeAllClass(this);
    }
  });
};
window.onload = function() {
  $('table input[type="checkbox"]').change(function(){
    var ip = $(this).parent().parent().find('td:eq(-4)').find('input').val();
    if ($(this).is(':checked')) {
      $.post("index.php", { disable: ip}).done(function(data) {
        console.log(data);
        if (data.length > 2){
           alert('Помилка');
        } else {
          checkDate();
        }
      });
    }  else {
      $.post("index.php", { enable: ip}).done(function(data) {
        console.log(data);
        if (data.length > 2){
          alert('Помилка');
        } else {
          checkDate();
        }
      });
    }
  });
  $('table input.comment').change(function(){
    var ip = $(this).parent().parent().find('td:eq(-4)').find('input').val();
    if ($(this).parent().parent().find('td').length === 9) {
      var elem = $(this).parent().parent(),
        comment = client+'|'+elem.find('td:eq(1)').find('input').val()+'|'+elem.find('td:eq(2)').find('input').val()+'|'+elem.find('td:eq(3)').find('input').val()+'|'+elem.find('td:eq(4)').find('input').val()
      $.post("index.php", { editComment: comment, ip: ip}).done(function(data) {
        console.log(data);
        if (data.length > 2){
          alert('Помилка');
        } else {
          checkDate();
        }
      });
    }  else {
      $.post("index.php", { editComment: $(this).parent().parent().find('td:eq(1)').find('input').val(), ip: ip}).done(function(data) {
        console.log(data);
        if (data.length > 2){
          alert('Помилка');
        } else {
          checkDate();
        }
      });
    }
  });
  $('table input.limit').change(function(){
    var ip = $(this).parent().parent().find('td:eq(-4)').find('input').val();
    $.post("index.php", { maxLimit: $(this).val(), ip: ip}).done(function(data) {
      console.log(data);
      if (data.length > 2){
        alert('Помилка');
      } else {
        checkDate();
      }
    });
  });
  $('table input.mac').change(function(){
    var ip = $(this).parent().parent().find('td:eq(-4)').find('input').val();
    $.post("index.php", { editMac: $(this).val(), ip: ip}).done(function(data) {
      console.log(data);
      if (data.length > 2){
        alert('Помилка');
      } else {
        checkDate();
      }
    });
  });
  $('table input[type="button"]').click(function(){
    var ip = $(this).parent().parent().find('td:eq(-4)').find('input').val(),
      _this = $(this).parent().parent();
    if (confirm("Дійсно вилучити ?")) {
      $.post("index.php", { remove: ip}).done(function(data) {
        console.log(data);
        if (data.length < 2){
          _this.remove();
          checkDate();
        } else {
           alert('Помилка');
        }
      });
    };
  });
  $('#addForm select').change(function(){
    if ($(this).val() === 'other') {
      $('#addForm label:eq(5)').hide();
      $('#addForm label:eq(6)').hide();
      $('#addForm label:eq(7)').hide();
    } else {
      $('#addForm label:eq(5)').show();
      $('#addForm label:eq(6)').show();
      $('#addForm label:eq(7)').show();
    }
  });
  $('#addForm input[type="button"]').click(function(){
    if ($('#addForm select').val() === 'client') {
      var comment = $('#addForm input:eq(4)').val()+'|'+$('#addForm input:eq(5)').val()+'|'+$('#addForm input:eq(6)').val()+'|'+$('#addForm input:eq(3)').val();
    } else {
      var comment = $('#addForm input:eq(3)').val();
    };
    $.post("index.php", {
      add:$('#addForm select').val(),
      mac:$('#addForm input:eq(1)').val(),
      limit: $('#addForm input:eq(2)').val(),
      ip: $('#addForm input:eq(0)').val(),
      comment: comment
    }).done(function(data) {
      console.log(data);
      if (data.length > 2){
        alert('Помилка');
      } else {
        document.location.reload();
      }
    });
  });
  $('a[href="?reportClear"]').click(function(e){
    if (!confirm('Дійсно видалити ?')) {
      e.preventDefault();
    }
  });
  checkDate();
}
