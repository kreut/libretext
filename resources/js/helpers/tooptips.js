export function getTooltipTarget(item, id){
  return `${item}_${id}`
}
export function initTooltips(vm) {

//the following was the only way for me to get rid of the tooltips after closing the modal
  vm.$root.$on('bv::modal::hide', (bvEvent, modalId) => {
    vm.modalHidden = true
  })
  vm.$root.$on('bv::tooltip::show', bvEvent => {
    if (vm.modalHidden) {
      bvEvent.preventDefault()
      vm.modalHidden = false
    }
  })
}
