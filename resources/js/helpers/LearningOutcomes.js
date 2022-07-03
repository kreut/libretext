import axios from 'axios'

export const subjectOptions = [
  { value: null, text: 'Choose a subject' },
  { value: 'chemistry', text: 'Chemistry' },
  { value: 'pre-calculus', text: 'Pre-calculus' },
  { value: 'nutrition', text: 'Nutrition' }
]

export async function getLearningOutcomes (subject) {
  this.learningOutcome = ''
  this.learningOutcomeOptions = []
  if (!subject) {
    return false
  }
  try {
    const { data } = await axios.get(`/api/learning-outcomes/${subject}`)
    if (data.type !== 'success') {
      this.$noty.error(data.message)
      return false
    }
    for (let i = 0; i < data.learning_outcomes.length; i++) {
      let learningOutcome = data.learning_outcomes[i]
      this.learningOutcomeOptions.push({ label: learningOutcome.description, id: learningOutcome.id })
    }
  } catch (error) {
    this.$noty.error(error.message)
  }
}
