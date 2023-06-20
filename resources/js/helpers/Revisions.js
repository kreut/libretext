export const labelMapping = {
  reason_for_edit: 'Reason For Edit',
  question_editor: 'Question Editor',
  non_technology_html: 'Open-Ended Content',
  technology: 'Auto-Graded Technology',
  text_question: 'Open-Ended Text Alternative',
  a11y_technology: 'Auto-Graded Technology Alternative',
  answer_html: 'Answer',
  solution_html: 'Solution',
  hint: 'Hint',
  technology_id: 'Path',
  title: 'Title',
  webwork_code: 'WebWork Code'
}

export function addRubricCategories (revision) {
  if (revision.rubric_categories && revision.rubric_categories.length) {
    for (let i = 0; i < revision.rubric_categories.length; i++) {
      let rubricCategory = revision.rubric_categories[i]
      revision[`Rubric Category ${rubricCategory.category}`] = rubricCategory.criteria + ' (' + rubricCategory.score + ' points)'
    }
  }
  return revision
}
