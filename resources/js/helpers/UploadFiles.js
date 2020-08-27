import axios from 'axios'

export async function submitUploadFile(type, form, noty, refs, nextTick, bvModal) {
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
    if (data.type === 'error') {
      form.errors.set(typeFile, data.message)
    } else {
      noty.success(data.message)
      nextTick(() => {
        bvModal.hide(`modal-upload-${type}-file`)
      })
    }
  } catch (error) {
    if (error.message.includes('status code 413')) {
      error.message = 'The maximum size allowed is 10MB.'
    }
    noty.error(error.message)

  }
  refs[type + 'FileInput'].reset()

}
