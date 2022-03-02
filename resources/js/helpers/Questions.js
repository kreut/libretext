import axios from 'axios'

export function getTechnologySrc (technology, src, question) {
  let technologySrc = ''
  let text
  question[src] = question[src].replace('&amp;', '&')
  if (question[src]) {
    let url = new URL(question[src])
    switch (question[technology]) {
      case ('webwork'):
        text = url.searchParams.get('sourceFilePath')
        technologySrc = `<a href="${question[src]}" target="”_blank”" >webwork:${text}</a>`
        break
      case ('h5p'):
        text = question[src].replace('https://studio.libretexts.org/h5p/', '').replace('/embed', '')
        technologySrc = `<a href="${question[src]}" target="”_blank”" ><img src="https://studio.libretexts.org/sites/default/files/LibreTexts_icon.png" alt="Libretexts logo" height="22" class="pb-1 pr-1">H5P Resource ID ${text} | LibreStudio</a>`
        break
      case ('imathas'):
        text = url.searchParams.get('id')
        technologySrc = `<a href="${question[src]}" target="”_blank”" >imathas:${text}</a>`
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


