import axios from 'axios'
export function  downloadSolutionFile(level, assignmentId, questionId, original_filename){
  let data =
    {
      'level': level,
      'question_id': questionId,
      'assignment_id': assignmentId
    }
  let url = '/api/solution-files/download'
  downloadFile(url, data, original_filename, this.$noty)
}
export function   downloadSubmissionFile(assignmentId, submission, original_filename) {
  let data =
    {
      'assignment_id': assignmentId,
      'submission': submission
    }
  let url = '/api/submission-files/download'
  downloadFile(url, data, original_filename, this.$noty)
}

export async function downloadFile(url, fileData, originalFilename, noty) {

  try {
    const {data} = await axios({
      method: 'post',
      url: url,
      responseType: 'arraybuffer',
      data: fileData
    })
    console.log(data)
    if (data.byteLength) {
      noty.success("The file is being downloaded.")
      let blob = new Blob([data], {type: 'application/pdf'})
      let link = document.createElement('a')
      link.href = window.URL.createObjectURL(blob)
      link.download = originalFilename
      link.click()
    } else {

      noty.error("We were not able to retrieve your file.  Please try again or contact us for assistance.")
    }
  } catch (error) {
    noty.error(error.message)
  }
}
