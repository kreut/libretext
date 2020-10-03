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
    console.log(data)
    if (data.byteLength) {
      noty.success("The file is being downloaded.")
      let blob = new Blob([data], {type: 'application/pdf'})
      let link = document.createElement('a')
      link.href = window.URL.createObjectURL(blob)
      link.download = original_filename
      link.click()
    } else {
      noty.error("We were not able to retrieve your file.  Please try again or contact us for assistance.")
    }
  } catch (error) {
    noty.error(error.message)
  }
}
