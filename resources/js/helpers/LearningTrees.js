import $ from 'jquery'

export function increaseLearningTreeModalSize () {
  this.$nextTick(() => {
    $('.modal-dialog.modal-xl').css('max-width', '95%')
  })
}
