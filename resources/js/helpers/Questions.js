import axios from 'axios'

export function doCopy (adaptId) {
  this.$copyText(adaptId).then((e) => {
    this.$noty.success('The Question ID has been copied to your clipboard.')
  }, function (e) {
    this.$noty.error('We could not copy the Question ID to your clipboard.')
  })
}

export function viewQuestion (questionId) {
  this.$router.push({ path: `/assignments/${this.assignmentId}/questions/view/${questionId}` })
  return false
}

export async function getQuestions () {
  this.questions = []
  try {
    const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/summary`)
    if (!data.rows.length) {
      return false
    }
    for (let i = 0; i < data.rows.length; i++) {
      let question = data.rows[i]
      this.questions.push(question)
      this.questionsOptions.push({ value: question.order, text: question.order })
    }
    this.questionId = data.rows[0].question_id
    if (data.type === 'error') {
      this.$noty.error(data.message)
      return false
    }
  } catch (error) {
    this.$noty.error(error.message)
  }
  this.currentQuestionPage = 1
}


