export function updateCompletionSplitOpenEndedSubmissionPercentage (form) {

  if (form.completion_split_auto_graded_percentage > 100) {
    form.completion_split_auto_graded_percentage = 100
  }
  if (form.completion_split_auto_graded_percentage < 0) {
    form.completion_split_auto_graded_percentage = 0
  }
  return 100 - parseFloat(form.completion_split_auto_graded_percentage)
}
