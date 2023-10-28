import Diff from 'vue-jsdiff'

export const labelMapping = {
  reason_for_edit: 'Reason For Edit',
  question_editor: 'Question Editor',
  non_technology_html: 'Open-Ended Content',
  technology: 'Auto-Graded Technology',
  text_question: 'Open-Ended Alternative',
  a11y_technology: 'Auto-Graded Technology Alternative',
  a11y_auto_graded_question_id: 'Auto-Graded Alternative',
  answer_html: 'Answer',
  solution_html: 'Solution',
  question_editor_name: 'Question Editor',
  hint: 'Hint',
  technology_id: 'Path',
  title: 'Title',
  webwork_code: 'WebWork Code'
}

export function addRubricCategories (revision) {
  if (revision && revision.rubric_categories && revision.rubric_categories.length) {
    for (let i = 0; i < revision.rubric_categories.length; i++) {
      let rubricCategory = revision.rubric_categories[i]
      revision[`Rubric Category ${rubricCategory.category}`] = rubricCategory.criteria + ' (' + rubricCategory.score + ' points)'
    }
  }
  return revision
}

export function getRevisionDifferences (revision1, revision2) {
  revision1 = addRubricCategories(revision1)
  revision2 = addRubricCategories(revision2)
  let differences = []
  differences.push({
    property: 'Reason for Edit',
    revision1: revision1.reason_for_edit ? revision1.reason_for_edit : 'N/A',
    revision2: revision2.reason_for_edit ? revision2.reason_for_edit : 'N/A',
    revision2NoDiffs: revision2.reason_for_edit ? revision2.reason_for_edit : 'N/A'
  })
  for (const property in revision1) {
    //console.log(property)
    if (property === 'webwork_code') {
      console.log(revision1['webwork_code'])
      console.log(revision2['webwork_code'])
      revision1['webwork_code'] = revision1['webwork_code'] ? revision1['webwork_code'].replaceAll('\n', '<br>') : null
      revision2['webwork_code'] = revision2['webwork_code'] ? revision2['webwork_code'].replaceAll('\n', '<br>') : null
      console.log(revision1['webwork_code'])
      console.log(revision2['webwork_code'])
    }
    if (revision2[property] !== revision1[property] &&
      (revision2[property] || revision1[property])) {
      if (!['created_at', 'updated_at', 'revision_number', 'reason_for_edit', 'technology_iframe', 'action', 'text', 'value', 'id', 'question_editor_user_id', 'rubric_categories'].includes(property)) {
        let text = ''
        try {
          revision1[property] = revision1[property] ? revision1[property] : ''
          const diff = Diff.diffChars(revision1[property], revision2[property])

          diff.forEach((part) => {
            const color = part.added ? 'green' : part.removed ? 'red' : 'grey'
            text += '<span style="color:' + color + '">' + part.value + '</span>'
          })
        } catch (error) {
          text = 'N/A'
        }
        differences.push({
          property: labelMapping[property] ? labelMapping[property] : property,
          revision1: revision1[property] ? revision1[property] : 'N/A',
          revision2: revision2[property] ? text : 'N/A',
          revision2NoDiffs: revision2[property] ? revision2[property] : 'N/A'
        })
      }
    }
  }
  return differences
}
