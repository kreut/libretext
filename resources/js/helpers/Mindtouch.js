var iframe = document.createElement("iframe");
var uniqueString = "ASTidilwei!@@";
document.body.appendChild(iframe);
iframe.style.display = "none";
iframe.contentWindow.name = uniqueString;

// construct a form with hidden inputs, targeting the iframe
var form = document.createElement("form");
form.target = uniqueString;
form.action = "https://dev.adapt.libretexts.org/mind-touch-events/update-tags";
form.method = "POST";

// repeat for each parameter
var input = document.createElement("input");
input.type = "hidden";
input.name = "some_name";
input.value = "some_value";
form.appendChild(input);

document.body.appendChild(form);
form.submit();

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




