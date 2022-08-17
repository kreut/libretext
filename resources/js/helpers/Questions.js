import axios from 'axios'
import { updateModalToggleIndex } from './accessibility/fixCKEditor'

export function getTechnologySrc (technology, src, question) {
  let technologySrc = ''
  let text
  if (question[src]) {
    question[src] = question[src].replace('&amp;', '&')
    let url = new URL(question[src])
    switch (question[technology]) {
      case ('webwork'):
        text = url.searchParams.get('sourceFilePath')
        if (text) {
          if (text.length > 65) {
            text = text.slice(0, 65) + '...' + text.slice(text.length - 4)
          }
          technologySrc = `<a href="${question[src]}" target="”_blank”" >${text}</a>`
        }
        break
      case ('h5p'):
        text = question[src].replace('https://studio.libretexts.org/h5p/', '').replace('/embed', '')
        technologySrc = `<a href="${question[src].replace('/embed', '')}" target="”_blank”" ><img src="https://studio.libretexts.org/sites/default/files/LibreTexts_icon.png" alt="Libretexts logo" height="22" class="pb-1 pr-1">H5P Resource ID ${text} | LibreStudio</a>`
        break
      case ('imathas'):
        text = url.searchParams.get('id')
        technologySrc = `<a href="${question[src]}" target="”_blank”" >${text}</a>`
        break
      default:
        technologySrc = `Please Contact Us.  We have not yet implemented the sharing code for ${question[technology]}`
    }
    return technologySrc
  }
}

export function doCopy (adaptId) {
  this.$copyText(adaptId).then((e) => {
    this.$noty.success('The Question ID has been copied to your clipboard.')
  }, function (e) {
    this.$noty.error('We could not copy the Question ID to your clipboard.')
  })
}

export async function editQuestionSource (question) {
  if (this.isBetaAssignment) {
    this.$bvModal.show('modal-should-not-edit-question-source-if-beta-assignment')
    return false
  }
  if (!this.isMe) {
    if (question.library === 'adapt' &&
      this.user.role !== 5 &&
      question.question_editor_user_id !== this.user.id) {
      this.$noty.info('You cannot edit this question since you did not create it.')
      return false
    }
  }
  if (question.library === 'adapt') {
    if (this.user.role === 5) {
      question.id = question.question_id
    }
    await this.getQuestionToEdit(question)
    let modalId = `modal-edit-question-${question.id}`
    this.$bvModal.show(modalId)
    this.$nextTick(() => {
      updateModalToggleIndex(modalId)
    })
  } else {
    let mindtouchUrl = question.mindtouch_url ? question.mindtouch_url : `https://${question.library}.libretexts.org/@go/page/${question.page_id}`
    window.open(mindtouchUrl, '_blank')
  }
}

export async function getQuestionToEdit (questionToEdit) {
  console.log(questionToEdit)
  this.questionToEdit = questionToEdit
  try {
    const { data } = await axios.get(`/api/questions/get-question-to-edit/${questionToEdit.id}`)
    if (data.type === 'error') {
      this.$noty.error(data.message)
      return false
    }
    this.questionToEdit = data.question_to_edit
  } catch (error) {
    this.$noty.error(error.message)
  }
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


