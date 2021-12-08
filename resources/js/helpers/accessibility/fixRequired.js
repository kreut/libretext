export function fixRequired (vm) {
  vm.$nextTick(() => {
    let required = document.getElementsByClassName('required')
    for (let i = 0; i < required.length; i++) {
      required[i].getElementsByTagName('input')[0].required = true
    }
  })
}
