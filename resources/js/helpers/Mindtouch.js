function updateInAdapt(){

  var mt_global_settings = $('#mt-global-settings');
  var data = JSON.parse($('#mt-global-settings').html());
  console.log(data);
  console.log(data.pageId);

}


$(document).on('click', '#page-options-menu-edit', function () {
  CKEDITOR.on('instanceReady', function () {
    console.log('ready');
    var ck_18 = $('a[class*="mindtouchsave"]').first();
    console.log(ck_18);
    var onclick = ck_18.attr('onclick');
    ck_18.attr('onclick', 'updateInAdapt();' + onclick);
    var onclick = ck_18.attr('onclick');
    console.log(onclick);

    ck_18.on('click', function () {
      console.log('yes');
    });

  });
});




