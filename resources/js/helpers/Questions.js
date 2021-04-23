export function doCopy (adaptId) {
  this.$copyText(adaptId).then((e) => {
    this.$noty.success('The Adapt ID has been copied to your clipboard.')
  }, function (e) {
    this.$noty.error('We could not copy the Adapt ID to your clipboard.')
  })
}

export function viewQuestion (questionId) {
  this.$router.push({ path: `/assignments/${this.assignmentId}/questions/view/${questionId}` })
  return false
}


