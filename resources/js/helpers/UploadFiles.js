import axios from 'axios'
import { fixInvalid } from './accessibility/FixInvalid'

export function getAcceptedFileTypes (fileTypes = '.pdf, .txt, .png, .jpeg, .jpg, .xlsx') {
  return fileTypes // update the validator in the S3 Trait if this changes
}

export function formatFileSize (size) {
  let sizes = [' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB']
  for (let i = 1; i < sizes.length; i++) {
    if (size < Math.pow(1024, i)) return (Math.round((size / Math.pow(1024, i - 1)) * 100) / 100) + sizes[i - 1]
  }
  return size
}

export function triggerFileSelect () {
  document.querySelector('#file').click()
}

export async function inputFilter (newFile, oldFile, prevent) {
  this.uploadFileForm.errors.clear()
  if (newFile && !oldFile) {
    // Filter non-image file
    if (parseInt(newFile.size) > 20000000) {
      let message = '20 MB max allowed.  Your file is too large.  '
      if (/\.(pdf)$/i.test(newFile.name)) {
        message += 'You might want to try an online PDF compressor such as https://smallpdf.com/compress-pdf to reduce the size.'
      }
      this.uploadFileForm.errors.set(this.uploadFileType, message)

      this.$nextTick(() => fixInvalid())
      this.allFormErrors = this.uploadFileForm.errors.flatten()
      this.$bvModal.show('modal-form-errors-file-upload')

      return prevent()
    }
    let validUploadTypesMessage
    let validExtension
    if (!this.isStructureImageUploader) {
      validUploadTypesMessage = `The valid upload types are: ${this.getSolutionUploadTypes()}`
      if (this.uploadLevel === 'question') {
        validExtension = this.isOpenEndedAudioSubmission ? /\.(mp3)$/i.test(newFile.name) : /\.(pdf|txt|png|jpeg|jpg|xlsx)$/i.test(newFile.name)
      } else {
        validExtension = /\.(pdf)$/i.test(newFile.name)
      }
    } else {
      validUploadTypesMessage = 'The valid upload types are: .png, .jpeg, .jpg'
      validExtension = /\.(png|jpeg|jpg)$/i.test(newFile.name)
    }

    if (!validExtension) {
      this.uploadFileForm.errors.set(this.uploadFileType, validUploadTypesMessage)
      this.$nextTick(() => fixInvalid())
      this.allFormErrors = this.uploadFileForm.errors.flatten()
      this.$bvModal.show('modal-form-errors-file-upload')
      return prevent()
    } else {
      try {
        this.preSignedURL = ''
        let uploadFileData = {
          assignment_id: this.assignmentId,
          upload_file_type: this.uploadFileType,
          file_name: newFile.name
        }
        const { data } = await axios.post('/api/s3/pre-signed-url', uploadFileData)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.preSignedURL = data.preSignedURL
        newFile.putAction = this.preSignedURL

        this.s3Key = data.s3_key
        this.originalFilename = newFile.name
        this.handledOK = false
      } catch (error) {
        this.$noty.error(error.message)
        return false
      }
    }
  }

  // Create a blob field
  newFile.blob = ''
  let URL = window.URL || window.webkitURL
  if (URL && URL.createObjectURL) {
    newFile.blob = URL.createObjectURL(newFile.file)
  }
}

export function inputFile (newFile, oldFile) {
  if (newFile && oldFile && !newFile.active && oldFile.active) {
    // Get response data

    if (newFile.xhr) {
      //  Get the response status code
      // console.log('status', newFile.xhr.status)
      if (newFile.xhr.status === 200) {
        if (!this.handledOK) {
          this.handledOK = true
          // console.log(this.handledOK)
          this.handleOK()
        }
      } else {
        this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
      }
    } else {
      this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
    }
  }
}

export async function submitUploadFile (type, form, noty, nextTick, bvModal, uploadFile, url, closeModal = true) {
  let typeFile = type + 'File'

  form.errors.clear(typeFile)
  // https://stackoverflow.com/questions/49328956/file-upload-with-vue-and-laravel
  let formData = new FormData()
  formData.append(typeFile, form[typeFile])
  formData.append('assignmentId', form.assignmentId)
  formData.append('questionId', form.questionId)
  formData.append('type', type)
  formData.append('s3_key', form.s3_key)
  formData.append('original_filename', form.original_filename)
  formData.append('uploadLevel', form.uploadLevel)// at the assignment or question level; used for cutups
  formData.append('_method', 'put') // add this

  const { data } = await axios.post(url, formData)

  if (data.type === 'error') {
    form.errors.set(type, data.message)
  } else {
    if (closeModal) {
      nextTick(() => {
        bvModal.hide(`modal-upload-file`)
      })
    }
    if (form.uploadLevel === 'question' && type === 'submission') {
      this.submissionDataMessage = data.message
      this.completedAllAssignmentQuestions = data.completed_all_assignment_questions
      this.cacheKey++
      this.completedAllAssignmentQuestions
        ? this.$bvModal.show('modal-completed-assignment')
        : this.$bvModal.show('modal-submission-accepted')
    } else {
      noty.success(data.message)
    }

    console.log(data)
    if (form.uploadLevel === 'assignment' && type === 'submission') {
      this.fullPdfUrl = data.full_pdf_url
    }
    if (form.uploadLevel === 'question' && type === 'submission') {
      // immediate feedback for them to see.
      // for assignments, they'll have to click on something else to get the information

      uploadFile.date_submitted = data.date_submitted
      uploadFile.original_filename = data.original_filename
      uploadFile.date_graded = uploadFile.text_feedback = 'N/A'
      uploadFile.submission_file_score = data.score ? data.score : 'N/A'
      uploadFile.file_feedback = null
      uploadFile.submission_file_exists = true
      uploadFile.submission = data.submission
      uploadFile.submission_file_url = data.submission_file_url
      uploadFile.can_give_up = data.can_give_up
    }
    if (type === 'solution') {
      uploadFile.solution = data.original_filename
      uploadFile.solution_file_url = data.solution_file_url
    }
  }
}
