import axios from 'axios'

export function getAcceptedFileTypes() {
  return '.pdf, .txt, .png, .jpeg, .jpg' //update the validator in the S3 Trait if this changes
}
export async function submitUploadFile(type, form, noty, refs, nextTick, bvModal, uploadFile) {
  let typeFile = type + 'File'

  try {
    form.errors.set(typeFile, null)
    //https://stackoverflow.com/questions/49328956/file-upload-with-vue-and-laravel
    let formData = new FormData();

    formData.append(typeFile, form[typeFile])
    formData.append('assignmentId', form.assignmentId)
    formData.append('questionId', form.questionId)
    formData.append('type', type)
    formData.append('_method', 'put'); // add this
    const {data} = await axios.post(`/api/submission-files`, formData)
    console.log(data)
    if (data.type === 'error') {
      form.errors.set(typeFile, data.message)
    } else {
      noty.success(data.message)
      nextTick(() => {
        bvModal.hide(`modal-upload-${type}-file`)
      })

     if (type === 'question') {
       //immediate feedback for them to see.
       //for assignments, they'll have to click on something else to get the information
       uploadFile.date_submitted = data.date_submitted
       uploadFile.original_filename = data.original_filename
       uploadFile.date_graded = uploadFile.text_feedback = uploadFile.submission_file_score = 'N/A'
       uploadFile.file_feedback = null
       uploadFile.submission_file_exists = true
     }
    }
  } catch (error) {
    if (error.message.includes('status code 413')) {
      error.message = 'The maximum size allowed is 10MB.'
    }
    noty.error(error.message)

  }
  refs[type + 'FileInput'].reset()

}
