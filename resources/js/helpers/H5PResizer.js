export const h5pResizer  = () =>  {
  let H5PScript = document.createElement('script')
  H5PScript.setAttribute('src', 'https://files.libretexts.org/github/LibreTextsMain/Miscellaneous/h5p-resizer.js')
  document.head.appendChild(H5PScript)
}
