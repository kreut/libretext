function updateInAdapt() {
  var iframe = document.createElement("iframe");
  var uniqueString = "MyUNiQueId";
  document.body.appendChild(iframe);
  iframe.style.display = "none";
  iframe.contentWindow.name = uniqueString;

// construct a form with hidden inputs, targeting the iframe
  var form = document.createElement("form");
  form.target = uniqueString;
  form.action = "https://dev.adapt.libretexts.org/api/mind-touch-events/update";
  form.method = "POST";

// repeat for each parameter
  var input = document.createElement("input");
  input.type = "hidden";
  input.name = "page_id";
  var mt_global_settings = $('#mt-global-settings');
  var data = JSON.parse($('#mt-global-settings').html());


  input.value = data.pageId;
  form.appendChild(input);

  document.body.appendChild(form);
  form.submit();


}


$(document).on('click', '#page-options-menu-edit', function () {
  CKEDITOR.on('instanceReady', function () {
    console.log('ready');
    var ck_18 = $('a[class*="mindtouchsave"]').first();
    var onclick = ck_18.attr('onclick');
    ck_18.attr('onclick', 'updateInAdapt();' + onclick);


  });
});




