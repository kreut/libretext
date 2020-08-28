import axios from 'axios'

export async function downloadSubmission(assignment_id, submission, original_filename, noty) {

  try {
    const {data} = await axios({
      method: 'post',
      url: '/api/submission-files/download',
      responseType: 'arraybuffer',
      data: {
        'assignment_id': assignment_id,
        'submission': submission
      }
    })
    noty.success("The assignment file is being downloaded")
    let blob = new Blob([data], {type: 'application/pdf'})
    let link = document.createElement('a')
    link.href = window.URL.createObjectURL(blob)
    link.download = original_filename
    link.click()
  } catch (error) {
    noty.error(message)
  }
}
