import axios from 'axios'

export function getAcceptedFileTypes(fileTypes = '.pdf, .txt, .png, .jpeg, .jpg') {
 return fileTypes //update the validator in the S3 Trait if this changes
}
export async function submitUploadFile(type, form, noty,  nextTick, bvModal, uploadFile, url) {
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

    const {data} = await axios.post(url , formData)

    if (data.type === 'error') {
      form.errors.set(type, data.message)
    } else {
      noty.success(data.message)
      nextTick(() => {
        bvModal.hide(`modal-upload-file`)
      })
     if (type === 'question') {
       //immediate feedback for them to see.
       //for assignments, they'll have to click on something else to get the information
       uploadFile.date_submitted = data.date_submitted
       uploadFile.original_filename = data.original_filename
       uploadFile.date_graded = uploadFile.text_feedback = uploadFile.submission_file_score = 'N/A'
       uploadFile.file_feedback = null
       uploadFile.submission_file_exists = true
       uploadFile.submission = data.submission
     }
     if (type === 'solution'){
       uploadFile.solution = data.original_filename
     }
    }
  } catch (error) {
    if (error.message.includes('status code 413')) {
      error.message = 'The maximum size allowed is 10MB.'
    }
    noty.error(error.message)

  }
}
