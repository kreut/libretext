export const h5pResizer = () => {
  let H5PScript = document.createElement('script')
  H5PScript.setAttribute('src', 'https://studio.libretexts.org/modules/contrib/h5p/vendor/h5p/h5p-core/js/h5p-resizer.js')
  document.head.appendChild(H5PScript)
}
