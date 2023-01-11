import axios from 'axios'

export const codeStatusOptions = [
  { value: '', text: 'Please choose an option' },
  { value: 'full_code', text: 'Full Code' },
  { value: 'dnr', text: 'DNR' }
]

export async function getCaseStudyNotesByQuestion () {
  this.$nextTick(async () => {
    try {
      const { data } = await axios.get(`/api/case-study-notes/assignment/${this.assignmentId}/question/${this.questions[this.currentPage - 1].id}`)
      if (data.type === 'error') {
        this.$noty.error(data.message)
        this.isLoading = false
        return false
      }
      this.caseStudyNotesByQuestion = data.case_study_notes
      let updatedInformations = []
      for (let i = 0; i < this.caseStudyNotesByQuestion.length; i++) {
        let caseStudyNotesByQuestion = this.caseStudyNotesByQuestion[i]
        if (caseStudyNotesByQuestion.updated_information) {
          updatedInformations.push(caseStudyNotesByQuestion.title)
        }
      }
      if (updatedInformations.length) {
        let message = 'The following Case Study Notes have been updated: ' + updatedInformations.join(', ') + '.'
        this.$noty.info(message)
      }
    } catch (error) {
      this.$noty.error(error.message)
      this.isLoading = false
    }
  })
}
