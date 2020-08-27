import axios from 'axios'

export async function toggleQuestionFiles(questions, currentPage, assignmentId, noty){
  let question =  questions[currentPage - 1]
  question.questionFiles = !question.questionFiles
  try {
    const {data} = await axios.patch(`/api/assignments/${assignmentId}/questions/${question.id}/toggle-question-files`,
      {question_files : question.questionFiles })
    noty[data.type](data.message)
  } catch (error) {
    question.questionFiles = !question.questionFiles
    console.log(error)
   noty.error('We could not toggle the question files option.  Please try again or contact us for assistance.')
  }
}
