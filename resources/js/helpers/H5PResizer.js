export const h5pResizer  = () =>  {
  let H5PScript = document.createElement('script')
  H5PScript.setAttribute('src', 'https://cdn.libretexts.net/github/LibreTextsMain/Miscellaneous/h5p-resizer.js')
  document.head.appendChild(H5PScript)
}
