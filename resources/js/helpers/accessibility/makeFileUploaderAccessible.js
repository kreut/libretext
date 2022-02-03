import $ from 'jquery'

/* The file-uploader uses a fake-button making it not accessible */
export function makeFileUploaderAccessible () {
  const cls = ['btn', 'btn-primary', 'small', 'mr-2', 'file-uploads', 'file-uploads-html5']
  $('label[for="file"]').remove()
  document.getElementsByClassName('file-uploads')[0].classList.remove(...cls)
  $('#file').attr('title', 'Choose File')
}
